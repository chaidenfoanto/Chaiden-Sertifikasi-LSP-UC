@props(['title' => null])
{{-- White bordered panel used for forms and tables on the dashboard pages. --}}
<section {{ $attributes->class('rounded-2xl border border-neutral-200 bg-white p-6') }}>
    @if ($title)
        <h2 class="mb-4 font-display font-bold">{{ $title }}</h2>
    @endif
    {{ $slot }}
</section>