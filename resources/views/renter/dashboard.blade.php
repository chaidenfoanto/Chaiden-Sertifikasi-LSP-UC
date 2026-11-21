@extends('layouts.legacy')

@section('title', 'Renter Dashboard')

@section('content')
<h1 class="h4 mb-1">Renter Dashboard</h1>
<p class="text-muted mb-4">Welcome, {{ auth()->user()->full_name }}.</p>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6">New Rental</h2>
                <p class="text-muted small">Pick your vehicle and dates, see the cost, and rent a garage.</p>
                <a href="{{ route('renter.rentals.create') }}" class="btn btn-dark btn-sm">Open</a>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6">My Rentals</h2>
                <p class="text-muted small">Review your rental history and payment proofs.</p>
                <a href="{{ route('renter.rentals.index') }}" class="btn btn-dark btn-sm">Open</a>
            </div>
        </div>
    </div>
</div>
@endsection