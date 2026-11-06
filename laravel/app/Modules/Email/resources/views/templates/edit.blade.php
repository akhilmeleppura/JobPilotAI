@extends('layouts.app')

@section('title', 'Edit Template — ' . $template->name)

@section('content')
<div class="container-fluid px-0">
    <div class="mb-4">
        <a href="{{ route('email.templates.index') }}" class="btn btn-sm btn-outline-secondary mb-2">
            <i class="bi bi-arrow-left me-1"></i>Back to Templates
        </a>
        <h3 class="fw-bold text-dark mb-1">Edit Template: {{ $template->name }}</h3>
        <p class="text-secondary small mb-0">Refine template content or use the AI Assistant to tailor it to specific roles.</p>
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
        <!-- Main Form -->
        <div class="col-lg-7">
            <div class="card shadow-sm border-0" style="border-radius: 14px;">
                <div class="card-body p-4">
                    <form action="{{ route('email.templates.update', $template->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Template Category <span class="text-danger">*</span></label>
                                <select name="category" class="form-select form-select-sm" required>
                                    @foreach(['InitialOutreach', 'FollowUp', 'RecruiterInquiry', 'InterviewConfirmation', 'PostInterviewThankYou', 'OfferNegotiation', 'ContractProposal', 'ReferralRequest'] as $c)
                                        <option value="{{ $c }}" {{ old('category', $template->category) === $c ? 'selected' : '' }}>
                                            {{ $c }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Template Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control form-control-sm" value="{{ old('name', $template->name) }}" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold small">Subject Line Template <span class="text-danger">*</span></label>
                                <input type="text" name="subject_template" class="form-control form-control-sm" value="{{ old('subject_template', $template->subject_template) }}" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold small">Body Template (HTML) <span class="text-danger">*</span></label>
                                <textarea id="bodyTemplateArea" name="body_template" rows="12" class="form-control" required>{{ old('body_template', $template->body_template) }}</textarea>
                            </div>

                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_default" value="1" id="defaultCheck" {{ old('is_default', $template->is_default) ? 'checked' : '' }}>
                                    <label class="form-check-label small fw-semibold" for="defaultCheck">
                                        Set as default template for this category
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                            <a href="{{ route('email.templates.index') }}" class="btn btn-sm btn-light border">Cancel</a>
                            <button type="submit" class="btn btn-sm btn-primary px-4">Update Template</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Mini AI Assistant Panel -->
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 mb-4 border-primary border-top border-4" style="border-radius: 14px;">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-stars text-primary fs-4"></i>
                        <h6 class="fw-bold text-dark mb-0">Mini AI Assistant (Email Tailor)</h6>
                    </div>
                    <p class="text-secondary small mb-3">
                        Use free local/cloud AI (Gemini / Groq) to tailor this email template to a specific vacancy.
                    </p>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Target Company</label>
                        <input type="text" id="aiCompany" class="form-control form-control-sm" placeholder="e.g. Stripe">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Job Title</label>
                        <input type="text" id="aiJobTitle" class="form-control form-control-sm" placeholder="e.g. Senior Backend Engineer">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Vacancy Keywords / Description</label>
                        <textarea id="aiDesc" rows="3" class="form-control form-control-sm" placeholder="Paste requirements snippet..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Tone / Custom Instructions</label>
                        <input type="text" id="aiInstructions" class="form-control form-control-sm" placeholder="e.g. Emphasize MySQL optimization and high availability">
                    </div>

                    <button type="button" id="tailorBtn" class="btn btn-sm btn-outline-primary w-100 d-inline-flex align-items-center justify-content-center gap-1">
                        <i class="bi bi-magic"></i>
                        <span>Tailor Email with AI</span>
                    </button>

                    <div id="aiSpinner" class="text-center d-none mt-3">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        <span class="small text-muted ms-2">Generating personalized outreach...</span>
                    </div>

                    <div id="aiSuccessAlert" class="alert alert-success small mt-3 d-none">
                        <i class="bi bi-check-circle me-1"></i>Tailored draft generated! Review and click below to apply to editor.
                        <button type="button" id="applyDraftBtn" class="btn btn-sm btn-success w-100 mt-2">Apply to Body Editor</button>
                    </div>
                </div>
            </div>

            <!-- Placeholders reference -->
            <div class="card shadow-sm border-0" style="border-radius: 14px;">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-2">Variables Reference</h6>
                    <div class="d-flex flex-wrap gap-1">
                        <span class="badge bg-light text-dark border">@{{company}}</span>
                        <span class="badge bg-light text-dark border">@{{job_title}}</span>
                        <span class="badge bg-light text-dark border">@{{candidate_name}}</span>
                        <span class="badge bg-light text-dark border">@{{tech_stack}}</span>
                        <span class="badge bg-light text-dark border">@{{experience_years}}</span>
                        <span class="badge bg-light text-dark border">@{{github_url}}</span>
                        <span class="badge bg-light text-dark border">@{{email_signature}}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tailorBtn = document.getElementById('tailorBtn');
    const aiSpinner = document.getElementById('aiSpinner');
    const aiSuccess = document.getElementById('aiSuccessAlert');
    const applyDraftBtn = document.getElementById('applyDraftBtn');
    const bodyArea = document.getElementById('bodyTemplateArea');

    let generatedHtml = '';

    tailorBtn.addEventListener('click', function() {
        const company = document.getElementById('aiCompany').value.trim() || 'Target Company';
        const title = document.getElementById('aiJobTitle').value.trim() || 'Software Engineer';
        const desc = document.getElementById('aiDesc').value.trim();
        const instructions = document.getElementById('aiInstructions').value.trim();

        aiSpinner.classList.remove('d-none');
        aiSuccess.classList.add('d-none');

        fetch("{{ route('email.templates.tailor-ai', $template->id) }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                company_name: company,
                job_title: title,
                description: desc,
                instructions: instructions
            })
        })
        .then(res => res.json())
        .then(data => {
            aiSpinner.classList.add('d-none');
            if (data.success) {
                generatedHtml = data.tailored_body;
                aiSuccess.classList.remove('d-none');
            } else {
                alert('AI Tailoring Error: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(err => {
            aiSpinner.classList.add('d-none');
            alert('Request failed: ' + err.message);
        });
    });

    applyDraftBtn.addEventListener('click', function() {
        if (generatedHtml) {
            bodyArea.value = generatedHtml;
            aiSuccess.classList.add('d-none');
        }
    });
});
</script>
@endpush
@endsection

