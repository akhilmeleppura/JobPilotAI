@extends('layouts.app')

@section('title', 'Email Outreach Templates Studio')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <a href="{{ route('email.index') }}" class="btn btn-sm btn-outline-secondary mb-2">
                <i class="bi bi-arrow-left me-1"></i>Back to Outreach Studio
            </a>
            <h3 class="fw-bold text-dark mb-1">
                <i class="bi bi-file-earmark-richtext-fill text-primary me-2"></i>Communication Templates Studio
            </h3>
            <p class="text-secondary small mb-0">
                8 standardized categories with placeholder variables (<code>@{{company}}</code>, <code>@{{job_title}}</code>, <code>@{{tech_stack}}</code>) and mini AI assistant for rapid tailoring.
            </p>
        </div>
        <div>
            <a href="{{ route('email.templates.create') }}" class="btn btn-primary d-inline-flex align-items-center gap-2">
                <i class="bi bi-plus-lg"></i>
                <span>Create Custom Template</span>
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-check-circle-fill fs-5"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @foreach($categories as $catKey => $catLabel)
        @if(isset($grouped[$catKey]) && $grouped[$catKey]->isNotEmpty())
            <div class="mb-5">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="badge bg-primary px-2 py-1 rounded-pill" style="font-size: 0.75rem;">{{ $catKey }}</span>
                    <h5 class="fw-bold text-dark mb-0">{{ $catLabel }}</h5>
                </div>

                <div class="row g-3">
                    @foreach($grouped[$catKey] as $tpl)
                        <div class="col-xl-6 col-12">
                            <div class="card h-100 shadow-sm border-0 position-relative {{ $tpl->is_default ? 'border-primary border-2' : '' }}" style="border-radius: 14px;">
                                <div class="card-body p-4 d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="fw-bold text-dark mb-1">{{ $tpl->name }}</h6>
                                            <span class="text-muted small font-monospace" style="font-size: 0.75rem;">{{ $tpl->slug }}</span>
                                        </div>
                                        @if($tpl->is_default)
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">
                                                Default
                                            </span>
                                        @endif
                                    </div>

                                    <div class="p-2 bg-light rounded small mb-3 border">
                                        <div class="text-muted fw-bold" style="font-size: 0.7rem;">SUBJECT LINE:</div>
                                        <div class="font-monospace text-dark">{{ $tpl->subject_template }}</div>
                                    </div>

                                    <div class="text-secondary small mb-3 flex-grow-1" style="font-size: 0.8rem; line-height: 1.5; max-height: 120px; overflow-y: hidden;">
                                        {!! Str::limit(strip_tags($tpl->body_template), 250) !!}
                                    </div>

                                    <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                                        <button class="btn btn-sm btn-outline-secondary preview-btn" data-id="{{ $tpl->id }}" data-bs-toggle="modal" data-bs-target="#previewModal">
                                            <i class="bi bi-eye me-1"></i>Preview
                                        </button>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('email.templates.edit', $tpl->id) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil-square me-1"></i>Edit
                                            </a>
                                            <form action="{{ route('email.templates.destroy', $tpl->id) }}" method="POST" onsubmit="return confirm('Delete this template?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-light border text-danger">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endforeach
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 14px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Template Preview with Live Variables</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="fw-semibold small text-muted">Interpolated Subject:</label>
                    <div id="modalSubject" class="p-2 bg-light rounded border fw-bold"></div>
                </div>
                <div>
                    <label class="fw-semibold small text-muted">Interpolated Body:</label>
                    <div id="modalBody" class="p-3 bg-light rounded border" style="min-height: 180px;"></div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const previewBtns = document.querySelectorAll('.preview-btn');
    const modalSubject = document.getElementById('modalSubject');
    const modalBody = document.getElementById('modalBody');

    previewBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            modalSubject.textContent = 'Loading...';
            modalBody.innerHTML = '<em>Rendering preview...</em>';

            fetch(`/email-studio/templates/${id}/preview`)
                .then(res => res.json())
                .then(data => {
                    modalSubject.textContent = data.subject;
                    modalBody.innerHTML = data.body;
                })
                .catch(err => {
                    modalSubject.textContent = 'Error loading preview';
                    modalBody.textContent = err.message;
                });
        });
    });
});
</script>
@endpush
@endsection

