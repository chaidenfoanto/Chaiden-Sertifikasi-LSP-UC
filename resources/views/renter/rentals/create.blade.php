@extends('layouts.dashboard')

@section('title', 'New Rental')
@section('heading', 'New Rental')

@section('body')
<x-error-summary />

<form method="POST" action="{{ route('renter.rentals.store') }}" enctype="multipart/form-data" id="rentalForm">
    @csrf
    <div class="grid gap-6 lg:grid-cols-5">
        <div class="space-y-6 lg:col-span-3">
            <x-card title="Vehicle">
                <div class="space-y-4">
                    <div>
                        <label for="vehicle_type" class="field-label">Vehicle type</label>
                        <select id="vehicle_type" name="vehicle_type" class="field" required>
                            <option value="">Select a vehicle type</option>
                            @foreach ($vehicleTypes as $type)
                                <option value="{{ $type }}" @selected(old('vehicle_type') === $type)>
                                    {{ ucwords(str_replace('_', ' ', $type)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div id="plateGroup">
                        <label for="plate_number" class="field-label">Plate number</label>
                        <input type="text" id="plate_number" name="plate_number" value="{{ old('plate_number') }}"
                               maxlength="15" class="field" placeholder="DD 1234 AB">
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label for="brand" class="field-label">Brand</label>
                            <input type="text" id="brand" name="brand" value="{{ old('brand') }}" maxlength="50" class="field" required>
                        </div>
                        <div>
                            <label for="model_name" class="field-label">Model</label>
                            <input type="text" id="model_name" name="model_name" value="{{ old('model_name') }}" maxlength="50" class="field" required>
                        </div>
                    </div>
                    <div id="batteryGroup" class="hidden">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label for="battery_capacity_kwh" class="field-label">Battery capacity (kWh)</label>
                                <input type="number" id="battery_capacity_kwh" name="battery_capacity_kwh"
                                       value="{{ old('battery_capacity_kwh') }}" min="0.01" max="500" step="0.01" class="field">
                            </div>
                            <div>
                                <label for="remaining_battery_kwh" class="field-label">Remaining battery (kWh)</label>
                                <input type="number" id="remaining_battery_kwh" name="remaining_battery_kwh"
                                       value="{{ old('remaining_battery_kwh') }}" min="0" max="500" step="0.01" class="field">
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-neutral-500">
                            An electric car in an electric car garage is charged to full. The charging cost is
                            (capacity minus remaining) multiplied by the price per kWh.
                        </p>
                    </div>
                </div>
            </x-card>

            <x-card title="Rental period">
                <input type="hidden" name="rental_type" id="rental_type" value="{{ old('rental_type', 'daily') }}">
                <div class="mb-4 grid grid-cols-2 gap-2" role="group" aria-label="Rental type">
                    <button type="button" data-rental-type="daily"
                            class="rounded-lg border px-4 py-2 text-sm font-semibold">Daily</button>
                    <button type="button" data-rental-type="monthly"
                            class="rounded-lg border px-4 py-2 text-sm font-semibold">Monthly</button>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="start_date" class="field-label">Start date</label>
                        <input type="date" id="start_date" name="start_date"
                            value="{{ old('start_date', now()->toDateString()) }}"
                            min="{{ now()->toDateString() }}" class="field" required>
                    </div>
                    <div id="endDateGroup">
                        <label for="end_date" class="field-label">End date</label>
                        <input type="date" id="end_date" name="end_date" value="{{ old('end_date') }}" class="field">
                    </div>
                </div>
                <p id="periodHint" class="mt-3 text-xs text-neutral-500"></p>
            </x-card>
        </div>

        <div class="space-y-6 lg:col-span-2">
            <x-card title="Rental summary">
                <div id="previewMessage" class="text-sm text-neutral-500">Fill in the vehicle type and duration to see the cost.</div>
                <div id="previewContent" class="hidden">
                    <p class="mb-3 text-sm">Period <strong id="previewDates"></strong></p>
                    <x-table>
                        <thead>
                            <tr><th>Type</th><th>Start</th><th>End</th><th>Days</th></tr>
                        </thead>
                        <tbody id="previewItems"></tbody>
                    </x-table>
                    <div class="mt-4">
                        <label for="garage_type" class="field-label">Garage type</label>
                        <select id="garage_type" name="garage_type" class="field" disabled>
                            <option value="">Select a vehicle type first</option>
                        </select>
                    </div>
                    <h3 class="mb-2 mt-4 text-sm font-semibold">Available garages</h3>
                    <div id="garageList" class="space-y-2"></div>
                </div>
            </x-card>

            <x-card title="Payment">
                <div class="mb-4 flex justify-between">
                    <span>Total cost</span>
                    <strong id="totalCost">-</strong>
                </div>
                <div class="mb-4">
                    <label for="payment_proof" class="field-label">Payment proof</label>
                    <input type="file" id="payment_proof" name="payment_proof" accept="image/png,image/jpeg" required
                           class="field file:mr-3 file:rounded-md file:border-0 file:bg-neutral-100 file:px-3 file:py-1.5 file:text-sm">
                    <p class="mt-1.5 text-xs text-neutral-500">A jpg, jpeg, or png image, at most 5 MB.</p>
                </div>
                <x-button type="submit" id="submitButton" class="w-full disabled:cursor-not-allowed disabled:opacity-50" disabled>Save rental</x-button>
            </x-card>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    (function () {
        const quoteUrl = @json(route('renter.rentals.quote'));
        const byId = (id) => document.getElementById(id);

        const vehicleType = byId('vehicle_type');
        const plateGroup = byId('plateGroup');
        const plateInput = byId('plate_number');
        const batteryGroup = byId('batteryGroup');
        const batteryCapacity = byId('battery_capacity_kwh');
        const batteryRemaining = byId('remaining_battery_kwh');
        const rentalType = byId('rental_type');
        const typeButtons = document.querySelectorAll('[data-rental-type]');
        const startDate = byId('start_date');
        const endDate = byId('end_date');
        const endDateGroup = byId('endDateGroup');
        const periodHint = byId('periodHint');
        const previewMessage = byId('previewMessage');
        const previewContent = byId('previewContent');
        const previewDates = byId('previewDates');
        const previewItems = byId('previewItems');
        const garageList = byId('garageList');
        const totalCost = byId('totalCost');
        const submitButton = byId('submitButton');

        const garageType = byId('garage_type');
        const compatibleTypes = {
            bicycle: ['bicycle'],
            motorcycle: ['bicycle'],
            car: ['car'],
            electric_car: ['car', 'electric_car'],
        };
        const typeLabels = {
            bicycle: 'Bicycle garage',
            car: 'Car garage',
            electric_car: 'Electric car garage',
        };
        let pendingGarageType = @json(old('garage_type'));

        let garages = [];
        let selectedGarageId = @json(old('garage_id'));
        let requestCounter = 0;
        let timer = null;

        const rupiah = (amount) => 'Rp ' + Number(amount).toLocaleString('id-ID');

        function el(tag, className, text) {
            const node = document.createElement(tag);
            if (className) node.className = className;
            if (text !== undefined) node.textContent = text;
            return node;
        }

        function isElectric() {
            return vehicleType.value === 'electric_car';
        }

        // Show or hide fields that depend on the vehicle and rental type.
        function syncFields() {
            const bicycle = vehicleType.value === 'bicycle';
            plateGroup.classList.toggle('hidden', bicycle);
            plateInput.disabled = bicycle;
            plateInput.required = ! bicycle;

            batteryGroup.classList.toggle('hidden', ! isElectric());
            batteryCapacity.disabled = ! isElectric();
            batteryRemaining.disabled = ! isElectric();
            batteryCapacity.required = isElectric();
            batteryRemaining.required = isElectric();

            syncPeriod();
        }

        // Date helpers work in UTC so daylight saving never shifts a day.
        const pad = (number) => String(number).padStart(2, '0');
        const toIso = (date) => date.getUTCFullYear() + '-' + pad(date.getUTCMonth() + 1) + '-' + pad(date.getUTCDate());
        const parseIso = (text) => {
            const [year, month, day] = text.split('-').map(Number);
            return new Date(Date.UTC(year, month - 1, day));
        };
        const addDays = (date, amount) => new Date(date.getTime() + amount * 86400000);
        const daysInMonthOf = (date) => new Date(Date.UTC(date.getUTCFullYear(), date.getUTCMonth() + 1, 0)).getUTCDate();

        // Highlight the chosen rental type and explain the date rules for the start month.
        function syncPeriod() {
            const monthly = rentalType.value === 'monthly';

            typeButtons.forEach((button) => {
                const active = button.dataset.rentalType === rentalType.value;
                button.classList.toggle('bg-black', active);
                button.classList.toggle('text-white', active);
                button.classList.toggle('border-black', active);
                button.classList.toggle('bg-white', ! active);
                button.classList.toggle('text-neutral-700', ! active);
                button.classList.toggle('border-neutral-300', ! active);
            });

            endDateGroup.classList.toggle('hidden', monthly);
            endDate.disabled = monthly;
            endDate.required = ! monthly;

            if (! startDate.value) {
                periodHint.textContent = '';
                return;
            }

            const start = parseIso(startDate.value);
            const monthDays = daysInMonthOf(start);
            const monthName = start.toLocaleString('en-US', { month: 'long', timeZone: 'UTC' });

            if (monthly) {
                periodHint.textContent = 'A monthly rental that starts in ' + monthName + ' lasts ' + monthDays
                    + ' days and ends on ' + toIso(addDays(start, monthDays - 1))
                    + '. It costs 25 times the daily price.';
                return;
            }

            const maxDays = monthDays - 1;
            const latest = toIso(addDays(start, maxDays - 1));
            endDate.min = startDate.value;
            endDate.max = latest;
            periodHint.textContent = 'A daily rental that starts in ' + monthName + ' can last at most ' + maxDays
                + ' days, so the latest end date is ' + latest + '. For a longer stay, save this rental and make'
                + ' another one that starts the day after it ends.';
        }

        function buildQuery() {
            const params = new URLSearchParams();
            params.set('vehicle_type', vehicleType.value);
            params.set('rental_type', rentalType.value);
            if (rentalType.value === 'daily' && endDate.value) params.set('end_date', endDate.value);
            if (startDate.value) params.set('start_date', startDate.value);
            if (isElectric()) {
                if (batteryCapacity.value) params.set('battery_capacity_kwh', batteryCapacity.value);
                if (batteryRemaining.value) params.set('remaining_battery_kwh', batteryRemaining.value);
            }
            return params.toString();
        }

        // Garages of the chosen garage type only.
        function visibleGarages() {
            return garages.filter((item) => item.type === garageType.value);
        }

        // Fill the garage type options with the types the vehicle may use.
        function syncGarageTypes() {
            const allowed = compatibleTypes[vehicleType.value] || [];
            const previous = garageType.value || pendingGarageType || '';
            pendingGarageType = null;

            garageType.replaceChildren();
            garageType.appendChild(new Option(allowed.length ? 'Select a garage type' : 'Select a vehicle type first', ''));
            allowed.forEach((name) => garageType.appendChild(new Option(typeLabels[name], name)));
            garageType.disabled = allowed.length === 0;

            // A single compatible type is picked automatically.
            garageType.value = allowed.includes(previous) ? previous : (allowed.length === 1 ? allowed[0] : '');
        }

        function updateTotal() {
            const garage = visibleGarages().find((item) => String(item.id) === String(selectedGarageId));
            totalCost.textContent = garage ? rupiah(garage.total) : '-';
            submitButton.disabled = ! garage;
        }

        function setMessage(text, isError) {
            garages = [];
            previewContent.classList.add('hidden');
            previewMessage.classList.remove('hidden');
            previewMessage.className = 'text-sm ' + (isError ? 'text-red-600' : 'text-neutral-500');
            previewMessage.textContent = text;
            updateTotal();
        }

        function render(data) {
            garages = data.garages;
            previewMessage.classList.add('hidden');
            previewContent.classList.remove('hidden');
            previewDates.textContent = data.start_date + ' to ' + data.end_date;

            previewItems.replaceChildren();
            data.items.forEach((item) => {
                const row = document.createElement('tr');
                row.appendChild(el('td', 'capitalize', item.rental_type));
                row.appendChild(el('td', '', item.start_date));
                row.appendChild(el('td', '', item.end_date));
                row.appendChild(el('td', '', item.days));
                previewItems.appendChild(row);
            });

            renderGarages();
        }

        // Show the available garages of the chosen garage type.
        function renderGarages() {
            garageList.replaceChildren();
            const visible = visibleGarages();

            if (! garageType.value) {
                garageList.appendChild(el('p', 'text-sm text-neutral-500', 'Choose a garage type to see the available garages.'));
            } else if (visible.length === 0) {
                garageList.appendChild(el('p', 'text-sm text-neutral-500', 'No garage of this type is available for these dates.'));
            }

            visible.forEach((garage) => {
                const label = el('label', 'flex cursor-pointer items-center gap-3 rounded-lg border border-neutral-200 p-3 hover:bg-neutral-50');
                const radio = document.createElement('input');
                radio.type = 'radio';
                radio.name = 'garage_id';
                radio.value = garage.id;
                radio.className = 'size-4 accent-black';
                radio.checked = String(garage.id) === String(selectedGarageId);
                radio.addEventListener('change', () => {
                    selectedGarageId = garage.id;
                    updateTotal();
                });

                const info = el('div', 'flex-1');
                info.appendChild(el('div', 'text-sm font-semibold', garage.code + ' (' + garage.type.replace('_', ' ') + ')'));
                if (garage.charging_cost > 0) {
                    info.appendChild(el('div', 'text-xs text-neutral-500', 'Includes charging ' + rupiah(garage.charging_cost)));
                }

                label.appendChild(radio);
                label.appendChild(info);
                label.appendChild(el('div', 'text-sm font-semibold', rupiah(garage.total)));
                garageList.appendChild(label);
            });

            updateTotal();
        }

        async function loadQuote() {
            if (! vehicleType.value || (rentalType.value === 'daily' && ! endDate.value)) {
                setMessage('Fill in the vehicle type and duration to see the cost.', false);
                return;
            }

            const current = ++requestCounter;

            try {
                const response = await fetch(quoteUrl + '?' + buildQuery(), {
                    headers: { 'Accept': 'application/json' },
                });

                if (current !== requestCounter) return;

                if (response.status === 422) {
                    const body = await response.json();
                    setMessage(Object.values(body.errors).flat()[0], true);
                    return;
                }

                if (! response.ok) {
                    setMessage('The cost could not be calculated. Please try again.', true);
                    return;
                }

                render(await response.json());
            } catch (error) {
                if (current === requestCounter) {
                    setMessage('The cost could not be calculated. Check your connection.', true);
                }
            }
        }

        function schedule() {
            clearTimeout(timer);
            timer = setTimeout(loadQuote, 300);
        }

        [vehicleType, startDate, endDate, batteryCapacity, batteryRemaining].forEach((field) => {
            field.addEventListener('input', () => {
                syncFields();
                schedule();
            });
        });

        vehicleType.addEventListener('input', () => {
            syncGarageTypes();
            renderGarages();
        });

        // The daily and monthly buttons write into the hidden rental_type field.
        typeButtons.forEach((button) => {
            button.addEventListener('click', () => {
                rentalType.value = button.dataset.rentalType;
                syncFields();
                schedule();
            });
        });
        garageType.addEventListener('change', renderGarages);

        syncFields();
        syncGarageTypes();
        loadQuote();
    })();
</script>
@endpush