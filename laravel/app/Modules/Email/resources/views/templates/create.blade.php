@extends('layouts.app')

@section('title', 'Create Outreach Template')

@section('content')
<div class="container-fluid px-0">
    <div class="mb-4">
        <a href="{{ route('email.templates.index') }}" class="btn btn-sm btn-outline-secondary mb-2">
            <i class="bi bi-arrow-left me-1"></i>Back to Templates
        </a>
        <h3 class="fw-bold text-dark mb-1">Create Communication Template</h3>
        <p class="text-secondary small mb-0">Construct reusable outreach emails with dynamic placeholder interpolation.</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0 small ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0" style="border-radius: 14px;">
                <div class="card-body p-4">
                    <form action="{{ route('email.templates.store') }}" method="POST">
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Template Category <span class="text-danger">*</span></label>
                                <select name="category" class="form-select form-select-sm" required>
                                    <option value="InitialOutreach">Initial Cold Application Outreach</option>
                                    <option value="FollowUp">Post-Application Follow-Up (5-Day Check-in)</option>
                                    <option value="RecruiterInquiry">Recruiter & Talent Pool Inquiry</option>
                                    <option value="InterviewConfirmation">Interview Confirmation & Logistics</option>
                                    <option value="PostInterviewThankYou">Post-Interview Thank You</option>
                                    <option value="OfferNegotiation">Offer & Compensation Negotiation</option>
                                    <option value="ContractProposal">Freelance & Contract Proposal</option>
                                    <option value="ReferralRequest">Internal Referral & Networking</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Template Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Senior Laravel Pitch — Direct Hiring Manager" value="{{ old('name') }}" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold small">Subject Line Template <span class="text-danger">*</span></label>
                                <input type="text" name="subject_template" class="form-control form-control-sm" placeholder="Application: @{{job_title}} — @{{candidate_name}}" value="{{ old('subject_template') }}" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold small">Body Template (HTML) <span class="text-danger">*</span></label>
                                <textarea name="body_template" rows="12" class="form-control" placeholder="<p>Dear @{{company}} Team,</p><p>I am writing to express my strong interest in the @{{job_title}} position...</p>" required>{{ old('body_template') }}</textarea>
                            </div>

                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_default" value="1" id="defaultCheck">
                                    <label class="form-check-label small fw-semibold" for="defaultCheck">
                                        Set as default template for this category
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                            <a href="{{ route('email.templates.index') }}" class="btn btn-sm btn-light border">Cancel</a>
                            <button type="submit" class="btn btn-sm btn-primary px-4">Save Template</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Variable Cheatsheet -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 sticky-top" style="border-radius: 14px; top: 80px;">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-2">Available Placeholder Variables</h6>
                    <p class="text-secondary small mb-3">Copy and paste these tags into your subject and body templates:</p>

                    <div class="list-group list-group-flush border rounded small">
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <code>@{{company}}</code>
                            <span class="text-muted">Target Company</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <code>@{{job_title}}</code>
                            <span class="text-muted">Job Position</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <code>@{{candidate_name}}</code>
                            <span class="text-muted">Your Full Name</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <code>@{{tech_stack}}</code>
                            <span class="text-muted">Profile Tech Stack</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <code>@{{experience_years}}</code>
                            <span class="text-muted">Years Experience</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <code>@{{github_url}}</code>
                            <span class="text-muted">GitHub Profile</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <code>@{{linkedin_url}}</code>
                            <span class="text-muted">LinkedIn Profile</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <code>@{{email_signature}}</code>
                            <span class="text-muted">Custom Signature</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <code>@{{current_date}}</code>
                            <span class="text-muted">Today's Date</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

