@extends('layouts.legacy')

@section('title', 'My Rentals')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">My Rentals</h1>
    <a href="{{ route('renter.rentals.create') }}" class="btn btn-dark btn-sm">New rental</a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-4">
        @if ($rentals->isEmpty())
            <p class="text-muted mb-0">You have no rentals yet.</p>
        @else
            @php $today = now()->toDateString(); @endphp
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Garage</th>
                            <th>Vehicle</th>
                            <th>Period</th>
                            <th>Parts</th>
                            <th class="text-end">Charging</th>
                            <th class="text-end">Total</th>
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
                                    ? ['Upcoming', 'secondary']
                                    : ($end < $today ? ['Finished', 'dark'] : ['Active', 'success']);
                            @endphp
                            <tr>
                                <td>
                                    {{ $rental->garage->code }}
                                    <div class="small text-muted">{{ ucwords(str_replace('_', ' ', $rental->garage->type->name)) }}</div>
                                </td>
                                <td>
                                    {{ $rental->vehicle->brand }} {{ $rental->vehicle->model_name }}
                                    <div class="small text-muted">{{ $rental->vehicle->plate_number ?? 'No plate' }}</div>
                                </td>
                                <td>{{ $start }} to {{ $end }}</td>
                                <td class="small">
                                    {{ $rental->items->map(fn ($item) => ucfirst($item->rental_type) . ' ' . $item->days . ' days')->implode(', ') }}
                                </td>
                                <td class="text-end">Rp {{ number_format($rental->charging_cost, 0, ',', '.') }}</td>
                                <td class="text-end fw-semibold">Rp {{ number_format($rental->total_cost, 0, ',', '.') }}</td>
                                <td><span class="badge text-bg-{{ $status[1] }}">{{ $status[0] }}</span></td>
                                <td>
                                    <a href="{{ asset('storage/' . $rental->payment_proof_path) }}" target="_blank"
                                       class="btn btn-outline-secondary btn-sm">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection