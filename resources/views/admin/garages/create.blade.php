@extends('layouts.dashboard')

@section('title', 'Create Garages')
@section('heading', 'Create Garages')

@section('body')
<div class="grid gap-6 lg:grid-cols-5">
    <x-card class="lg:col-span-2">
        <form method="POST" action="{{ route('admin.garages.store') }}" class="space-y-4">
            @csrf
            <div>
                <label for="garage_type_id" class="field-label">Garage type</label>
                <select id="garage_type_id" name="garage_type_id" required
                        @class(['field', 'border-red-600' => $errors->has('garage_type_id')])>
                    <option value="">Select a type</option>
                    @foreach ($types as $type)
                        <option value="{{ $type->id }}" data-electric="{{ $type->name === 'electric_car' ? '1' : '0' }}"
                            data-daily="{{ $type->daily_price }}" data-kwh="{{ $type->electricity_price_per_kwh }}"
                            data-count="{{ $type->garages_count }}"
                            @selected(old('garage_type_id') == $type->id)>
                            {{ ucwords(str_replace('_', ' ', $type->name)) }} ({{ $type->code }})
                        </option>
                    @endforeach
                </select>
                <x-field-error name="garage_type_id" />
            </div>
            <div>
                <label for="quantity" class="field-label">Number of garages (1 to 999)</label>
                <input type="number" id="quantity" name="quantity" value="{{ old('quantity') }}" min="1" max="999" required
                       @class(['field', 'border-red-600' => $errors->has('quantity')])>
                <x-field-error name="quantity" />
            </div>
            <div>
                <label for="daily_price" class="field-label">Daily rental price (Rp)</label>
                <input type="number" id="daily_price" name="daily_price" value="{{ old('daily_price') }}" min="1" required
                       @class(['field', 'border-red-600' => $errors->has('daily_price')])>
                <x-field-error name="daily_price" />
                <p class="mt-1.5 text-xs text-neutral-500">The monthly price is always 25 times the daily price.</p>
                <p id="priceNotice" class="mt-1.5 hidden text-xs font-medium text-amber-700"></p>
            </div>
            <div id="electricityField" class="hidden">
                <label for="electricity_price_per_kwh" class="field-label">Electricity price per kWh (Rp)</label>
                <input type="number" id="electricity_price_per_kwh" name="electricity_price_per_kwh"
                       value="{{ old('electricity_price_per_kwh') }}" min="1"
                       @class(['field', 'border-red-600' => $errors->has('electricity_price_per_kwh')])>
                <x-field-error name="electricity_price_per_kwh" />
            </div>
            <x-button type="submit" class="w-full">Create garages</x-button>
        </form>
    </x-card>

    <x-card title="Garage types" class="lg:col-span-3">
        <x-table>
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Garages</th>
                    <th>Used</th>
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
                        <td>{{ $type->used_garages_count }}</td>
                        <td>{{ $type->daily_price === null ? '-' : 'Rp ' . number_format($type->daily_price, 0, ',', '.') }}</td>
                        <td>{{ $type->monthly_price === null ? '-' : 'Rp ' . number_format($type->monthly_price, 0, ',', '.') }}</td>
                        <td>{{ $type->electricity_price_per_kwh === null ? '-' : 'Rp ' . number_format($type->electricity_price_per_kwh, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>
    </x-card>
</div>
@endsection

@push('scripts')
<script>
    const typeSelect = document.getElementById('garage_type_id');
    const electricityField = document.getElementById('electricityField');
    const dailyInput = document.getElementById('daily_price');
    const kwhInput = document.getElementById('electricity_price_per_kwh');
    const priceNotice = document.getElementById('priceNotice');

    // Show the electricity price field only for the electric car type.
    function toggleElectricityField() {
        const selected = typeSelect.options[typeSelect.selectedIndex];
        const electric = !! selected && selected.dataset.electric === '1';

        electricityField.classList.toggle('hidden', ! electric);
        kwhInput.required = electric;
    }

    // Warn that a new price also applies to garages that already exist.
    // When "prefill" is true the form is filled with the price in effect now.
    function showCurrentPrices(prefill) {
        const selected = typeSelect.options[typeSelect.selectedIndex];
        const count = selected ? Number(selected.dataset.count || 0) : 0;

        if (prefill) {
            dailyInput.value = selected?.dataset.daily || '';
            kwhInput.value = selected?.dataset.kwh || '';
        }

        priceNotice.classList.toggle('hidden', count === 0);
        priceNotice.textContent = count === 0
            ? ''
            : 'This type already has ' + count + ' garage(s). The price you enter applies to them as well.';
    }

    typeSelect.addEventListener('change', () => {
        toggleElectricityField();
        showCurrentPrices(true);
    });

    // On page load keep any values the admin typed before a validation error.
    toggleElectricityField();
    showCurrentPrices(false);
</script>
@endpush