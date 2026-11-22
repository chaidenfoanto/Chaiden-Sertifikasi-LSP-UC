@extends('layouts.dashboard')

@section('title', 'Admin Dashboard')
@section('heading', 'Admin Dashboard')
@section('subtitle', 'Welcome, ' . auth()->user()->full_name . '.')

@section('body')
@php
    $cards = [
        ['Create Garages', 'Add garages in batches and set the prices per garage type.', route('admin.garages.create')],
        ['Verify Renters', 'Approve or reject new renter registrations.', route('admin.renters.index')],
        ['Extend Rentals', 'Extend the end date of rentals that are active today.', route('admin.rentals.index')],
        ['Reports', 'See rentals and revenue per month, day, garage type, or renter.', route('admin.reports.index')],
    ];
@endphp

<div class="grid gap-4 md:grid-cols-2">
    @foreach ($cards as [$title, $text, $url])
        <x-card class="flex flex-col">
            <h2 class="font-display font-bold">{{ $title }}</h2>
            <p class="mb-4 mt-1 text-sm text-neutral-600">{{ $text }}</p>
            <div class="mt-auto"><x-button :href="$url" size="sm">Open</x-button></div>
        </x-card>
    @endforeach
</div>
@endsection