@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<h1 class="h4 mb-1">Admin Dashboard</h1>
<p class="text-muted mb-4">Welcome, {{ auth()->user()->full_name }}.</p>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6">Create Garages</h2>
                <p class="text-muted small">Add garages in batches and set the prices per garage type.</p>
                <a href="{{ route('admin.garages.create') }}" class="btn btn-dark btn-sm">Open</a>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6">Verify Renters</h2>
                <p class="text-muted small">Approve or reject new renter registrations.</p>
                <a href="{{ route('admin.renters.index') }}" class="btn btn-dark btn-sm">Open</a>
            </div>
        </div>
    </div>
</div>
@endsection