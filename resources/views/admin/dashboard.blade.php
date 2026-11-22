@extends('layouts.dashboard')

@section('title', 'Admin Dashboard')
@section('heading', 'Admin Dashboard')
@section('subtitle', 'Welcome, ' . auth()->user()->full_name . '. Here is where things stand today.')
@section('actions')
    <x-button :href="route('home')" variant="outline" size="sm">View website</x-button>
@endsection

@section('body')
@php
    $rupiah = fn (int $amount): string => 'Rp ' . number_format($amount, 0, ',', '.');
    $stats = [
        ['Pending registrations', $summary['pending_renters'], 'Review', route('admin.renters.index')],
        ['Active rentals today', $summary['active_rentals'], 'Extend a rental', route('admin.rentals.index')],
        ['Free garages today', $summary['garages_free_today'] . ' of ' . $summary['garages_total'], 'Create garages', route('admin.garages.create')],
        ['Revenue this month', $rupiah($summary['month_revenue']), 'Open reports', route('admin.reports.index')],
    ];
@endphp

<div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    @foreach ($stats as [$label, $value, $action, $url])
        <x-card>
            <p class="text-sm text-neutral-500">{{ $label }}</p>
            <p class="mt-1 font-display text-2xl font-bold">{{ $value }}</p>
            <a href="{{ $url }}" class="mt-3 inline-block text-xs font-semibold underline">{{ $action }}</a>
        </x-card>
    @endforeach
</div>

<div class="grid gap-6 lg:grid-cols-2">
    <x-card title="Pending registrations">
        @if ($summary['pending_list']->isEmpty())
            <p class="text-sm text-neutral-500">There are no pending registrations.</p>
        @else
            <x-table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Registered</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($summary['pending_list'] as $renter)
                        <tr>
                            <td>{{ $renter->full_name }}</td>
                            <td>{{ $renter->email }}</td>
                            <td class="whitespace-nowrap">{{ $renter->created_at->format('d M Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
            @if ($summary['pending_renters'] > $summary['pending_list']->count())
                <p class="mt-3 text-xs text-neutral-500">
                    Showing {{ $summary['pending_list']->count() }} of {{ $summary['pending_renters'] }}.
                    <a href="{{ route('admin.renters.index') }}" class="font-semibold underline">See all</a>
                </p>
            @endif
        @endif
    </x-card>

    <x-card title="Rentals ending within 7 days">
        @if ($summary['ending_soon']->isEmpty())
            <p class="text-sm text-neutral-500">No active rental ends in the next 7 days.</p>
        @else
            <x-table>
                <thead>
                    <tr>
                        <th>Garage</th>
                        <th>Renter</th>
                        <th>Ends</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($summary['ending_soon'] as $rental)
                        <tr>
                            <td>{{ $rental->garage->code }}</td>
                            <td>{{ $rental->user->full_name }}</td>
                            <td class="whitespace-nowrap">{{ $rental->end_date->toDateString() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
            <p class="mt-3 text-xs text-neutral-500">
                <a href="{{ route('admin.rentals.index') }}" class="font-semibold underline">Go to extend rentals</a>
            </p>
        @endif
    </x-card>
</div>

<x-card title="Garage types today" class="mt-6">
    <x-table>
        <thead>
            <tr>
                <th>Type</th>
                <th>Garages</th>
                <th>In use</th>
                <th>Free</th>
                <th>Daily price</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($summary['types'] as $type)
                <tr>
                    <td>{{ ucwords(str_replace('_', ' ', $type->name)) }} ({{ $type->code }})</td>
                    <td>{{ $type->garages_count }}</td>
                    <td>{{ $type->occupied_count }}</td>
                    <td>{{ $type->garages_count - $type->occupied_count }}</td>
                    <td>{{ $type->daily_price === null ? '-' : $rupiah($type->daily_price) }}</td>
                </tr>
            @endforeach
        </tbody>
    </x-table>
</x-card>
@endsection