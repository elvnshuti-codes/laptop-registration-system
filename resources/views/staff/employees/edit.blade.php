@extends('layouts.app')

@section('title', 'Edit Employee')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white py-3"><h4 class="mb-0">Edit employee</h4></div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('staff.employees.update', $employee) }}">
                    @csrf
                    @method('PUT')
                    @include('staff.employees._form')
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Save changes</button>
                        <a href="{{ route('staff.employees.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection