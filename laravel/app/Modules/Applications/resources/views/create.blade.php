@extends('layouts.app')

@section('title', 'Stage New Application')

@section('content')
<div class="container-fluid px-0">
    <div class="mb-4">
        <a href="{{ route('applications.index') }}" class="btn btn-sm btn-outline-secondary mb-2">
            <i class="bi bi-arrow-left me-1"></i>Back to Application Registry
        </a>
        <h3 class="fw-bold text-dark mb-1">Stage New Job Application</h3>
        <p class="text-secondary small mb-0">Record a new opportunity with automatic SHA-256 duplicate fingerprint checking.</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <h6 class="alert-heading fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i>Submission Alert</h6>
            <ul class="mb-0 small ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Dynamic Duplicate Alert Box -->
    <div id="duplicateAlertBox" class="alert d-none mb-4" role="alert">
        <div class="d-flex align-items-center gap-2">
            <i id="duplicateAlertIcon" class="bi fs-4"></i>
            <div>
                <strong id="duplicateAlertTitle"></strong>
                <div id="duplicateAlertMsg" class="small mt-1"></div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0" style="border-radius: 14px;">
        <div class="card-body p-4">
            <form action="{{ route('applications.store') }}" method="POST" id="stageApplicationForm">
                @csrf

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Company Name <span class="text-danger">*</span></label>
                        <input type="text" id="company_name" name="company_name" class="form-control check-duplicate-field" placeholder="e.g. Acme Cloud Corp" value="{{ old('company_name') }}" required>
                        <div class="form-text">Corporate designations like Inc./LLC are automatically normalized.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Position Title <span class="text-danger">*</span></label>
                        <input type="text" id="job_title" name="job_title" class="form-control check-duplicate-field" placeholder="e.g. Senior Laravel Engineer" value="{{ old('job_title') }}" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Location / Region</label>
                        <input type="text" id="location" name="location" class="form-control check-duplicate-field" placeholder="e.g. Remote, San Francisco, London" value="{{ old('location') }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Work Arrangement <span class="text-danger">*</span></label>
                        <select name="work_mode" class="form-select" required>
                            <option value="Remote" {{ old('work_mode') === 'Remote' ? 'selected' : '' }}>Remote</option>
                            <option value="Hybrid" {{ old('work_mode') === 'Hybrid' ? 'selected' : '' }}>Hybrid</option>
                            <option value="Onsite" {{ old('work_mode') === 'Onsite' ? 'selected' : '' }}>Onsite</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Submission Channel <span class="text-danger">*</span></label>
                        <select name="apply_method" class="form-select" required>
                            <option value="Email" {{ old('apply_method') === 'Email' ? 'selected' : '' }}>Email Outreach</option>
                            <option value="Portal" {{ old('apply_method') === 'Portal' ? 'selected' : '' }}>Company Portal (ATS)</option>
                            <option value="LinkedIn" {{ old('apply_method') === 'LinkedIn' ? 'selected' : '' }}>LinkedIn Easy Apply</option>
                            <option value="Direct" {{ old('apply_method') === 'Direct' ? 'selected' : '' }}>Direct Recruiter Contact</option>
                        </select>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Job Posting / Application URL</label>
                        <input type="url" id="apply_url" name="apply_url" class="form-control check-duplicate-field" placeholder="https://careers.acme.com/jobs/12345" value="{{ old('apply_url') }}">
                        <div class="form-text">Tracking parameters (utm_*, ref) will be stripped automatically.</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">External Requisition ID</label>
                        <input type="text" id="external_job_id" name="external_job_id" class="form-control check-duplicate-field" placeholder="REQ-98765" value="{{ old('external_job_id') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Target Career Persona</label>
                        <select name="role_profile_id" class="form-select">
                            <option value="">-- Select Matched Role Profile --</option>
                            @foreach($roleProfiles as $rp)
                                <option value="{{ $rp->id }}" {{ old('role_profile_id') == $rp->id || ($rp->is_default && !old('role_profile_id')) ? 'selected' : '' }}>
                                    {{ $rp->name }} {{ $rp->is_default ? '(Default)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">HR / Hiring Manager Email</label>
                        <input type="email" name="hr_email" class="form-control" placeholder="careers@acme.com" value="{{ old('hr_email') }}">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Notes / Context</label>
                        <textarea name="notes" rows="3" class="form-control" placeholder="Discovered via LinkedIn recommendation. Reached out to tech lead on Twitter.">{{ old('notes') }}</textarea>
                    </div>

                    <div class="col-12 d-none" id="overrideDuplicateContainer">
                        <div class="form-check p-3 bg-warning-subtle border border-warning rounded">
                            <input class="form-check-input ms-0 me-2" type="checkbox" name="override_duplicate" value="1" id="overrideCheck">
                            <label class="form-check-label fw-semibold text-dark" for="overrideCheck">
                                I confirm and authorize re-application for this opportunity (Reopen override).
                            </label>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                    <a href="{{ route('applications.index') }}" class="btn btn-light border">Cancel</a>
                    <button type="submit" id="submitBtn" class="btn btn-primary px-4">Stage Application</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const fields = document.querySelectorAll('.check-duplicate-field');
    const alertBox = document.getElementById('duplicateAlertBox');
    const alertIcon = document.getElementById('duplicateAlertIcon');
    const alertTitle = document.getElementById('duplicateAlertTitle');
    const alertMsg = document.getElementById('duplicateAlertMsg');
    const overrideContainer = document.getElementById('overrideDuplicateContainer');
    const submitBtn = document.getElementById('submitBtn');

    let debounceTimer;

    fields.forEach(field => {
        field.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(checkDuplicate, 600);
        });
    });

    function checkDuplicate() {
        const company = document.getElementById('company_name').value.trim();
        const title = document.getElementById('job_title').value.trim();
        if (!company || !title) return;

        const location = document.getElementById('location').value.trim();
        const externalId = document.getElementById('external_job_id').value.trim();
        const applyUrl = document.getElementById('apply_url').value.trim();

        fetch("{{ route('applications.check-duplicate') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                company_name: company,
                job_title: title,
                location: location,
                external_job_id: externalId,
                apply_url: applyUrl
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'EXACT_DUPLICATE') {
                alertBox.className = 'alert alert-danger mb-4';
                alertIcon.className = 'bi bi-shield-x text-danger fs-4';
                alertTitle.textContent = 'Duplicate Application Blocked!';
                alertMsg.textContent = data.message;
                alertBox.classList.remove('d-none');
                overrideContainer.classList.remove('d-none');
                submitBtn.disabled = true;
            } else if (data.status === 'REOPENED_AFTER_60_DAYS') {
                alertBox.className = 'alert alert-warning mb-4';
                alertIcon.className = 'bi bi-exclamation-triangle text-warning fs-4';
                alertTitle.textContent = 'Reopened Opportunity Detected (60+ Days)';
                alertMsg.textContent = data.message;
                alertBox.classList.remove('d-none');
                overrideContainer.classList.remove('d-none');
                submitBtn.disabled = false;
            } else if (data.status === 'ALREADY_STAGED') {
                alertBox.className = 'alert alert-info mb-4';
                alertIcon.className = 'bi bi-info-circle text-info fs-4';
                alertTitle.textContent = 'Already in Pipeline';
                alertMsg.textContent = data.message;
                alertBox.classList.remove('d-none');
                overrideContainer.classList.add('d-none');
                submitBtn.disabled = false;
            } else {
                alertBox.classList.add('d-none');
                overrideContainer.classList.add('d-none');
                submitBtn.disabled = false;
            }
        })
        .catch(err => console.error('Duplicate check error:', err));
    }

    const overrideCheck = document.getElementById('overrideCheck');
    if (overrideCheck) {
        overrideCheck.addEventListener('change', function() {
            if (this.checked) {
                submitBtn.disabled = false;
            }
        });
    }
});
</script>
@endpush
@endsection

