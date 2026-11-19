<?php

namespace App\Http\Controllers;

use App\Models\SampleReception;
use App\Models\SampleType;
use App\Models\VisitException;
use App\Models\VisitorVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SampleReceptionController extends Controller
{
    // Dashboard: every Testing visitor still on site, waiting ones first.
    public function index()
    {
        $visits = VisitorVisit::where('for_sample_reception', true)
            ->whereNotNull('gate_checked_in_at')
            ->whereNull('gate_checked_out_at')
            ->with(['samples', 'sampleReception', 'devices', 'receptions', 'exceptions'])
            ->withCount(['devices as devices_on_site' => fn ($q) => $q->whereNull('device_checked_out_at')])
            ->orderBy('gate_checked_in_at')
            ->get()
            ->sortBy(fn ($visit) => $visit->sampleReception ? 1 : 0)
            ->values();

        $onSite = $visits->count();
        $served = $visits->filter(fn ($visit) => $visit->sampleReception)->count();
        $waiting = $onSite - $served;

        // Testing visitors Entry recorded as leaving unserved, not yet acknowledged.
        // These stay listed even after the visitor has left, until someone acknowledges them.
        $unserved = VisitException::where('type', 'left_unserved')
            ->whereNull('acknowledged_at')
            ->whereHas('visit', fn ($q) => $q->where('for_sample_reception', true))
            ->with(['visit', 'recordedBy'])
            ->latest()
            ->get();

        return view('sample-reception.index', compact('visits', 'onSite', 'served', 'waiting', 'unserved'));
    }

    // Scan or type a visit code to open that visitor's page.
    public function lookup(Request $request)
    {
        $request->validate(['code' => 'required|string|max:100']);

        // Barcodes carry hyphens, printed codes carry slashes: treat them the same.
        $code = str_replace('/', '-', trim($request->input('code')));

        $visit = VisitorVisit::whereNull('gate_checked_out_at')
            ->whereRaw("REPLACE(visit_code, '/', '-') = ?", [$code])
            ->first();

        if (! $visit) {
            return back()->withInput()->withErrors(['code' => 'No visitor on site has that code. Check it and try again.']);
        }

        if (! $visit->for_sample_reception) {
            return back()->withInput()->withErrors(['code' => 'This visitor is for Standard House Reception, not Sample Reception.']);
        }

        return redirect()->route('sample.visits.show', $visit);
    }

    // The visitor's page: details, samples, devices, and the serve form.
    public function show(VisitorVisit $visit)
    {
        abort_unless($visit->for_sample_reception, 403, 'This visitor is not for Sample Reception.');

        $visit->load(['samples', 'devices', 'sampleReception.servedBy']);

        $sampleTypes = SampleType::options();
        $deliveredBy = GateController::DELIVERED_BY;

        return view('sample-reception.show', compact('visit', 'sampleTypes', 'deliveredBy'));
    }

    // The sample details, captured at Sample Reception (the Gate only keeps a short note).
    public function intake(Request $request, VisitorVisit $visit)
    {
        abort_unless($visit->for_sample_reception, 403, 'This visitor is not for Sample Reception.');

        if ($visit->gate_checked_out_at) {
            return back()->with('error', "{$visit->name} has already left the premises.");
        }

        if ($visit->sampleReception()->exists()) {
            return back()->with('error', "{$visit->name} has already been served. The sample details can no longer be changed.");
        }

        // If the owner is delivering, their own mobile is the owner's phone: no need to type it twice.
        if ($request->input('delivered_by_type') === 'Owner'
            && blank($request->input('sample_owner_phone'))
            && filled($visit->visitor_mobile_number)) {
            $request->merge(['sample_owner_phone' => $visit->visitor_mobile_number]);
        }

        $validated = $request->validate([
            'samples' => 'required|array|min:1|max:10',
            'samples.*.type' => ['required', 'distinct', Rule::exists('sample_types', 'name')],
            'samples.*.count' => 'required|integer|min:1|max:9999',
            'sample_owner_name' => 'nullable|string|max:255',
            'sample_owner_company' => 'required|string|max:255',
            'sample_owner_tin' => 'nullable|string|max:30',
            'sample_owner_phone' => 'nullable|required_without:sample_owner_email|string|max:20',
            'sample_owner_email' => 'nullable|email|max:255',
            'sample_prn' => 'nullable|string|max:50',
            'delivered_by_type' => ['required', Rule::in(array_keys(GateController::DELIVERED_BY))],
            'delivered_by_company' => 'nullable|required_if:delivered_by_type,Company representative,Delivery company|string|max:255',
        ], [
            'sample_owner_phone.required_without' => "Enter the owner's phone number or email (at least one is needed).",
            'samples.required' => 'Add at least one sample type.',
            'samples.*.type.required' => 'Choose a sample type for every row.',
            'samples.*.type.distinct' => 'Each sample type can only be listed once.',
            'samples.*.count.required' => 'Enter the number of samples for every row.',
            'sample_owner_company.required' => "Enter the owner's company.",
            'delivered_by_type.required' => 'Choose who is delivering the samples.',
        ]);

        $samples = $validated['samples'];
        unset($validated['samples']);

        if (! empty($validated['sample_prn'])) {
            $validated['sample_prn'] = strtoupper(trim($validated['sample_prn']));
        }

        // The person at the gate is the owner: no need to type the name twice.
        if ($validated['delivered_by_type'] === 'Owner') {
            $validated['sample_owner_name'] = $validated['sample_owner_name'] ?? $visit->name;
            $validated['delivered_by_company'] = null;
        }
        if ($validated['delivered_by_type'] === 'Delivery person') {
            $validated['delivered_by_company'] = null;
        }

        DB::transaction(function () use ($visit, $validated, $samples) {
            $visit->update($validated);

            $visit->samples()->delete();
            foreach ($samples as $row) {
                $visit->samples()->create(['sample_type' => $row['type'], 'sample_count' => $row['count']]);
            }
        });

        return redirect()
            ->route('sample.visits.show', $visit)
            ->with('success', 'Sample details saved.');
    }

    // Marks the visitor as served, with the reference number and an optional note.
    public function serve(Request $request, VisitorVisit $visit)
    {
        abort_unless($visit->for_sample_reception, 403, 'This visitor is not for Sample Reception.');

        if ($visit->gate_checked_out_at) {
            return back()->with('error', "{$visit->name} has already left the premises.");
        }

        if ($visit->sampleReception()->exists()) {
            return back()->with('error', "{$visit->name} has already been served.");
        }

        // The sample details are recorded here before the visitor is served.
        if (! $visit->samples()->exists() || blank($visit->sample_owner_company)) {
            return back()->with('error', 'Record the sample details first, then mark the visitor as served.');
        }

        $request->merge(['reference_number' => strtoupper(trim((string) $request->input('reference_number')))]);

        $validated = $request->validate([
            'reference_number' => 'nullable|string|max:50',
            'note' => 'nullable|string|max:500',
        ]);

        SampleReception::create([
            'visitor_visit_id' => $visit->id,
            'served_by' => auth()->id(),
            'reference_number' => $validated['reference_number'] ?: null,
            'note' => $validated['note'] ?? null,
            'served_at' => now(),
        ]);

        return redirect()
            ->route('sample.visits.show', $visit)
            ->with('success', "{$visit->name} marked as served.");
    }

    // The sample officer confirms they have seen a "left without being served" record.
    public function acknowledge(Request $request, VisitException $exception)
    {
        abort_unless($exception->type === 'left_unserved', 404);
        abort_unless($exception->visit->for_sample_reception, 403, 'This record belongs to Standard House Reception.');

        if ($exception->acknowledged_at) {
            return back()->with('error', 'That record was already acknowledged.');
        }

        $exception->update([
            'acknowledged_by' => $request->user()->id,
            'acknowledged_at' => now(),
        ]);

        return back()->with('success', "Acknowledged: {$exception->visit->name}.");
    }
}