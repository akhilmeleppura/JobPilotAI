@extends('layouts.app')

@section('title', $vacancy->job_title . ' at ' . $vacancy->company_name)

@section('content')
<div class="container-fluid px-0">
    <!-- Back Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="{{ route('jobs.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Job Discovery
        </a>
        <div class="d-flex gap-2">
            @if($vacancy->status !== 'Staged' && $vacancy->status !== 'Applied')
                <form action="{{ route('jobs.stage', $vacancy->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>Stage for Outreach
                    </button>
                </form>
            @endif
            @if($vacancy->apply_url)
                <a href="{{ $vacancy->apply_url }}" target="_blank" class="btn btn-sm btn-outline-dark">
                    <i class="bi bi-box-arrow-up-right me-1"></i>External Apply Page
                </a>
            @endif
        </div>
    </div>

    <div class="row g-4">
        <!-- Main Job Details -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h3 class="fw-bold text-dark mb-1">{{ $vacancy->job_title }}</h3>
                            <h5 class="text-secondary mb-0"><i class="bi bi-building me-1"></i>{{ $vacancy->company_name }}</h5>
                        </div>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill">
                            {{ $vacancy->source_provider }}
                        </span>
                    </div>

                    <hr class="my-3">

                    <div class="row g-3 mb-4">
                        <div class="col-sm-4">
                            <small class="text-muted d-block">Work Mode & Location</small>
                            <span class="fw-semibold text-dark">{{ $vacancy->work_mode }} &bull; {{ $vacancy->location }}</span>
                        </div>
                        <div class="col-sm-4">
                            <small class="text-muted d-block">Employment Type</small>
                            <span class="fw-semibold text-dark">{{ $vacancy->employment_type }}</span>
                        </div>
                        <div class="col-sm-4">
                            <small class="text-muted d-block">Compensation</small>
                            <span class="fw-semibold text-dark">
                                @if($vacancy->salary_min && $vacancy->salary_max)
                                    {{ $vacancy->currency }} {{ number_format($vacancy->salary_min) }} - {{ number_format($vacancy->salary_max) }}
                                @elseif($vacancy->salary_min)
                                    {{ $vacancy->currency }} {{ number_format($vacancy->salary_min) }}+
                                @else
                                    Market Competitive
                                @endif
                            </span>
                        </div>
                    </div>

                    <!-- Skills -->
                    @if(!empty($vacancy->skills))
                        <div class="mb-4">
                            <h6 class="fw-bold text-dark mb-2">Target Skills & Technologies</h6>
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($vacancy->skills as $skill)
                                    <span class="badge bg-light text-dark border px-2 py-1">{{ $skill }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Description -->
                    <div>
                        <h6 class="fw-bold text-dark mb-2">Role Overview & Description</h6>
                        <div class="p-3 bg-light rounded small" style="line-height: 1.7; white-space: pre-wrap;">
{{ $vacancy->description ?: 'No detailed description provided by the job source.' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar: AI CV Match Resolver Recommendation -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 border-top border-primary border-4 mb-4" style="border-radius: 12px;">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-cpu-fill text-primary fs-3"></i>
                        <div>
                            <h6 class="fw-bold text-dark mb-0">AI CV Match Resolution</h6>
                            <small class="text-muted">Modes 1–4 Resolver Engine</small>
                        </div>
                    </div>

                    <div class="p-3 rounded bg-light mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small fw-bold text-dark">Recommended Resume</span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">{{ $cvMatch['match_score'] }}% Match</span>
                        </div>
                        <div class="fw-bold text-primary">{{ $cvMatch['resume']?->title }}</div>
                        <div class="small text-muted mt-1">
                            Resolution Tier: <span class="badge bg-secondary-subtle text-secondary">{{ $cvMatch['mode'] }}</span>
                        </div>
                    </div>

                    <p class="small text-secondary mb-3">
                        <strong>Reasoning:</strong> {{ $cvMatch['reasoning'] }}
                    </p>

                    @if($vacancy->status !== 'Staged' && $vacancy->status !== 'Applied')
                        <form action="{{ route('jobs.stage', $vacancy->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="resume_id" value="{{ $cvMatch['resume']?->id }}">
                            <button type="submit" class="btn btn-primary w-100 btn-sm">
                                <i class="bi bi-check2-circle me-1"></i>Stage With Recommended CV
                            </button>
                        </form>
                    @else
                        <div class="alert alert-info py-2 px-3 small mb-0">
                            <i class="bi bi-info-circle me-1"></i>This opportunity has already been staged for outreach.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

