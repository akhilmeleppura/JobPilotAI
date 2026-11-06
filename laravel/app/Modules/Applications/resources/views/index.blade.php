@extends('layouts.app')

@section('title', 'Permanent Application Registry')

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold text-dark mb-1">
                <i class="bi bi-journal-check text-primary me-2"></i>Permanent Application Registry
            </h3>
            <p class="text-secondary small mb-0">
                SHA-256 canonical deduplication index preventing duplicate job applications across all discovery channels.
            </p>
        </div>
        <div>
            <a href="{{ route('applications.create') }}" class="btn btn-primary d-inline-flex align-items-center gap-2">
                <i class="bi bi-plus-lg"></i>
                <span>Stage New Application</span>
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

    <!-- Metrics Row -->
    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-md-4 col-6">
            <div class="stat-card">
                <div class="text-muted small fw-semibold">TOTAL REGISTRY</div>
                <div class="fs-4 fw-bold text-dark mt-1">{{ number_format($metrics['total']) }}</div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="stat-card">
                <div class="text-warning small fw-semibold">STAGED / READY</div>
                <div class="fs-4 fw-bold text-dark mt-1">{{ number_format($metrics['staged']) }}</div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="stat-card">
                <div class="text-info small fw-semibold">APPLICATIONS SENT</div>
                <div class="fs-4 fw-bold text-dark mt-1">{{ number_format($metrics['sent']) }}</div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="stat-card">
                <div class="text-primary small fw-semibold">INTERVIEWS</div>
                <div class="fs-4 fw-bold text-dark mt-1">{{ number_format($metrics['interviews']) }}</div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="stat-card">
                <div class="text-success small fw-semibold">OFFERS</div>
                <div class="fs-4 fw-bold text-dark mt-1">{{ number_format($metrics['offers']) }}</div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="stat-card">
                <div class="text-danger small fw-semibold">REJECTED / CLOSED</div>
                <div class="fs-4 fw-bold text-dark mt-1">{{ number_format($metrics['rejected']) }}</div>
            </div>
        </div>
    </div>

    <!-- Filters and Table Card -->
    <div class="card shadow-sm border-0" style="border-radius: 14px;">
        <div class="card-header bg-white border-0 pt-3 px-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <!-- Status Tabs -->
                <ul class="nav nav-pills gap-1">
                    <li class="nav-item">
                        <a class="nav-link btn-sm {{ !$status || $status === 'all' ? 'active' : '' }}" href="{{ route('applications.index', ['status' => 'all']) }}">All</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn-sm {{ $status === 'Staged' ? 'active' : '' }}" href="{{ route('applications.index', ['status' => 'Staged']) }}">Staged</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn-sm {{ $status === 'Sent' ? 'active' : '' }}" href="{{ route('applications.index', ['status' => 'Sent']) }}">Sent</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn-sm {{ $status === 'Interview' ? 'active' : '' }}" href="{{ route('applications.index', ['status' => 'Interview']) }}">Interview</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn-sm {{ $status === 'Offer' ? 'active' : '' }}" href="{{ route('applications.index', ['status' => 'Offer']) }}">Offer</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn-sm {{ $status === 'Rejected' ? 'active' : '' }}" href="{{ route('applications.index', ['status' => 'Rejected']) }}">Rejected</a>
                    </li>
                </ul>

                <!-- Search -->
                <form action="{{ route('applications.index') }}" method="GET" class="d-flex align-items-center gap-2">
                    @if($status)
                        <input type="hidden" name="status" value="{{ $status }}">
                    @endif
                    <div class="input-group input-group-sm">
                        <input type="text" name="q" class="form-control" placeholder="Search company or title..." value="{{ $search }}">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted small text-uppercase" style="font-size: 0.75rem;">
                        <tr>
                            <th class="ps-4">Company & Position</th>
                            <th>Matched Role Profile</th>
                            <th>Channel & Source</th>
                            <th>Canonical Fingerprint</th>
                            <th>Status</th>
                            <th>Date Tracked</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($applications as $app)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark">{{ $app->vacancy?->company_name ?? 'Confidential Company' }}</div>
                                    <div class="text-secondary small">{{ $app->vacancy?->job_title ?? 'Software Engineer' }}</div>
                                    <div class="text-muted" style="font-size: 0.75rem;">
                                        <i class="bi bi-geo-alt me-1"></i>{{ $app->vacancy?->location ?? 'Remote' }} &bull; {{ $app->vacancy?->work_mode ?? 'Remote' }}
                                    </div>
                                </td>
                                <td>
                                    @if($app->roleProfile)
                                        <span class="badge bg-light text-dark border">{{ $app->roleProfile->name }}</span>
                                    @else
                                        <span class="text-muted small">General Application</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-dark border border-secondary-subtle">
                                        {{ $app->applied_via }}
                                    </span>
                                    <span class="text-muted small d-block mt-1" style="font-size: 0.7rem;">via {{ $app->discovered_from }}</span>
                                </td>
                                <td>
                                    <code class="text-primary small" title="{{ $app->registry?->canonical_fingerprint }}">
                                        {{ substr($app->registry?->canonical_fingerprint ?? 'N/A', 0, 10) }}...
                                    </code>
                                </td>
                                <td>
                                    @php
                                        $statusClass = match($app->application_status) {
                                            'Staged' => 'bg-warning-subtle text-warning border-warning-subtle',
                                            'Sent' => 'bg-info-subtle text-info border-info-subtle',
                                            'Replied' => 'bg-primary-subtle text-primary border-primary-subtle',
                                            'Interview' => 'bg-success-subtle text-success border-success-subtle',
                                            'Offer' => 'bg-success text-white',
                                            'Rejected' => 'bg-danger-subtle text-danger border-danger-subtle',
                                            default => 'bg-light text-secondary border'
                                        };
                                    @endphp
                                    <span class="badge border rounded-pill {{ $statusClass }}">
                                        {{ $app->application_status }}
                                    </span>
                                </td>
                                <td class="small text-secondary">
                                    {{ $app->created_at->format('M d, Y') }}
                                    <span class="text-muted d-block" style="font-size: 0.7rem;">{{ $app->created_at->diffForHumans() }}</span>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('applications.show', $app->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-arrow-right-circle me-1"></i>Inspect
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-journal-x" style="font-size: 2.5rem;"></i>
                                    <p class="mt-2 mb-0">No job applications recorded in this view.</p>
                                    <a href="{{ route('applications.create') }}" class="btn btn-sm btn-outline-primary mt-2">Stage First Vacancy</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($applications->hasPages())
            <div class="card-footer bg-white border-0 py-3 px-4">
                {{ $applications->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

