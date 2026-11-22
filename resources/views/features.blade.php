@extends('layouts.app')

@section('title', 'Features')

@section('content')
<div class="mx-auto max-w-6xl px-4 py-12 sm:px-6">
    <h1 class="font-display text-3xl font-bold tracking-tight">Features</h1>
    <p class="mb-8 mt-2 text-neutral-600">
        {{ $locked ? 'A preview of what you can do after you log in.' : 'Pick what you want to do.' }}
    </p>

    <div class="relative">
        {{-- "inert" keeps keyboard and screen reader users out of the locked preview. --}}
        <div @if ($locked) inert aria-hidden="true" @endif>
            @foreach ($groups as $group)
                <h2 class="mb-3 text-xs font-semibold uppercase tracking-wider text-neutral-500">{{ $group['title'] }}</h2>
                <div class="mb-8 grid gap-4 md:grid-cols-2">
                    @foreach ($group['items'] as $item)
                        <div class="flex flex-col rounded-2xl border border-neutral-200 bg-white p-6">
                            <h3 class="font-display font-bold">{{ $item['title'] }}</h3>
                            <p class="mb-4 mt-1 text-sm text-neutral-600">{{ $item['text'] }}</p>
                            @unless ($locked)
                                <div class="mt-auto"><x-button :href="route($item['route'])" size="sm">Open</x-button></div>
                            @endunless
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>

        @if ($locked)
            <div class="absolute inset-0 z-10 flex items-center justify-center rounded-2xl bg-neutral-500/60 p-4 backdrop-blur-[2px]">
                <div class="w-full max-w-sm rounded-2xl border border-neutral-200 bg-white p-6 text-center text-black shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto size-12" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                        <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2m3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2"/>
                    </svg>
                    <p class="mb-1 mt-3 font-display text-xl font-bold">Log in or register to unlock these features.</p>
                    <p class="mb-5 text-sm text-neutral-600">New accounts are approved by an admin before you can log in.</p>
                    <div class="flex justify-center gap-3">
                        <x-button :href="route('login')">Login</x-button>
                        <x-button :href="route('register')" variant="outline">Register</x-button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection