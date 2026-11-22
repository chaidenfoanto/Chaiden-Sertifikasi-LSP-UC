@props(['rental'])
{{-- Links to the renter's payment proof and to the proof of each extension (uploaded by the admin). --}}
<div class="flex flex-col gap-1 text-xs">
    <a href="{{ asset('storage/' . $rental->payment_proof_path) }}" target="_blank" rel="noopener"
       class="font-semibold underline underline-offset-2 hover:text-neutral-500">Renter payment</a>
    @foreach ($rental->extensions->sortBy('id') as $extension)
        <div>
            <a href="{{ asset('storage/' . $extension->payment_proof_path) }}" target="_blank" rel="noopener"
               class="underline underline-offset-2 hover:text-neutral-500">Extension {{ $loop->iteration }}</a>
            <span class="text-neutral-500">to {{ $extension->new_end_date->toDateString() }}, Rp {{ number_format($extension->additional_cost, 0, ',', '.') }}</span>
        </div>
    @endforeach
</div>