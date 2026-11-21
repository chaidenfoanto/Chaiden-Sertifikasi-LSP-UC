@props(['src', 'alt' => '', 'code' => null, 'priority' => false])
<div {{ $attributes->class('relative aspect-[4/3] overflow-hidden bg-linear-to-br from-neutral-800 to-neutral-950') }}>
    @if ($code)
        <span aria-hidden="true" class="absolute inset-0 grid place-items-center font-display text-8xl font-extrabold text-white/10">{{ $code }}</span>
    @endif
    <img src="{{ asset($src) }}" alt="{{ $alt }}" width="1200" height="900"
         @if ($priority) fetchpriority="high" @else loading="lazy" @endif
         class="absolute inset-0 size-full object-cover" onerror="this.remove()">
</div>