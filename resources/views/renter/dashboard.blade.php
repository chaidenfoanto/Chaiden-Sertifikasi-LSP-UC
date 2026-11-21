@extends('layouts.app')

@section('title', 'Renter Dashboard')

@section('content')
<h1 class="h4 mb-3">Renter Dashboard</h1>
<p class="text-muted">Welcome, {{ auth()->user()->full_name }}.</p>
@endsection