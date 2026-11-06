@extends('layouts.guest')

@section('title', 'Sign In')

@section('content')
<form method="POST" action="{{ route('login') }}">
    @csrf

    <div class="mb-3">
        <label for="email" class="form-label small fw-semibold">Email Address</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required autofocus placeholder="name@domain.com">
        </div>
    </div>

    <div class="mb-3">
        <label for="password" class="form-label small fw-semibold">Password</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-key"></i></span>
            <input type="password" class="form-control" id="password" name="password" required placeholder="••••••••">
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="remember" id="remember">
            <label class="form-check-label small" for="remember">Remember me</label>
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
        <i class="bi bi-box-arrow-in-right me-1"></i>Sign In
    </button>

    <div class="text-center mt-4">
        <span class="text-muted small">New to JobPilot AI?</span>
        <a href="{{ route('register') }}" class="small fw-semibold ms-1">Create Account</a>
    </div>
</form>
@endsection

