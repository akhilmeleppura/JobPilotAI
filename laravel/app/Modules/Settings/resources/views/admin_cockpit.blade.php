@extends('layouts.app')

@section('title', 'Admin Central Cockpit & System Governance')

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-danger px-2 py-1 text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">Superadmin Console</span>
                <span class="badge bg-primary-subtle text-primary border" style="font-size: 0.7rem;">TiDB Cloud Serverless</span>
            </div>
            <h3 class="fw-bold text-dark mt-2 mb-1">
                <i class="bi bi-shield-shaded text-danger me-2"></i>Advanced Admin Control & Governance
            </h3>
            <p class="text-secondary small mb-0">
                System-wide oversight across candidate tenants, outbound dispatch safety, kill switch circuit breakers, and database operations.
            </p>
        </div>

        <!-- Global Action Controls -->
        <div class="d-flex align-items-center gap-2">
            <form action="{{ route('admin.cache.flush') }}" method="POST" class="m-0">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
                    <i class="bi bi-arrow-repeat"></i>
                    <span>Flush Cache</span>
                </button>
            </form>
            <form action="{{ route('admin.maintenance.run') }}" method="POST" class="m-0">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
                    <i class="bi bi-database-check"></i>
                    <span>Verify Schema</span>
                </button>
            </form>
            <a href="{{ route('settings.index') }}" class="btn btn-sm btn-secondary d-inline-flex align-items-center gap-1">
                <i class="bi bi-sliders"></i>
                <span>Integration Vault</span>
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-check-circle-fill fs-5"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Emergency Kill Switch Circuit Breaker Card -->
    <div class="card border-0 shadow-sm mb-4 {{ $metrics['emergency_stop_status']['is_engaged'] ? 'border-danger border-start border-4' : 'border-success border-start border-4' }}" style="border-radius: 14px;">
        <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2">
                    <div class="stat-icon {{ $metrics['emergency_stop_status']['is_engaged'] ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' }}" style="width: 44px; height: 44px;">
                        <i class="bi {{ $metrics['emergency_stop_status']['is_engaged'] ? 'bi-slash-circle-fill' : 'bi-shield-check' }} fs-4"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">
                            Global Emergency Kill Switch Circuit Breaker: 
                            <span class="badge {{ $metrics['emergency_stop_status']['is_engaged'] ? 'bg-danger' : 'bg-success' }}">
                                {{ $metrics['emergency_stop_status']['is_engaged'] ? 'HALTED / ENGAGED' : 'OPERATIONAL / NOMINAL' }}
                            </span>
                        </h6>
                        <div class="small text-muted mt-1">
                            Status: {{ $metrics['emergency_stop_status']['reason'] }}
                            @if($metrics['emergency_stop_status']['engaged_at'])
                                &bull; Triggered at: {{ $metrics['emergency_stop_status']['engaged_at'] }}
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <form action="{{ route('admin.emergency-stop.toggle') }}" method="POST" class="m-0">
                @csrf
                <button type="submit" class="btn {{ $metrics['emergency_stop_status']['is_engaged'] ? 'btn-success' : 'btn-danger' }} px-4 fw-bold shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="bi {{ $metrics['emergency_stop_status']['is_engaged'] ? 'bi-play-circle-fill' : 'bi-exclamation-octagon-fill' }}"></i>
                    <span>{{ $metrics['emergency_stop_status']['is_engaged'] ? 'Resume All Operations' : 'ENGAGE EMERGENCY STOP' }}</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Metrics Cards Grid -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="stat-card">
                <div class="text-muted small fw-semibold">REGISTERED USERS</div>
                <div class="fs-4 fw-bold text-dark mt-1">{{ number_format($metrics['total_users']) }} Users</div>
                <div class="text-muted small mt-2" style="font-size: 0.75rem;">Multi-tenant accounts in TiDB</div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="stat-card">
                <div class="text-primary small fw-semibold">TOTAL APPLICATIONS</div>
                <div class="fs-4 fw-bold text-primary mt-1">{{ number_format($metrics['total_applications']) }} Active</div>
                <div class="text-muted small mt-2" style="font-size: 0.75rem;">Tracked career submissions</div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="stat-card">
                <div class="text-success small fw-semibold">OUTBOUND EMAILS SENT</div>
                <div class="fs-4 fw-bold text-success mt-1">{{ number_format($metrics['total_emails_sent']) }} Verified</div>
                <div class="text-muted small mt-2" style="font-size: 0.75rem;">Across all candidate mailboxes</div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="stat-card">
                <div class="text-info small fw-semibold">OUTLOOK HUB RECORDS</div>
                <div class="fs-4 fw-bold text-info mt-1">{{ number_format($metrics['outlook_messages_count']) }} Messages</div>
                <div class="text-muted small mt-2" style="font-size: 0.75rem;">Stored recruiter threads</div>
            </div>
        </div>
    </div>

    <!-- Management Data Tables Row -->
    <div class="row g-4">
        <!-- User Accounts & Tenants -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 14px;">
                <div class="card-header bg-white border-0 pt-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-people-fill text-primary me-2"></i>User & Tenant Accounts
                    </h6>
                    <span class="badge bg-secondary-subtle text-dark border">{{ $users->count() }} accounts</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">User</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th class="text-end pe-4">Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $u)
                                    <tr>
                                        <td class="ps-4 fw-bold text-dark">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="stat-icon bg-primary-subtle text-primary" style="width: 28px; height: 28px; font-size: 0.75rem;">
                                                    {{ strtoupper(substr($u->name, 0, 1)) }}
                                                </div>
                                                <span>{{ $u->name }}</span>
                                            </div>
                                        </td>
                                        <td class="text-secondary font-monospace">{{ $u->email }}</td>
                                        <td>
                                            @if($u->isAdmin())
                                                <span class="badge bg-danger-subtle text-danger border border-danger">Admin</span>
                                            @else
                                                <span class="badge bg-light text-dark border">Candidate</span>
                                            @endif
                                        </td>
                                        <td class="text-end pe-4 text-muted">{{ $u->created_at->diffForHumans() }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Outreach Audits -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 14px;">
                <div class="card-header bg-white border-0 pt-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-envelope-paper-heart-fill text-success me-2"></i>Recent Outbound Dispatches
                    </h6>
                    <a href="{{ route('email.index') }}" class="btn btn-sm btn-link text-decoration-none">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Recipient</th>
                                    <th>Subject</th>
                                    <th>Status</th>
                                    <th class="text-end pe-4">Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentDispatches as $log)
                                    <tr>
                                        <td class="ps-4 font-monospace fw-semibold text-dark">
                                            {{ implode(', ', $log->to_recipients) }}
                                        </td>
                                        <td class="text-truncate" style="max-width: 180px;">{{ $log->subject }}</td>
                                        <td>
                                            <span class="badge {{ $log->status === 'Sent' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }} border">
                                                {{ $log->status }}
                                            </span>
                                        </td>
                                        <td class="text-end pe-4 text-muted">{{ $log->created_at->diffForHumans() }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">No outbound dispatches logged yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
