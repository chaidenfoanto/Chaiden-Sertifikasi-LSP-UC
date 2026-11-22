@props(['href' => null, 'variant' => 'dark', 'size' => 'md'])
@php
    $base = 'inline-flex items-center justify-center rounded-lg font-semibold transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black';
    $variants = [
        'dark' => 'bg-black text-white hover:bg-neutral-700',
        'light' => 'bg-white text-black hover:bg-neutral-200',
        'outline' => 'border border-neutral-300 text-black hover:bg-neutral-100',
        'outline-light' => 'border border-white/40 text-white hover:bg-white/10',
        'success' => 'bg-green-700 text-white hover:bg-green-800',
        'outline-danger' => 'border border-red-300 text-red-700 hover:bg-red-50',
    ];
    $sizes = [
        'sm' => 'px-3 py-2 text-xs',
        'md' => 'px-4 py-2.5 text-sm',
        'lg' => 'px-6 py-3 text-base',
    ];
    $classes = $base . ' ' . $variants[$variant] . ' ' . $sizes[$size];
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'button', 'class' => $classes]) }}>{{ $slot }}</button>
@endif