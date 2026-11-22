@extends('layouts.dashboard')

@section('title', 'Renter Dashboard')
@section('heading', 'Renter Dashboard')
@section('subtitle', 'Welcome, ' . auth()->user()->full_name . '.')

@section('body')
<div class="grid gap-4 md:grid-cols-2">
    <x-card class="flex flex-col">
        <h2 class="font-display font-bold">New Rental</h2>
        <p class="mb-4 mt-1 text-sm text-neutral-600">Pick your vehicle and dates, see the cost, and rent a garage.</p>
        <div class="mt-auto"><x-button :href="route('renter.rentals.create')" size="sm">Open</x-button></div>
    </x-card>
    <x-card class="flex flex-col">
        <h2 class="font-display font-bold">My Rentals</h2>
        <p class="mb-4 mt-1 text-sm text-neutral-600">Review your rental history and payment proofs.</p>
        <div class="mt-auto"><x-button :href="route('renter.rentals.index')" size="sm">Open</x-button></div>
    </x-card>
</div>
@endsection