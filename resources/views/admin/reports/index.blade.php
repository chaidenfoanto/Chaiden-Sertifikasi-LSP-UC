@extends('layouts.legacy')

@section('title', 'Reports')

@section('content')
<h1 class="h4 mb-3">Rental Reports</h1>

<div class="card shadow-sm mb-4">
    <div class="card-body p-4">
        <form method="GET" action="{{ route('admin.reports.index') }}" class="row g-3 align-items-end">
            <div class="col-md-2">
                <label for="group" class="form-label">Group by</label>
                <select id="group" name="group" class="form-select">
                    <option value="month" @selected($report['group'] === 'month')>Month</option>
                    <option value="day" @selected($report['group'] === 'day')>Day</option>
                    <option value="type" @selected($report['group'] === 'type')>Garage type</option>
                    <option value="renter" @selected($report['group'] === 'renter')>Renter</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="year" class="form-label">Year</label>
                <input type="number" id="year" name="year" value="{{ $report['year'] }}" min="2000" max="2100" placeholder="All years"
                       class="form-control">
            </div>
            <div class="col-md-2" id="monthGroup">
                <label for="month" class="form-label">Month</label>
                <select id="month" name="month" class="form-select">
                    @foreach (range(1, 12) as $number)
                        <option value="{{ $number }}" @selected($report['month'] === $number)>
                            {{ date('F', mktime(0, 0, 0, $number, 1)) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="garage_type_id" class="form-label">Garage type</label>
                <select id="garage_type_id" name="garage_type_id" class="form-select">
                    <option value="">All types</option>
                    @foreach ($garageTypes as $type)
                        <option value="{{ $type->id }}" @selected((int) ($filters['garage_type_id'] ?? 0) === $type->id)>
                            {{ ucwords(str_replace('_', ' ', $type->name)) }} ({{ $type->code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="user_id" class="form-label">Renter</label>
                <select id="user_id" name="user_id" class="form-select">
                    <option value="">All renters</option>
                    @foreach ($renters as $renter)
                        <option value="{{ $renter->id }}" @selected((int) ($filters['user_id'] ?? 0) === $renter->id)>
                            {{ $renter->full_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-dark">Apply filters</button>
                <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h2 class="h6 mb-1">Summary</h2>
                <p class="small text-muted">Rentals are counted on their start date. The total includes extensions. Type and renter reports cover all years when the year is empty.</p>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>{{ ['month' => 'Month', 'day' => 'Day', 'type' => 'Garage type', 'renter' => 'Renter'][$report['group']] }}</th>
                                <th class="text-end">Rentals</th>
                                <th class="text-end">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($report['summary'] as $row)
                                <tr @class(['text-muted' => $row['count'] === 0])>
                                    <td>{{ $row['label'] }}</td>
                                    <td class="text-end">{{ $row['count'] }}</td>
                                    <td class="text-end">Rp {{ number_format($row['total'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="fw-semibold">
                                <td>Total</td>
                                <td class="text-end">{{ $report['count'] }}</td>
                                <td class="text-end">Rp {{ number_format($report['grand_total'], 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h2 class="h6 mb-3">Rentals</h2>
                @if ($report['rentals']->isEmpty())
                    <p class="text-muted mb-0">No rentals match the selected filters.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Period</th>
                                    <th>Garage</th>
                                    <th>Renter</th>
                                    <th>Vehicle</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($report['rentals'] as $rental)
                                    <tr>
                                        <td>{{ $rental->start_date->toDateString() }} to {{ $rental->end_date->toDateString() }}</td>
                                        <td>
                                            {{ $rental->garage->code }}
                                            <div class="small text-muted">{{ ucwords(str_replace('_', ' ', $rental->garage->type->name)) }}</div>
                                        </td>
                                        <td>{{ $rental->user->full_name }}</td>
                                        <td>
                                            {{ $rental->vehicle->brand }} {{ $rental->vehicle->model_name }}
                                            <div class="small text-muted">{{ $rental->vehicle->plate_number ?? 'No plate' }}</div>
                                        </td>
                                        <td class="text-end">Rp {{ number_format($rental->total_cost, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // The month filter only matters when the report is grouped per day.
    const groupSelect = document.getElementById('group');
    const monthGroup = document.getElementById('monthGroup');

    function toggleMonthField() {
        monthGroup.style.display = groupSelect.value === 'day' ? 'block' : 'none';
    }

    groupSelect.addEventListener('change', toggleMonthField);
    toggleMonthField();
</script>
@endpush