@extends('layouts.app')

@section('title', 'Create Garages')

@section('content')
<h1 class="h4 mb-3">Create Garages</h1>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.garages.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="garage_type_id" class="form-label">Garage type</label>
                        <select id="garage_type_id" name="garage_type_id"
                                class="form-select @error('garage_type_id') is-invalid @enderror" required>
                            <option value="">Select a type</option>
                            @foreach ($types as $type)
                                <option value="{{ $type->id }}" data-electric="{{ $type->name === 'electric_car' ? '1' : '0' }}"
                                    @selected(old('garage_type_id') == $type->id)>
                                    {{ ucwords(str_replace('_', ' ', $type->name)) }} ({{ $type->code }})
                                </option>
                            @endforeach
                        </select>
                        @error('garage_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Number of garages (1 to 999)</label>
                        <input type="number" id="quantity" name="quantity" value="{{ old('quantity') }}" min="1" max="999"
                               class="form-control @error('quantity') is-invalid @enderror" required>
                        @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="daily_price" class="form-label">Daily rental price (Rp)</label>
                        <input type="number" id="daily_price" name="daily_price" value="{{ old('daily_price') }}" min="1"
                               class="form-control @error('daily_price') is-invalid @enderror" required>
                        @error('daily_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">The monthly price is always 25 times the daily price.</div>
                    </div>
                    <div class="mb-3" id="electricityField" style="display: none;">
                        <label for="electricity_price_per_kwh" class="form-label">Electricity price per kWh (Rp)</label>
                        <input type="number" id="electricity_price_per_kwh" name="electricity_price_per_kwh"
                               value="{{ old('electricity_price_per_kwh') }}" min="1"
                               class="form-control @error('electricity_price_per_kwh') is-invalid @enderror">
                        @error('electricity_price_per_kwh')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-dark w-100">Create garages</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h2 class="h6 mb-3">Garage types</h2>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Garages</th>
                                <th>Daily</th>
                                <th>Monthly</th>
                                <th>Per kWh</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($types as $type)
                                <tr>
                                    <td>{{ ucwords(str_replace('_', ' ', $type->name)) }} ({{ $type->code }})</td>
                                    <td>{{ $type->garages_count }}</td>
                                    <td>{{ $type->daily_price === null ? '-' : 'Rp ' . number_format($type->daily_price, 0, ',', '.') }}</td>
                                    <td>{{ $type->monthly_price === null ? '-' : 'Rp ' . number_format($type->monthly_price, 0, ',', '.') }}</td>
                                    <td>{{ $type->electricity_price_per_kwh === null ? '-' : 'Rp ' . number_format($type->electricity_price_per_kwh, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Show the electricity price field only for the electric car type.
    const typeSelect = document.getElementById('garage_type_id');
    const electricityField = document.getElementById('electricityField');

    function toggleElectricityField() {
        const selected = typeSelect.options[typeSelect.selectedIndex];
        electricityField.style.display = selected && selected.dataset.electric === '1' ? 'block' : 'none';
    }

    typeSelect.addEventListener('change', toggleElectricityField);
    toggleElectricityField();
</script>
@endpush