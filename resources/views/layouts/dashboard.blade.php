@extends('layouts.app')

{{-- Shared frame for the admin and renter pages: width, heading, and an optional action button. --}}
@section('content')
<div class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="font-display text-2xl font-bold tracking-tight">@yield('heading')</h1>
            @hasSection('subtitle')
                <p class="mt-1 text-neutral-600">@yield('subtitle')</p>
            @endif
        </div>
        @yield('actions')
    </div>

    @yield('body')
</div>
@endsection