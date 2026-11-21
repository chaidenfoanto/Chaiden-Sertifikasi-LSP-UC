@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<h1 class="h4 mb-1">Admin Dashboard</h1>
<p class="text-muted mb-4">Welcome, {{ auth()->user()->full_name }}.</p>

@php
    $cards = [
        ['Create Garages', 'Add garages in batches and set the prices per garage type.', route('admin.garages.create')],
        ['Verify Renters', 'Approve or reject new renter registrations.', route('admin.renters.index')],
        ['Extend Rentals', 'Extend the end date of rentals that are active today.', route('admin.rentals.index')],
        ['Reports', 'See rentals and revenue per month, day, garage type, or renter.', route('admin.reports.index')],
    ];
@endphp

<div class="row g-3">
    @foreach ($cards as [$title, $text, $url])
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6">{{ $title }}</h2>
                    <p class="text-muted small">{{ $text }}</p>
                    <a href="{{ $url }}" class="btn btn-dark btn-sm">Open</a>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection