@extends('layouts.app')

@section('title', ($application->vacancy?->job_title ?? 'Application') . ' — Permanent Registry')

@section('content')
<div class="container-fluid px-0">
    <div class="mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <a href="{{ route('applications.index') }}" class="btn btn-sm btn-outline-secondary mb-2">
                <i class="bi bi-arrow-left me-1"></i>Back to Application Registry
            </a>
            <h3 class="fw-bold text-dark mb-1">
                {{ $application->vacancy?->job_title ?? 'Software Engineer' }}
            </h3>
            <p class="text-secondary small mb-0">
                <i class="bi bi-building me-1 text-primary"></i><strong>{{ $application->vacancy?->company_name }}</strong> &bull;
                <i class="bi bi-geo-alt me-1"></i>{{ $application->vacancy?->location ?? 'Remote' }} &bull;
                <i class="bi bi-laptop me-1"></i>{{ $application->vacancy?->work_mode ?? 'Remote' }}
            </p>
        </div>

        <div class="d-flex align-items-center gap-2">
            <!-- Status Update Dropdown Form -->
            <form action="{{ route('applications.update-status', $application->id) }}" method="POST" class="d-flex gap-2 align-items-center">
                @csrf
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach(['Draft', 'Staged', 'Sent', 'Replied', 'Interview', 'Offer', 'Rejected'] as $opt)
                        <option value="{{ $opt }}" {{ $application->application_status === $opt ? 'selected' : '' }}>
                            Status: {{ $opt }}
                        </option>
                    @endforeach
                </select>
            </form>
            @if($application->vacancy?->apply_url)
                <a href="{{ $application->vacancy->apply_url }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-box-arrow-up-right me-1"></i>Open Vacancy URL
                </a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-check-circle-fill fs-5"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- Main Column: Details & Audit Trail -->
        <div class="col-lg-8">
            <!-- Vacancy Overview -->
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 14px;">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-dark mb-3">Vacancy Specifications</h5>
                    <div class="row g-3 small">
                        <div class="col-sm-6">
                            <span class="text-muted d-block">Matched Career Persona:</span>
                            <span class="fw-semibold text-dark">{{ $application->roleProfile?->name ?? 'None Assigned' }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block">Application Submission Channel:</span>
                            <span class="fw-semibold text-dark">{{ $application->applied_via }} (Discovered via {{ $application->discovered_from }})</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block">Compensation Target:</span>
                            <span class="fw-semibold text-dark">
                                @if($application->vacancy?->salary_min || $application->vacancy?->salary_max)
                                    {{ $application->vacancy->currency }} {{ number_format($application->vacancy->salary_min) }} - {{ number_format($application->vacancy->salary_max) }}
                                @else
                                    Not Disclosed / Negotiable
                                @endif
                            </span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block">HR / Recruiter Contact:</span>
                            <span class="fw-semibold text-dark">
                                {{ $application->vacancy?->hr_email ?? 'Direct Application Portal' }}
                            </span>
                        </div>
                    </div>

                    @if($application->notes)
                        <div class="mt-3 pt-3 border-top">
                            <label class="fw-semibold small text-muted">Application Notes:</label>
                            <p class="small text-secondary mb-0 mt-1">{{ $application->notes }}</p>
                        </div>
                    @endif

                    @if($application->vacancy?->description)
                        <div class="mt-4 pt-3 border-top">
                            <h6 class="fw-bold text-dark mb-2">Job Description Snapshot</h6>
                            <div class="p-3 bg-light rounded text-secondary small" style="white-space: pre-wrap; max-height: 250px; overflow-y: auto;">
                                {{ $application->vacancy->description }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Audit Trail / Events Timeline -->
            <div class="card shadow-sm border-0" style="border-radius: 14px;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold text-dark mb-0">Application Lifecycle Events</h5>
                        <span class="badge bg-light text-dark border">{{ $application->events->count() }} Recorded Events</span>
                    </div>

                    <div class="timeline mt-3">
                        @forelse($application->events as $event)
                            <div class="d-flex gap-3 mb-3 position-relative">
                                <div class="timeline-icon">
                                    @php
                                        $icon = match($event->event_type) {
                                            'STAGED' => 'bi-pin-angle-fill text-warning',
                                            'EMAIL_SENT' => 'bi-send-fill text-info',
                                            'STATUS_CHANGE' => 'bi-arrow-repeat text-primary',
                                            'REPLY_DETECTED' => 'bi-chat-dots-fill text-success',
                                            default => 'bi-circle-fill text-secondary'
                                        };
                                    @endphp
                                    <i class="bi {{ $icon }} fs-5"></i>
                                </div>
                                <div class="bg-light p-3 rounded flex-grow-1 border">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-bold small text-dark">{{ $event->event_type }}</span>
                                        <span class="text-muted" style="font-size: 0.75rem;">{{ $event->created_at->format('M d, Y H:i:s') }}</span>
                                    </div>
                                    @if(!empty($event->event_payload))
                                        <div class="small text-secondary">
                                            @if(!empty($event->event_payload['previous_status']) && !empty($event->event_payload['new_status']))
                                                Moved from <strong>{{ $event->event_payload['previous_status'] }}</strong> to <strong>{{ $event->event_payload['new_status'] }}</strong>
                                            @endif
                                            @if(!empty($event->event_payload['note']))
                                                <div class="mt-1 text-muted fst-italic">"{{ $event->event_payload['note'] }}"</div>
                                            @endif
                                            @if(!empty($event->event_payload['via']))
                                                Application registered via <strong>{{ $event->event_payload['via'] }}</strong>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-muted small mb-0">No lifecycle events recorded yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Column: Permanent Deduplication Box -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-4 bg-white" style="border-radius: 14px;">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-shield-check text-success fs-4"></i>
                        <h6 class="fw-bold mb-0 text-dark">Permanent Identity Fingerprint</h6>
                    </div>
                    <p class="text-secondary small" style="font-size: 0.8rem; line-height: 1.4;">
                        This canonical SHA-256 fingerprint remains permanently stored in your database to prevent duplicate submissions across all platforms.
                    </p>

                    <div class="mb-3">
                        <label class="text-muted small fw-bold text-uppercase" style="font-size: 0.7rem;">Canonical SHA-256</label>
                        <div class="p-2 bg-light rounded text-break font-monospace small border text-primary" style="font-size: 0.75rem;">
                            {{ $application->registry?->canonical_fingerprint }}
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="text-muted small fw-bold text-uppercase" style="font-size: 0.7rem;">Indexed Identity Keys</label>
                        <div class="list-group list-group-flush border rounded mt-1">
                            @forelse($application->registry?->identityKeys ?? [] as $key)
                                <div class="list-group-item px-2 py-1 small">
                                    <span class="badge bg-secondary-subtle text-dark border me-1">{{ $key->key_type }}</span>
                                    <span class="text-break text-muted font-monospace" style="font-size: 0.7rem;">{{ Str::limit($key->key_value, 35) }}</span>
                                </div>
                            @empty
                                <div class="list-group-item p-2 small text-muted">No secondary identity keys.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="border-top pt-3 small text-secondary">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Discovered:</span>
                            <span class="fw-semibold text-dark">{{ $application->registry?->first_discovered_at?->format('M d, Y') ?? 'N/A' }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Last Applied:</span>
                            <span class="fw-semibold text-dark">{{ $application->registry?->last_applied_at?->format('M d, Y') ?? 'Pending Submission' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Status Change Note Form -->
            <div class="card shadow-sm border-0" style="border-radius: 14px;">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-2">Add Timeline Note</h6>
                    <form action="{{ route('applications.update-status', $application->id) }}" method="POST">
                        @csrf
                        <input type="hidden" name="status" value="{{ $application->application_status }}">
                        <div class="mb-3">
                            <textarea name="note" rows="3" class="form-control form-control-sm" placeholder="e.g. Received recruiter email regarding technical interview..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary w-100">Post Note to Timeline</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

