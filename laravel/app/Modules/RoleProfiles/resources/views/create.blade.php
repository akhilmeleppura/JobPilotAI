@extends('layouts.app')

@section('title', 'Add Career Role Profile')

@section('content')
<div class="container-fluid px-0">
    <div class="mb-4">
        <a href="{{ route('roleprofiles.index') }}" class="btn btn-sm btn-outline-secondary mb-2">
            <i class="bi bi-arrow-left me-1"></i>Back to Role Profiles
        </a>
        <h3 class="fw-bold text-dark mb-1">Create Career Role Profile</h3>
        <p class="text-secondary small mb-0">Define a persona with specific titles, tech stacks, and compensation targets.</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0" style="border-radius: 14px;">
        <div class="card-body p-4">
            <form action="{{ route('roleprofiles.store') }}" method="POST">
                @csrf

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Profile Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Senior Laravel Backend Specialist" value="{{ old('name') }}" required>
                        <div class="form-text">A descriptive label identifying this career specialization.</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Years of Experience <span class="text-danger">*</span></label>
                        <input type="number" step="0.5" min="0" max="50" name="experience_years" class="form-control" value="{{ old('experience_years', 5.0) }}" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Target Job Titles (Comma-separated)</label>
                        <input type="text" name="target_titles" class="form-control" placeholder="Senior Laravel Developer, PHP Backend Engineer, API Architect" value="{{ old('target_titles') }}">
                        <div class="form-text">Used by job discovery algorithms to find matching postings.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Primary Technical Skills (Comma-separated)</label>
                        <textarea name="technical_skills" rows="3" class="form-control" placeholder="PHP 8.4, Laravel 11/12, MySQL, Redis, REST APIs, Docker, PHPUnit">{{ old('technical_skills') }}</textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Secondary / Supporting Tools (Comma-separated)</label>
                        <textarea name="secondary_skills" rows="3" class="form-control" placeholder="Livewire, Alpine.js, Tailwind CSS, AWS S3, GitHub Actions">{{ old('secondary_skills') }}</textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Professional Summary / Value Proposition</label>
                        <textarea name="summary" rows="3" class="form-control" placeholder="Experienced backend engineer with a focus on high-throughput microservices, database performance, and elegant domain modeling...">{{ old('summary') }}</textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Preferred Work Modes</label>
                        <div class="d-flex gap-4 mt-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="work_modes[]" value="Remote" id="modeRemote" checked>
                                <label class="form-check-label" for="modeRemote">Remote</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="work_modes[]" value="Hybrid" id="modeHybrid">
                                <label class="form-check-label" for="modeHybrid">Hybrid</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="work_modes[]" value="Onsite" id="modeOnsite">
                                <label class="form-check-label" for="modeOnsite">Onsite</label>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Location Preferences (Comma-separated)</label>
                        <input type="text" name="location_preferences" class="form-control" placeholder="Remote Worldwide, United States, Europe" value="{{ old('location_preferences') }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Minimum Salary / Compensation</label>
                        <input type="number" name="salary_min" class="form-control" placeholder="50000" value="{{ old('salary_min') }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Maximum Target Salary</label>
                        <input type="number" name="salary_max" class="form-control" placeholder="100000" value="{{ old('salary_max') }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Currency</label>
                        <select name="currency" class="form-select">
                            <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>USD ($)</option>
                            <option value="EUR" {{ old('currency') == 'EUR' ? 'selected' : '' }}>EUR (€)</option>
                            <option value="GBP" {{ old('currency') == 'GBP' ? 'selected' : '' }}>GBP (£)</option>
                            <option value="INR" {{ old('currency') == 'INR' ? 'selected' : '' }}>INR (₹)</option>
                            <option value="CAD" {{ old('currency') == 'CAD' ? 'selected' : '' }}>CAD ($)</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Discovery Keywords (Comma-separated)</label>
                        <input type="text" name="search_keywords" class="form-control" placeholder="Laravel, PHP, Symfony, Backend, API" value="{{ old('search_keywords') }}">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Default Outreach Email Signature</label>
                        <textarea name="email_signature" rows="3" class="form-control" placeholder="Best regards,&#10;Your Name&#10;Senior Laravel Specialist">{{ old('email_signature') }}</textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                    <a href="{{ route('roleprofiles.index') }}" class="btn btn-light border">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4">Save Role Profile</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

