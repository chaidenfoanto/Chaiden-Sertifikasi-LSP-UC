@extends('layouts.dashboard')

@section('title', 'Extend Rentals')
@section('heading', 'Extend Rentals')

@section('body')
<x-error-summary />

<x-card>
    <p class="mb-4 text-sm text-neutral-500">Only rentals that are active today are listed.</p>

    @if ($rentals->isEmpty())
        <p class="text-neutral-500">There are no active rentals today.</p>
    @else
        <x-table>
            <thead>
                <tr>
                    <th>Garage</th>
                    <th>Renter</th>
                    <th>Vehicle</th>
                    <th>Period</th>
                    <th class="text-right">Total</th>
                    <th class="text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rentals as $rental)
                    <tr>
                        <td>
                            {{ $rental->garage->code }}
                            <div class="text-xs text-neutral-500">{{ ucwords(str_replace('_', ' ', $rental->garage->type->name)) }}</div>
                        </td>
                        <td>{{ $rental->user->full_name }}</td>
                        <td>
                            {{ $rental->vehicle->brand }} {{ $rental->vehicle->model_name }}
                            <div class="text-xs text-neutral-500">{{ $rental->vehicle->plate_number ?? 'No plate' }}</div>
                        </td>
                        <td class="whitespace-nowrap">{{ $rental->start_date->toDateString() }} to {{ $rental->end_date->toDateString() }}</td>
                        <td class="text-right">Rp {{ number_format($rental->total_cost, 0, ',', '.') }}</td>
                        <td class="text-right">
                            <x-button size="sm" data-extend
                                      data-id="{{ $rental->id }}"
                                      data-garage="{{ $rental->garage->code }}"
                                      data-end="{{ $rental->end_date->toDateString() }}">Extend</x-button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>
    @endif
</x-card>

<dialog id="extendModal" class="m-auto w-full max-w-md rounded-2xl border border-neutral-200 p-0 backdrop:bg-black/50">
    <form method="POST" enctype="multipart/form-data" id="extendForm" class="space-y-4 p-6">
        @csrf
        <h2 class="font-display text-lg font-bold">Extend rental</h2>
        <p class="text-sm">
            Garage <strong id="extendGarage"></strong>, current end date <strong id="extendCurrentEnd"></strong>
        </p>
        <div>
            <label for="new_end_date" class="field-label">New end date</label>
            <input type="date" id="new_end_date" name="new_end_date" class="field" required>
        </div>
        <div id="extendPreview" class="text-sm text-neutral-500">Pick a new end date to see the extra cost.</div>
        <div>
            <label for="extend_payment_proof" class="field-label">Extra payment proof</label>
            <input type="file" id="extend_payment_proof" name="payment_proof" accept="image/png,image/jpeg" required
                   class="field file:mr-3 file:rounded-md file:border-0 file:bg-neutral-100 file:px-3 file:py-1.5 file:text-sm">
            <p class="mt-1.5 text-xs text-neutral-500">A jpg, jpeg, or png image, at most 5 MB.</p>
        </div>
        <div class="flex justify-end gap-2 pt-2">
            <x-button variant="outline" data-close>Cancel</x-button>
            <x-button type="submit">Save extension</x-button>
        </div>
    </form>
</dialog>
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
            preview.className = 'text-sm ' + className;
            preview.textContent = text;
        }

        document.querySelectorAll('[data-extend]').forEach((button) => {
            button.addEventListener('click', () => {
                currentId = button.dataset.id;

                form.reset();
                form.action = extendTemplate.replace('__ID__', currentId);
                garageLabel.textContent = button.dataset.garage;
                endLabel.textContent = button.dataset.end;
                newEnd.min = addDay(button.dataset.end);
                setPreview('Pick a new end date to see the extra cost.', 'text-neutral-500');
                modal.showModal();
            });
        });

        modal.querySelector('[data-close]').addEventListener('click', () => modal.close());

        newEnd.addEventListener('input', async () => {
            if (! newEnd.value) {
                setPreview('Pick a new end date to see the extra cost.', 'text-neutral-500');
                return;
            }

            const current = ++requestCounter;
            const url = quoteTemplate.replace('__ID__', currentId) + '?new_end_date=' + encodeURIComponent(newEnd.value);

            try {
                const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
                if (current !== requestCounter) return;

                if (response.status === 422) {
                    const body = await response.json();
                    setPreview(Object.values(body.errors).flat()[0], 'text-red-600');
                    return;
                }

                if (! response.ok) {
                    setPreview('The cost could not be calculated. Please try again.', 'text-red-600');
                    return;
                }

                const data = await response.json();
                const parts = data.items
                    .map((item) => item.rental_type + ' ' + item.days + ' days')
                    .join(', ');

                setPreview(
                    data.extra_days + ' extra day(s) (' + parts + '). Additional cost ' + rupiah(data.additional_cost),
                    'text-black'
                );
            } catch (error) {
                if (current === requestCounter) {
                    setPreview('The cost could not be calculated. Check your connection.', 'text-red-600');
                }
            }
        });
    })();
</script>
@endpush