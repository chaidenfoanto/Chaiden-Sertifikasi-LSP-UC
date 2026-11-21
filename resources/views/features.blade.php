@extends('layouts.app')

@section('title', 'Features')

@push('styles')
<style>
    /* Semi transparent gray layer that covers the features for visitors. */
    .locked-area { position: relative; }
    .locked-overlay {
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        z-index: 10;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        text-align: center;
        color: #fff;
        background-color: rgba(52, 58, 64, 0.7);
        border-radius: 0.5rem;
    }
</style>
@endpush

@section('content')
<h1 class="h4 mb-1">Features</h1>
<p class="text-muted mb-4">
    {{ $locked ? 'A preview of what you can do after you log in.' : 'Pick what you want to do.' }}
</p>

<div class="locked-area">
    {{-- "inert" keeps keyboard and screen reader users out of the locked preview. --}}
    <div @if ($locked) inert aria-hidden="true" @endif>
        @foreach ($groups as $group)
            <h2 class="h6 text-uppercase text-muted mb-3">{{ $group['title'] }}</h2>
            <div class="row g-3 mb-4">
                @foreach ($group['items'] as $item)
                    <div class="col-md-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-body">
                                <h3 class="h6">{{ $item['title'] }}</h3>
                                <p class="text-muted small">{{ $item['text'] }}</p>
                                @unless ($locked)
                                    <a href="{{ route($item['route']) }}" class="btn btn-dark btn-sm">Open</a>
                                @endunless
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>

    @if ($locked)
        <div class="locked-overlay">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2m3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2"/>
            </svg>
            <p class="h5 mt-3 mb-1">Log in or register to unlock these features.</p>
            <p class="small mb-3">New accounts are approved by an admin before you can log in.</p>
            <div class="d-flex gap-2">
                <a href="{{ route('login') }}" class="btn btn-light">Login</a>
                <a href="{{ route('register') }}" class="btn btn-outline-light">Register</a>
            </div>
        </div>
    @endif
</div>
@endsection