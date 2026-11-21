@extends('layouts.app')

@section('title', 'Register')

@section('full')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-3">Register as Renter</h1>
                <form method="POST" action="{{ route('register.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full name</label>
                        <input type="text" id="full_name" name="full_name" value="{{ old('full_name') }}"
                               class="form-control @error('full_name') is-invalid @enderror" required>
                        @error('full_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="nik" class="form-label">NIK (16 digits)</label>
                        <input type="text" id="nik" name="nik" value="{{ old('nik') }}" maxlength="16"
                               class="form-control @error('nik') is-invalid @enderror" required>
                        @error('nik')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="phone_number" class="form-label">Phone number</label>
                        <input type="text" id="phone_number" name="phone_number" value="{{ old('phone_number') }}"
                               class="form-control @error('phone_number') is-invalid @enderror" required>
                        @error('phone_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email (used as username)</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}"
                               class="form-control @error('email') is-invalid @enderror" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password (min. 8 characters)</label>
                        <input type="password" id="password" name="password"
                               class="form-control @error('password') is-invalid @enderror" required>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Confirm password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation"
                               class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-dark w-100 py-2">Register</button>
                </form>
                <p class="text-center mt-3 mb-0">Already registered? <a href="{{ route('login') }}">Login</a></p>
            </div>
        </div>
    </div>
</div>
@endsection