@extends('layouts.guest')

@section('title', 'Register')

@section('content')
<form method="POST" action="{{ route('register') }}">
    @csrf

    <div class="mb-3">
        <label for="name" class="form-label small fw-semibold">Full Name</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-person"></i></span>
            <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required autofocus placeholder="John Doe">
        </div>
    </div>

    <div class="mb-3">
        <label for="email" class="form-label small fw-semibold">Email Address</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required placeholder="john@example.com">
        </div>
    </div>

    <div class="mb-3">
        <label for="password" class="form-label small fw-semibold">Password</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
            <input type="password" class="form-control" id="password" name="password" required placeholder="Minimum 8 characters">
        </div>
    </div>

    <div class="mb-4">
        <label for="password_confirmation" class="form-label small fw-semibold">Confirm Password</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-shield-check"></i></span>
            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required placeholder="Re-enter password">
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
        <i class="bi bi-person-plus-fill me-1"></i>Create Account
    </button>

    <div class="text-center mt-4">
        <span class="text-muted small">Already have an account?</span>
        <a href="{{ route('login') }}" class="small fw-semibold ms-1">Sign In</a>
    </div>
</form>
@endsection

