@extends('layouts.app')

@section('title', 'Job Discovery & Multi-Source Ingestion')

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold text-dark mb-1">
                <i class="bi bi-briefcase-fill text-primary me-2"></i>Job Discovery & Ingestion Engine
            </h3>
            <p class="text-secondary small mb-0">
                Multi-source aggregator: <strong>Himalayas</strong> &bull; <strong>Remote OK</strong> &bull; <strong>Remotive</strong> &bull; <strong>Adzuna</strong> &bull; <strong>Jooble</strong> &bull; <strong>ATS Boards</strong> (Greenhouse, Lever, Ashby).
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">
                <i class="bi bi-patch-check-fill me-1"></i>100% Free Public APIs
            </span>
            <form action="{{ route('jobs.fetch') }}" method="POST" class="d-flex align-items-center gap-2">
                @csrf
                <input type="text" name="query" class="form-control form-control-sm" placeholder="Search tech/role..." value="{{ request('query', 'PHP Laravel') }}" style="width: 160px;">
                <button type="submit" class="btn btn-sm btn-primary d-flex align-items-center gap-1 shadow-sm">
                    <i class="bi bi-cloud-arrow-down-fill"></i>
                    <span>Fetch Openings</span>
                </button>
            </form>
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
        <div class="col-xl-3 col-sm-6">
            <div class="stat-card">
                <div class="text-muted small fw-semibold">TOTAL DISCOVERED JOBS</div>
                <div class="fs-4 fw-bold text-dark mt-1">{{ number_format($metrics['total_discovered']) }} Positions</div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="stat-card">
                <div class="text-primary small fw-semibold">REMOTE OPENINGS</div>
                <div class="fs-4 fw-bold text-dark mt-1">{{ number_format($metrics['remote_count']) }} Roles</div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="stat-card">
                <div class="text-success small fw-semibold">STAGED FOR OUTREACH</div>
                <div class="fs-4 fw-bold text-dark mt-1">{{ number_format($metrics['staged_count']) }} Staged</div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="stat-card">
                <div class="text-info small fw-semibold">ACTIVE FREE ADAPTERS</div>
                <div class="fs-4 fw-bold text-dark mt-1">{{ $metrics['providers_count'] }} Connected Sources</div>
            </div>
        </div>
    </div>

    <!-- Filter Bar Card -->
    <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
        <div class="card-body p-3">
            <form action="{{ route('jobs.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="query" class="form-control border-start-0" placeholder="Filter by title, company, or keyword..." value="{{ request('query') }}">
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <select name="provider" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all">All Sources</option>
                        <option value="Himalayas" {{ request('provider') === 'Himalayas' ? 'selected' : '' }}>Himalayas</option>
                        <option value="Remote OK" {{ request('provider') === 'Remote OK' ? 'selected' : '' }}>Remote OK</option>
                        <option value="Remotive" {{ request('provider') === 'Remotive' ? 'selected' : '' }}>Remotive</option>
                        <option value="Adzuna" {{ request('provider') === 'Adzuna' ? 'selected' : '' }}>Adzuna</option>
                        <option value="Jooble" {{ request('provider') === 'Jooble' ? 'selected' : '' }}>Jooble</option>
                        <option value="Greenhouse" {{ request('provider') === 'Greenhouse' ? 'selected' : '' }}>Greenhouse ATS</option>
                    </select>
                </div>
                <div class="col-md-2 col-6">
                    <select name="work_mode" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all">All Work Modes</option>
                        <option value="Remote" {{ request('work_mode') === 'Remote' ? 'selected' : '' }}>Remote Only</option>
                        <option value="Hybrid" {{ request('work_mode') === 'Hybrid' ? 'selected' : '' }}>Hybrid</option>
                        <option value="Onsite" {{ request('work_mode') === 'Onsite' ? 'selected' : '' }}>Onsite</option>
                    </select>
                </div>
                <div class="col-md-2 col-6">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all">All Statuses</option>
                        <option value="New" {{ request('status') === 'New' ? 'selected' : '' }}>New Discovered</option>
                        <option value="Staged" {{ request('status') === 'Staged' ? 'selected' : '' }}>Staged</option>
                        <option value="Applied" {{ request('status') === 'Applied' ? 'selected' : '' }}>Applied</option>
                    </select>
                </div>
                <div class="col-md-2 col-6 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-outline-secondary w-100">Filter</button>
                    @if(request()->hasAny(['query', 'provider', 'work_mode', 'status']))
                        <a href="{{ route('jobs.index') }}" class="btn btn-sm btn-light border text-muted" title="Reset Filters"><i class="bi bi-x-circle"></i></a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Vacancy Listings Table / Grid -->
    <div class="card shadow-sm border-0" style="border-radius: 14px;">
        <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
            <span class="fw-bold text-dark">Aggregated Opportunities ({{ $vacancies->total() }})</span>
            <span class="small text-muted">Showing {{ $vacancies->firstItem() ?? 0 }} to {{ $vacancies->lastItem() ?? 0 }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-4">Role & Company</th>
                            <th>Source Provider</th>
                            <th>Work Mode & Location</th>
                            <th>Compensation</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vacancies as $job)
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="fw-bold text-dark">{{ $job->job_title }}</div>
                                    <div class="small text-muted d-flex align-items-center gap-1">
                                        <i class="bi bi-building"></i>{{ $job->company_name }}
                                        @if(!empty($job->skills))
                                            <span class="ms-2">
                                                @foreach(array_slice($job->skills, 0, 3) as $skill)
                                                    <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.7rem;">{{ $skill }}</span>
                                                @endforeach
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <i class="bi bi-broadcast me-1 text-primary"></i>{{ $job->source_provider }}
                                    </span>
                                </td>
                                <td>
                                    <div>
                                        <span class="badge {{ $job->work_mode === 'Remote' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }} rounded-pill me-1">
                                            {{ $job->work_mode }}
                                        </span>
                                        <span class="small text-muted">{{ Str::limit($job->location, 25) }}</span>
                                    </div>
                                </td>
                                <td>
                                    @if($job->salary_min && $job->salary_max)
                                        <span class="small fw-semibold text-dark">
                                            {{ $job->currency }} {{ number_format($job->salary_min) }} - {{ number_format($job->salary_max) }}
                                        </span>
                                    @elseif($job->salary_min)
                                        <span class="small fw-semibold text-dark">
                                            {{ $job->currency }} {{ number_format($job->salary_min) }}+
                                        </span>
                                    @else
                                        <span class="small text-muted">Competitive</span>
                                    @endif
                                </td>
                                <td>
                                    @if($job->status === 'Staged')
                                        <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill">
                                            <i class="bi bi-clock-history me-1"></i>Staged
                                        </span>
                                    @elseif($job->status === 'Applied')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">
                                            <i class="bi bi-check2-circle me-1"></i>Applied
                                        </span>
                                    @else
                                        <span class="badge bg-light text-secondary border rounded-pill">New</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end align-items-center gap-1">
                                        <a href="{{ route('jobs.show', $job->id) }}" class="btn btn-xs btn-outline-secondary py-1 px-2" title="Inspect Opportunity">
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        @if($job->status !== 'Staged' && $job->status !== 'Applied')
                                            <button type="button" class="btn btn-xs btn-primary py-1 px-2 d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#stageModal{{ $job->id }}">
                                                <i class="bi bi-plus-circle"></i>Stage
                                            </button>
                                        @else
                                            <a href="{{ route('applications.index') }}" class="btn btn-xs btn-outline-info py-1 px-2">
                                                View App
                                            </a>
                                        @endif

                                        @if($job->apply_url)
                                            <a href="{{ $job->apply_url }}" target="_blank" class="btn btn-xs btn-outline-dark py-1 px-2" title="External Apply Link">
                                                <i class="bi bi-box-arrow-up-right"></i>
                                            </a>
                                        @endif
                                    </div>

                                    <!-- Quick Stage Modal -->
                                    <div class="modal fade text-start" id="stageModal{{ $job->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <form action="{{ route('jobs.stage', $job->id) }}" method="POST" class="modal-content">
                                                @csrf
                                                <div class="modal-header">
                                                    <h6 class="modal-title fw-bold">Stage Application: {{ $job->job_title }}</h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p class="small text-secondary mb-3">
                                                        Staging creates an application entry in your registry, assigns your optimal CV via the Mode 1–4 resolver, and prepares a personalized outreach email draft.
                                                    </p>
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-bold">Target Resume / CV Override</label>
                                                        <select name="resume_id" class="form-select form-select-sm">
                                                            <option value="">Auto-Resolve (Mode 1-4 AI Recommendation)</option>
                                                            @foreach($resumes as $res)
                                                                <option value="{{ $res->id }}">
                                                                    {{ $res->title }} {{ $res->is_global_default ? '(Default)' : '' }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-bold">Outreach Email Template</label>
                                                        <select name="template_id" class="form-select form-select-sm">
                                                            <option value="">Default Cold Outreach Template</option>
                                                            @foreach($templates as $tmpl)
                                                                <option value="{{ $tmpl->id }}">{{ $tmpl->name }} ({{ $tmpl->category }})</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary btn-sm">Confirm Staging</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-search fs-2 d-block mb-2 text-secondary"></i>
                                    No job vacancies discovered yet matching your criteria.
                                    <div class="mt-2">
                                        <form action="{{ route('jobs.fetch') }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="query" value="PHP Laravel">
                                            <button type="submit" class="btn btn-sm btn-primary">
                                                <i class="bi bi-cloud-arrow-down-fill me-1"></i>Fetch Live Opportunities
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($vacancies->hasPages())
            <div class="card-footer bg-white py-2 px-4 border-top">
                {{ $vacancies->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

