<?php

namespace App\Mail;

use App\Models\Employee;
use App\Models\StaffDevice;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class StaffDevicesCollected extends Mailable
{
    public function __construct(public Employee $employee, public StaffDevice $collected)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Devices registered to you at UNBS');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.staff-devices-collected',
            with: ['devices' => $this->employee->devices()->where('is_active', true)->orderBy('owner_type')->get()],
        );
    }
}