@extends('layouts.app')

@section('title', 'Edit Role Profile — ' . $profile->name)

@section('content')
<div class="container-fluid px-0">
    <div class="mb-4">
        <a href="{{ route('roleprofiles.index') }}" class="btn btn-sm btn-outline-secondary mb-2">
            <i class="bi bi-arrow-left me-1"></i>Back to Role Profiles
        </a>
        <h3 class="fw-bold text-dark mb-1">Edit Role Profile: {{ $profile->name }}</h3>
        <p class="text-secondary small mb-0">Update target job titles, technology stacks, and compensation criteria.</p>
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
            <form action="{{ route('roleprofiles.update', $profile->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Profile Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $profile->name) }}" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Years of Experience <span class="text-danger">*</span></label>
                        <input type="number" step="0.5" min="0" max="50" name="experience_years" class="form-control" value="{{ old('experience_years', $profile->experience_years) }}" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Target Job Titles (Comma-separated)</label>
                        <input type="text" name="target_titles" class="form-control" value="{{ old('target_titles', implode(', ', $profile->target_titles ?? [])) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Primary Technical Skills (Comma-separated)</label>
                        <textarea name="technical_skills" rows="3" class="form-control">{{ old('technical_skills', implode(', ', $profile->technical_skills ?? [])) }}</textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Secondary / Supporting Tools (Comma-separated)</label>
                        <textarea name="secondary_skills" rows="3" class="form-control">{{ old('secondary_skills', implode(', ', $profile->secondary_skills ?? [])) }}</textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Professional Summary / Value Proposition</label>
                        <textarea name="summary" rows="3" class="form-control">{{ old('summary', $profile->summary) }}</textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Preferred Work Modes</label>
                        <div class="d-flex gap-4 mt-2">
                            @php
                                $selectedModes = old('work_modes', $profile->work_modes ?? ['Remote']);
                            @endphp
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="work_modes[]" value="Remote" id="modeRemote" {{ in_array('Remote', $selectedModes) ? 'checked' : '' }}>
                                <label class="form-check-label" for="modeRemote">Remote</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="work_modes[]" value="Hybrid" id="modeHybrid" {{ in_array('Hybrid', $selectedModes) ? 'checked' : '' }}>
                                <label class="form-check-label" for="modeHybrid">Hybrid</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="work_modes[]" value="Onsite" id="modeOnsite" {{ in_array('Onsite', $selectedModes) ? 'checked' : '' }}>
                                <label class="form-check-label" for="modeOnsite">Onsite</label>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Location Preferences (Comma-separated)</label>
                        <input type="text" name="location_preferences" class="form-control" value="{{ old('location_preferences', implode(', ', $profile->location_preferences ?? [])) }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Minimum Salary / Compensation</label>
                        <input type="number" name="salary_min" class="form-control" value="{{ old('salary_min', $profile->salary_min) }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Maximum Target Salary</label>
                        <input type="number" name="salary_max" class="form-control" value="{{ old('salary_max', $profile->salary_max) }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Currency</label>
                        <select name="currency" class="form-select">
                            <option value="USD" {{ old('currency', $profile->currency) == 'USD' ? 'selected' : '' }}>USD ($)</option>
                            <option value="EUR" {{ old('currency', $profile->currency) == 'EUR' ? 'selected' : '' }}>EUR (€)</option>
                            <option value="GBP" {{ old('currency', $profile->currency) == 'GBP' ? 'selected' : '' }}>GBP (£)</option>
                            <option value="INR" {{ old('currency', $profile->currency) == 'INR' ? 'selected' : '' }}>INR (₹)</option>
                            <option value="CAD" {{ old('currency', $profile->currency) == 'CAD' ? 'selected' : '' }}>CAD ($)</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Discovery Keywords (Comma-separated)</label>
                        <input type="text" name="search_keywords" class="form-control" value="{{ old('search_keywords', implode(', ', $profile->search_keywords ?? [])) }}">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Default Outreach Email Signature</label>
                        <textarea name="email_signature" rows="3" class="form-control">{{ old('email_signature', $profile->email_signature) }}</textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                    <a href="{{ route('roleprofiles.index') }}" class="btn btn-light border">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4">Update Role Profile</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

