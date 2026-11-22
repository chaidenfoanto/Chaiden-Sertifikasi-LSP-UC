@extends('layouts.dashboard')

@section('title', 'My Rentals')
@section('heading', 'My Rentals')

@section('actions')
    <x-button :href="route('renter.rentals.create')" size="sm">New rental</x-button>
@endsection

@section('body')
<x-card>
    @if ($rentals->isEmpty())
        <p class="text-neutral-500">You have no rentals yet.</p>
    @else
        @php $today = now()->toDateString(); @endphp
        <x-table>
            <thead>
                <tr>
                    <th>Garage</th>
                    <th>Vehicle</th>
                    <th>Period</th>
                    <th>Parts</th>
                    <th class="text-right">Charging</th>
                    <th class="text-right">Total</th>
                    <th>Status</th>
                    <th>Proof</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rentals as $rental)
                    @php
                        $start = $rental->start_date->toDateString();
                        $end = $rental->end_date->toDateString();
                        $status = $start > $today
                            ? ['Upcoming', 'bg-neutral-200 text-neutral-800']
                            : ($end < $today ? ['Finished', 'bg-black text-white'] : ['Active', 'bg-green-100 text-green-800']);
                    @endphp
                    <tr>
                        <td>
                            {{ $rental->garage->code }}
                            <div class="text-xs text-neutral-500">{{ ucwords(str_replace('_', ' ', $rental->garage->type->name)) }}</div>
                        </td>
                        <td>
                            {{ $rental->vehicle->brand }} {{ $rental->vehicle->model_name }}
                            <div class="text-xs text-neutral-500">{{ $rental->vehicle->plate_number ?? 'No plate' }}</div>
                        </td>
                        <td class="whitespace-nowrap">{{ $start }} to {{ $end }}</td>
                        <td class="text-xs">
                            {{ $rental->items->map(fn ($item) => ucfirst($item->rental_type) . ' ' . $item->days . ' days')->implode(', ') }}
                        </td>
                        <td class="text-right">Rp {{ number_format($rental->charging_cost, 0, ',', '.') }}</td>
                        <td class="text-right font-semibold">Rp {{ number_format($rental->total_cost, 0, ',', '.') }}</td>
                        <td><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $status[1] }}">{{ $status[0] }}</span></td>
                        <td>
                            <x-button :href="asset('storage/' . $rental->payment_proof_path)" variant="outline" size="sm" target="_blank">View</x-button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>
    @endif
</x-card>
@endsection