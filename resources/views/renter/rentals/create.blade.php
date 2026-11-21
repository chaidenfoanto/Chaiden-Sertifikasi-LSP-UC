@extends('layouts.legacy')

@section('title', 'New Rental')

@section('content')
<h1 class="h4 mb-3">New Rental</h1>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('renter.rentals.store') }}" enctype="multipart/form-data" id="rentalForm">
    @csrf
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm mb-4">
                <div class="card-body p-4">
                    <h2 class="h6 mb-3">Vehicle</h2>
                    <div class="mb-3">
                        <label for="vehicle_type" class="form-label">Vehicle type</label>
                        <select id="vehicle_type" name="vehicle_type" class="form-select" required>
                            <option value="">Select a vehicle type</option>
                            @foreach ($vehicleTypes as $type)
                                <option value="{{ $type }}" @selected(old('vehicle_type') === $type)>
                                    {{ ucwords(str_replace('_', ' ', $type)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3" id="plateGroup">
                        <label for="plate_number" class="form-label">Plate number</label>
                        <input type="text" id="plate_number" name="plate_number" value="{{ old('plate_number') }}"
                               maxlength="15" class="form-control" placeholder="DD 1234 AB">
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="brand" class="form-label">Brand</label>
                            <input type="text" id="brand" name="brand" value="{{ old('brand') }}" maxlength="50"
                                   class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="model_name" class="form-label">Model</label>
                            <input type="text" id="model_name" name="model_name" value="{{ old('model_name') }}"
                                   maxlength="50" class="form-control" required>
                        </div>
                    </div>
                    <div class="row g-3 mt-0" id="batteryGroup" style="display: none;">
                        <div class="col-md-6">
                            <label for="battery_capacity_kwh" class="form-label">Battery capacity (kWh)</label>
                            <input type="number" id="battery_capacity_kwh" name="battery_capacity_kwh"
                                   value="{{ old('battery_capacity_kwh') }}" min="0.01" max="500" step="0.01"
                                   class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label for="remaining_battery_kwh" class="form-label">Remaining battery (kWh)</label>
                            <input type="number" id="remaining_battery_kwh" name="remaining_battery_kwh"
                                   value="{{ old('remaining_battery_kwh') }}" min="0" max="500" step="0.01"
                                   class="form-control">
                        </div>
                        <div class="col-12">
                            <div class="form-text">
                                An electric car in an electric car garage is charged to full. The charging cost is
                                (capacity minus remaining) multiplied by the price per kWh.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h6 mb-3">Rental period</h2>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="rental_type" class="form-label">Rental type</label>
                            <select id="rental_type" name="rental_type" class="form-select" required>
                                <option value="daily" @selected(old('rental_type', 'daily') === 'daily')>Daily</option>
                                <option value="monthly" @selected(old('rental_type') === 'monthly')>Monthly</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="quantity" id="quantityLabel" class="form-label">Number of days (1 to 366)</label>
                            <input type="number" id="quantity" name="quantity" value="{{ old('quantity') }}" min="1"
                                   max="366" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label for="start_date" class="form-label">Start date</label>
                            <input type="text" id="start_date" value="{{ now()->toDateString() }}"
                                   class="form-control" readonly>
                        </div>
                        <div class="col-12">
                            <div class="form-text">
                                A daily part can be at most the days of its month minus 1. A longer rental is split
                                automatically into monthly and daily parts, and a monthly part costs 25 times the daily price.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm mb-4">
                <div class="card-body p-4">
                    <h2 class="h6 mb-3">Rental summary</h2>
                    <div id="previewMessage" class="small text-muted">Fill in the vehicle type and duration to see the cost.</div>
                    <div id="previewContent" style="display: none;">
                        <p class="mb-2">Period <strong id="previewDates"></strong></p>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr><th>Type</th><th>Start</th><th>End</th><th>Days</th></tr>
                                </thead>
                                <tbody id="previewItems"></tbody>
                            </table>
                        </div>
                        <div class="mb-3">
                            <label for="garage_type" class="form-label">Garage type</label>
                            <select id="garage_type" name="garage_type" class="form-select" disabled>
                                <option value="">Select a vehicle type first</option>
                            </select>
                        </div>
                        <h3 class="h6 mt-3">Available garages</h3>
                        <div id="garageList" class="list-group"></div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h6 mb-3">Payment</h2>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Total cost</span>
                        <strong id="totalCost">-</strong>
                    </div>
                    <div class="mb-3">
                        <label for="payment_proof" class="form-label">Payment proof</label>
                        <input type="file" id="payment_proof" name="payment_proof" accept="image/png,image/jpeg"
                               class="form-control" required>
                        <div class="form-text">A jpg, jpeg, or png image, at most 5 MB.</div>
                    </div>
                    <button type="submit" id="submitButton" class="btn btn-dark w-100" disabled>Save rental</button>
                </div>
            </div>
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
        const quantity = byId('quantity');
        const quantityLabel = byId('quantityLabel');
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
            plateGroup.style.display = bicycle ? 'none' : 'block';
            plateInput.disabled = bicycle;

            batteryGroup.style.display = isElectric() ? 'flex' : 'none';
            batteryCapacity.disabled = ! isElectric();
            batteryRemaining.disabled = ! isElectric();
            batteryCapacity.required = isElectric();
            batteryRemaining.required = isElectric();

            const monthly = rentalType.value === 'monthly';
            quantityLabel.textContent = monthly ? 'Number of months (1 to 12)' : 'Number of days (1 to 366)';
            quantity.max = monthly ? 12 : 366;
        }

        function buildQuery() {
            const params = new URLSearchParams();
            params.set('vehicle_type', vehicleType.value);
            params.set('rental_type', rentalType.value);
            params.set('quantity', quantity.value);
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
            previewContent.style.display = 'none';
            previewMessage.style.display = 'block';
            previewMessage.className = 'small ' + (isError ? 'text-danger' : 'text-muted');
            previewMessage.textContent = text;
            updateTotal();
        }

        function render(data) {
            garages = data.garages;
            previewMessage.style.display = 'none';
            previewContent.style.display = 'block';
            previewDates.textContent = data.start_date + ' to ' + data.end_date;

            previewItems.replaceChildren();
            data.items.forEach((item) => {
                const row = document.createElement('tr');
                row.appendChild(el('td', 'text-capitalize', item.rental_type));
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
                garageList.appendChild(el('div', 'text-muted small', 'Choose a garage type to see the available garages.'));
            } else if (visible.length === 0) {
                garageList.appendChild(el('div', 'text-muted small', 'No garage of this type is available for these dates.'));
            }

            visible.forEach((garage) => {
                const label = el('label', 'list-group-item d-flex align-items-center gap-3');
                const radio = document.createElement('input');
                radio.type = 'radio';
                radio.name = 'garage_id';
                radio.value = garage.id;
                radio.className = 'form-check-input mt-0';
                radio.checked = String(garage.id) === String(selectedGarageId);
                radio.addEventListener('change', () => {
                    selectedGarageId = garage.id;
                    updateTotal();
                });

                const info = el('div', 'flex-grow-1');
                info.appendChild(el('div', 'fw-semibold', garage.code + ' (' + garage.type.replace('_', ' ') + ')'));
                if (garage.charging_cost > 0) {
                    info.appendChild(el('div', 'small text-muted', 'Includes charging ' + rupiah(garage.charging_cost)));
                }

                label.appendChild(radio);
                label.appendChild(info);
                label.appendChild(el('div', 'fw-semibold', rupiah(garage.total)));
                garageList.appendChild(label);
            });

            updateTotal();
        }

        async function loadQuote() {
            if (! vehicleType.value || ! quantity.value) {
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

        [vehicleType, rentalType, quantity, batteryCapacity, batteryRemaining].forEach((field) => {
            field.addEventListener('input', () => {
                syncFields();
                schedule();
            });
        });

        vehicleType.addEventListener('input', () => {
            syncGarageTypes();
            renderGarages();
        });
        garageType.addEventListener('change', renderGarages);

        syncFields();
        syncGarageTypes();
        loadQuote();
    })();
</script>
@endpush