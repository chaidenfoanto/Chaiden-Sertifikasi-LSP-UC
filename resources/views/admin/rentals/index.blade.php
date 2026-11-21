@extends('layouts.legacy')

@section('title', 'Extend Rentals')

@section('content')
<h1 class="h4 mb-3">Extend Rentals</h1>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card shadow-sm">
    <div class="card-body p-4">
        <p class="text-muted small">Only rentals that are active today are listed.</p>

        @if ($rentals->isEmpty())
            <p class="text-muted mb-0">There are no active rentals today.</p>
        @else
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Garage</th>
                            <th>Renter</th>
                            <th>Vehicle</th>
                            <th>Period</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rentals as $rental)
                            <tr>
                                <td>
                                    {{ $rental->garage->code }}
                                    <div class="small text-muted">{{ ucwords(str_replace('_', ' ', $rental->garage->type->name)) }}</div>
                                </td>
                                <td>{{ $rental->user->full_name }}</td>
                                <td>
                                    {{ $rental->vehicle->brand }} {{ $rental->vehicle->model_name }}
                                    <div class="small text-muted">{{ $rental->vehicle->plate_number ?? 'No plate' }}</div>
                                </td>
                                <td>{{ $rental->start_date->toDateString() }} to {{ $rental->end_date->toDateString() }}</td>
                                <td class="text-end">Rp {{ number_format($rental->total_cost, 0, ',', '.') }}</td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-dark btn-sm" data-bs-toggle="modal"
                                            data-bs-target="#extendModal" data-id="{{ $rental->id }}"
                                            data-garage="{{ $rental->garage->code }}"
                                            data-end="{{ $rental->end_date->toDateString() }}">
                                        Extend
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<div class="modal fade" id="extendModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" enctype="multipart/form-data" id="extendForm" class="modal-content">
            @csrf
            <div class="modal-header">
                <h2 class="modal-title h5">Extend rental</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">
                    Garage <strong id="extendGarage"></strong>, current end date <strong id="extendCurrentEnd"></strong>
                </p>
                <div class="mb-3">
                    <label for="new_end_date" class="form-label">New end date</label>
                    <input type="date" id="new_end_date" name="new_end_date" class="form-control" required>
                </div>
                <div id="extendPreview" class="small text-muted mb-3">Pick a new end date to see the extra cost.</div>
                <div class="mb-0">
                    <label for="extend_payment_proof" class="form-label">Extra payment proof</label>
                    <input type="file" id="extend_payment_proof" name="payment_proof" accept="image/png,image/jpeg"
                           class="form-control" required>
                    <div class="form-text">A jpg, jpeg, or png image, at most 5 MB.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-dark">Save extension</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const quoteTemplate = @json(route('admin.rentals.quote', ['rental' => '__ID__']));
        const extendTemplate = @json(route('admin.rentals.extend', ['rental' => '__ID__']));

        const modal = document.getElementById('extendModal');
        const form = document.getElementById('extendForm');
        const garageLabel = document.getElementById('extendGarage');
        const endLabel = document.getElementById('extendCurrentEnd');
        const newEnd = document.getElementById('new_end_date');
        const preview = document.getElementById('extendPreview');

        let currentId = null;
        let requestCounter = 0;

        const rupiah = (amount) => 'Rp ' + Number(amount).toLocaleString('id-ID');

        // Add one day to a YYYY-MM-DD string without time zone surprises.
        function addDay(iso) {
            const [year, month, day] = iso.split('-').map(Number);
            return new Date(Date.UTC(year, month - 1, day + 1)).toISOString().slice(0, 10);
        }

        function setPreview(text, className) {
            preview.className = 'small mb-3 ' + className;
            preview.textContent = text;
        }

        modal.addEventListener('show.bs.modal', (event) => {
            const button = event.relatedTarget;
            currentId = button.dataset.id;

            form.reset();
            form.action = extendTemplate.replace('__ID__', currentId);
            garageLabel.textContent = button.dataset.garage;
            endLabel.textContent = button.dataset.end;
            newEnd.min = addDay(button.dataset.end);
            setPreview('Pick a new end date to see the extra cost.', 'text-muted');
        });

        newEnd.addEventListener('input', async () => {
            if (! newEnd.value) {
                setPreview('Pick a new end date to see the extra cost.', 'text-muted');
                return;
            }

            const current = ++requestCounter;
            const url = quoteTemplate.replace('__ID__', currentId) + '?new_end_date=' + encodeURIComponent(newEnd.value);

            try {
                const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
                if (current !== requestCounter) return;

                if (response.status === 422) {
                    const body = await response.json();
                    setPreview(Object.values(body.errors).flat()[0], 'text-danger');
                    return;
                }

                if (! response.ok) {
                    setPreview('The cost could not be calculated. Please try again.', 'text-danger');
                    return;
                }

                const data = await response.json();
                const parts = data.items
                    .map((item) => item.rental_type + ' ' + item.days + ' days')
                    .join(', ');

                setPreview(
                    data.extra_days + ' extra day(s) (' + parts + '). Additional cost ' + rupiah(data.additional_cost),
                    'text-dark'
                );
            } catch (error) {
                if (current === requestCounter) {
                    setPreview('The cost could not be calculated. Check your connection.', 'text-danger');
                }
            }
        });
    })();
</script>
@endpush