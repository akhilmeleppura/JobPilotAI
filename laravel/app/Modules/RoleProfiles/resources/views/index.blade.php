@extends('layouts.app')

@section('title', 'Role Profiles Manager')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold text-dark mb-1">
                <i class="bi bi-person-badge-fill text-primary me-2"></i>Career Role Profiles
            </h3>
            <p class="text-secondary small mb-0">
                Manage your specialized developer personas (PHP Backend, WordPress, Full Stack, Freelance). Vacancies and AI CVs are automatically tailored against your active profiles.
            </p>
        </div>
        <div>
            <a href="{{ route('roleprofiles.create') }}" class="btn btn-primary d-inline-flex align-items-center gap-2">
                <i class="bi bi-plus-lg"></i>
                <span>Add Role Profile</span>
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

    @if($errors->has('error'))
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
            <div>{{ $errors->first('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        @forelse($profiles as $profile)
            <div class="col-xl-6 col-12">
                <div class="card h-100 shadow-sm border-0 position-relative {{ $profile->is_default ? 'border-primary border-2' : '' }}" style="border-radius: 14px;">
                    <div class="card-body p-4 d-flex flex-column">
                        <!-- Card Header -->
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                    <h5 class="fw-bold mb-0 text-dark">{{ $profile->name }}</h5>
                                    @if($profile->is_default)
                                        <span class="badge bg-primary px-2 py-1 rounded-pill">
                                            <i class="bi bi-star-fill me-1"></i>Primary Default
                                        </span>
                                    @endif
                                    @if($profile->is_active)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Active</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill">Paused</span>
                                    @endif
                                </div>
                                <span class="text-muted small">
                                    <i class="bi bi-clock-history me-1"></i>{{ $profile->experience_years }} yrs experience &bull;
                                    <i class="bi bi-geo-alt me-1"></i>{{ implode(', ', $profile->work_modes ?? ['Remote']) }}
                                </span>
                            </div>

                            <div class="dropdown">
                                <button class="btn btn-sm btn-light border" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                    @if(!$profile->is_default)
                                        <li>
                                            <form action="{{ route('roleprofiles.set-default', $profile->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="dropdown-item">
                                                    <i class="bi bi-star me-2 text-warning"></i>Set as Primary Default
                                                </button>
                                            </form>
                                        </li>
                                    @endif
                                    <li>
                                        <a class="dropdown-item" href="{{ route('roleprofiles.edit', $profile->id) }}">
                                            <i class="bi bi-pencil-square me-2 text-primary"></i>Edit Profile
                                        </a>
                                    </li>
                                    <li>
                                        <form action="{{ route('roleprofiles.toggle-active', $profile->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="dropdown-item">
                                                <i class="bi bi-power me-2 {{ $profile->is_active ? 'text-secondary' : 'text-success' }}"></i>
                                                {{ $profile->is_active ? 'Pause Matching' : 'Activate Matching' }}
                                            </button>
                                        </form>
                                    </li>
                                    @if(!$profile->is_default)
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ route('roleprofiles.destroy', $profile->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this role profile?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="bi bi-trash3 me-2"></i>Delete Profile
                                                </button>
                                            </form>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </div>

                        <!-- Summary -->
                        @if($profile->summary)
                            <p class="text-secondary small mb-3 flex-grow-0" style="line-height: 1.5;">
                                {{ Str::limit($profile->summary, 180) }}
                            </p>
                        @endif

                        <!-- Target Titles -->
                        <div class="mb-3">
                            <label class="text-uppercase fw-bold text-muted small" style="font-size: 0.7rem; letter-spacing: 0.05em;">Target Job Titles</label>
                            <div class="d-flex flex-wrap gap-1 mt-1">
                                @forelse($profile->target_titles ?? [] as $title)
                                    <span class="badge bg-light text-dark border fw-normal">{{ $title }}</span>
                                @empty
                                    <span class="text-muted small">No target titles specified</span>
                                @endforelse
                            </div>
                        </div>

                        <!-- Technical Skills -->
                        <div class="mb-3">
                            <label class="text-uppercase fw-bold text-muted small" style="font-size: 0.7rem; letter-spacing: 0.05em;">Primary Tech Stack</label>
                            <div class="d-flex flex-wrap gap-1 mt-1">
                                @forelse($profile->technical_skills ?? [] as $skill)
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-normal">{{ $skill }}</span>
                                @empty
                                    <span class="text-muted small">No skills listed</span>
                                @endforelse
                            </div>
                        </div>

                        <!-- Secondary / Supporting Skills -->
                        @if(!empty($profile->secondary_skills))
                            <div class="mb-3">
                                <label class="text-uppercase fw-bold text-muted small" style="font-size: 0.7rem; letter-spacing: 0.05em;">Secondary & Supporting Tools</label>
                                <div class="d-flex flex-wrap gap-1 mt-1">
                                    @foreach($profile->secondary_skills as $secondary)
                                        <span class="badge bg-secondary-subtle text-dark border border-secondary-subtle fw-normal">{{ $secondary }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Footer Details -->
                        <div class="mt-auto pt-3 border-top d-flex flex-wrap justify-content-between align-items-center gap-2 small text-secondary">
                            <div>
                                <i class="bi bi-cash-stack me-1"></i>
                                @if($profile->salary_min || $profile->salary_max)
                                    <strong>{{ $profile->currency }} {{ number_format($profile->salary_min) }} - {{ number_format($profile->salary_max) }}</strong>
                                @else
                                    <span class="text-muted">Salary Negotiable</span>
                                @endif
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('roleprofiles.edit', $profile->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil-square me-1"></i>Edit
                                </a>
                                @if(!$profile->is_default)
                                    <form action="{{ route('roleprofiles.set-default', $profile->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                                            Make Default
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center py-5">
                <i class="bi bi-person-x text-muted" style="font-size: 3rem;"></i>
                <h5 class="fw-bold mt-2">No Role Profiles Found</h5>
                <p class="text-secondary small">Click "Add Role Profile" to create your first specialized career role.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection

