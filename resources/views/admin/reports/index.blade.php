@extends('layouts.dashboard')

@section('title', 'Reports')
@section('heading', 'Rental Reports')

@section('body')
<x-card class="mb-6">
    <form method="GET" action="{{ route('admin.reports.index') }}" class="grid items-end gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <div>
            <label for="group" class="field-label">Group by</label>
            <select id="group" name="group" class="field">
                <option value="month" @selected($report['group'] === 'month')>Month</option>
                <option value="day" @selected($report['group'] === 'day')>Day</option>
                <option value="type" @selected($report['group'] === 'type')>Garage type</option>
                <option value="renter" @selected($report['group'] === 'renter')>Renter</option>
            </select>
        </div>
        <div>
            <label for="year" class="field-label">Year</label>
            <input type="number" id="year" name="year" value="{{ $report['year'] }}" min="2000" max="2100"
                   placeholder="All years" class="field">
        </div>
        <div id="monthGroup">
            <label for="month" class="field-label">Month</label>
            <select id="month" name="month" class="field">
                @foreach (range(1, 12) as $number)
                    <option value="{{ $number }}" @selected($report['month'] === $number)>
                        {{ date('F', mktime(0, 0, 0, $number, 1)) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="garage_type_id" class="field-label">Garage type</label>
            <select id="garage_type_id" name="garage_type_id" class="field">
                <option value="">All types</option>
                @foreach ($garageTypes as $type)
                    <option value="{{ $type->id }}" @selected((int) ($filters['garage_type_id'] ?? 0) === $type->id)>
                        {{ ucwords(str_replace('_', ' ', $type->name)) }} ({{ $type->code }})
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="user_id" class="field-label">Renter</label>
            <select id="user_id" name="user_id" class="field">
                <option value="">All renters</option>
                @foreach ($renters as $renter)
                    <option value="{{ $renter->id }}" @selected((int) ($filters['user_id'] ?? 0) === $renter->id)>
                        {{ $renter->full_name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2 sm:col-span-2 lg:col-span-5">
            <x-button type="submit">Apply filters</x-button>
            <x-button :href="route('admin.reports.index')" variant="outline">Reset</x-button>
        </div>
    </form>
</x-card>

<div class="grid gap-6 lg:grid-cols-5">
    <x-card title="Summary" class="lg:col-span-2">
        <p class="-mt-2 mb-4 text-xs text-neutral-500">Rentals are counted on their start date. The total includes extensions. Type and renter reports cover all years when the year is empty.</p>
        <x-table>
            <thead>
                <tr>
                    <th>{{ ['month' => 'Month', 'day' => 'Day', 'type' => 'Garage type', 'renter' => 'Renter'][$report['group']] }}</th>
                    <th class="text-right">Rentals</th>
                    <th class="text-right">Revenue</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($report['summary'] as $row)
                    <tr @class(['text-neutral-400' => $row['count'] === 0])>
                        <td>{{ $row['label'] }}</td>
                        <td class="text-right">{{ $row['count'] }}</td>
                        <td class="text-right">Rp {{ number_format($row['total'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td>Total</td>
                    <td class="text-right">{{ $report['count'] }}</td>
                    <td class="text-right">Rp {{ number_format($report['grand_total'], 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </x-table>
    </x-card>

    <x-card title="Rentals" class="lg:col-span-3">
        @if ($report['rentals']->isEmpty())
            <p class="text-neutral-500">No rentals match the selected filters.</p>
        @else
            <x-table>
                <thead>
                    <tr>
                        <th>Period</th>
                        <th>Garage</th>
                        <th>Renter</th>
                        <th>Vehicle</th>
                        <th class="text-right">Total</th>
                        <th>Payment proof</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($report['rentals'] as $rental)
                        <tr>
                            <td class="whitespace-nowrap">{{ $rental->start_date->toDateString() }} to {{ $rental->end_date->toDateString() }}</td>
                            <td>
                                {{ $rental->garage->code }}
                                <div class="text-xs text-neutral-500">{{ ucwords(str_replace('_', ' ', $rental->garage->type->name)) }}</div>
                            </td>
                            <td>{{ $rental->user->full_name }}</td>
                            <td>
                                {{ $rental->vehicle->brand }} {{ $rental->vehicle->model_name }}
                                <div class="text-xs text-neutral-500">{{ $rental->vehicle->plate_number ?? 'No plate' }}</div>
                            </td>
                            <td class="text-right">Rp {{ number_format($rental->total_cost, 0, ',', '.') }}</td>
                            <td><x-proof-links :rental="$rental" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
        @endif
    </x-card>
</div>
@endsection

@push('scripts')
<script>
    // The month filter only matters when the report is grouped per day.
    const groupSelect = document.getElementById('group');
    const monthGroup = document.getElementById('monthGroup');

    function toggleMonthField() {
        monthGroup.classList.toggle('hidden', groupSelect.value !== 'day');
    }

    groupSelect.addEventListener('change', toggleMonthField);
    toggleMonthField();
</script>
@endpush