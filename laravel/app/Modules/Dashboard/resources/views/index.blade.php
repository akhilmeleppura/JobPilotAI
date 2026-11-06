@extends('layouts.app')

@section('title', 'Cockpit Dashboard')

@section('content')
<div class="container-fluid p-0">
    <!-- Top Action Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-3">
        <div>
            <h3 class="fw-bold mb-1">Career Command Center</h3>
            <p class="text-muted small mb-0">Live cockpit for job discovery, AI matching, application tracking, dynamic free email allowances, and automatic provider failover.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ url('/jobs') }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-search me-1"></i>Discover Vacancies
            </a>
            <a href="{{ url('/email-studio') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-send-fill me-1"></i>Compose Outreach
            </a>
        </div>
    </div>

    <!-- Career Statistics Row -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Discovered Jobs</span>
                    <h3 class="fw-bold my-1">{{ $stats['jobs_discovered'] }}</h3>
                    <span class="text-success small fw-medium"><i class="bi bi-arrow-up-right me-1"></i>Across 10 sources</span>
                </div>
                <div class="stat-icon bg-primary-subtle text-primary">
                    <i class="bi bi-compass-fill"></i>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="stat-card d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Role Matches</span>
                    <h3 class="fw-bold my-1 text-primary">{{ $stats['matched_jobs'] }}</h3>
                    <span class="text-muted small fw-medium">Based on 5 active roles</span>
                </div>
                <div class="stat-icon bg-info-subtle text-info">
                    <i class="bi bi-stars"></i>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="stat-card d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Applications Sent</span>
                    <h3 class="fw-bold my-1 text-success">{{ $stats['applications_sent'] }}</h3>
                    <span class="text-success small fw-medium"><i class="bi bi-check-circle me-1"></i>Permanent SHA-256 indexed</span>
                </div>
                <div class="stat-icon bg-success-subtle text-success">
                    <i class="bi bi-send-check-fill"></i>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="stat-card d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Interviews & Calls</span>
                    <h3 class="fw-bold my-1 text-warning">{{ $stats['interviews_scheduled'] }}</h3>
                    <span class="text-muted small fw-medium">{{ $stats['recruiter_responses'] }} recruiter replies</span>
                </div>
                <div class="stat-icon bg-warning-subtle text-warning">
                    <i class="bi bi-calendar-event-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Dynamic Free Email Allowance Summary Cockpit (Section 10) -->
    <div class="card border-0 shadow-sm rounded-4 mb-4" id="emailAllowanceSummary">
        <div class="card-header bg-white border-0 py-3 px-4 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-speedometer2 text-primary fs-5"></i>
                <h6 class="fw-bold mb-0 text-dark">FREE EMAIL PROVIDER ALLOWANCE SUMMARY</h6>
            </div>
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill small">
                <i class="bi bi-shield-check me-1"></i>Zero Fixed Cap &bull; Dynamic Provider Detection
            </span>
        </div>
        <div class="card-body px-4 py-2 border-top">
            <div class="row g-3 py-2">
                <div class="col-6 col-md-3 col-xl">
                    <div class="p-2 border rounded-3 bg-light">
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.68rem;">Connected Providers</div>
                        <div class="fs-5 fw-bold text-dark">{{ $emailSummary['connected_providers'] }}</div>
                        <div class="text-secondary small" style="font-size: 0.7rem;">Active Mailers</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-xl">
                    <div class="p-2 border rounded-3 bg-light">
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.68rem;">Free Providers Eligible</div>
                        <div class="fs-5 fw-bold text-success">{{ $emailSummary['free_providers_eligible'] }}</div>
                        <div class="text-success small" style="font-size: 0.7rem;">Policy Compliant</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-xl">
                    <div class="p-2 border rounded-3 bg-light">
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.68rem;">Recipients Sent Today</div>
                        <div class="fs-5 fw-bold text-primary">{{ $emailSummary['recipients_sent_today'] }}</div>
                        <div class="text-muted small" style="font-size: 0.7rem;">Across All Accounts</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-xl">
                    <div class="p-2 border rounded-3 bg-light">
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.68rem;">Recipients This Month</div>
                        <div class="fs-5 fw-bold text-dark">{{ $emailSummary['recipients_sent_this_month'] }}</div>
                        <div class="text-muted small" style="font-size: 0.7rem;">Monthly Total</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-xl">
                    <div class="p-2 border rounded-3 bg-light">
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.68rem;">Pending Emails</div>
                        <div class="fs-5 fw-bold text-warning">{{ $emailSummary['pending_emails'] }}</div>
                        <div class="text-warning small" style="font-size: 0.7rem;">In Review / Queue</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-xl">
                    <div class="p-2 border rounded-3 bg-light">
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.68rem;">Failed Emails</div>
                        <div class="fs-5 fw-bold {{ $emailSummary['failed_emails'] > 0 ? 'text-danger' : 'text-secondary' }}">{{ $emailSummary['failed_emails'] }}</div>
                        <div class="text-muted small" style="font-size: 0.7rem;">Today's Errors</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-xl">
                    <div class="p-2 border rounded-3 bg-light">
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.68rem;">Providers Paused</div>
                        <div class="fs-5 fw-bold {{ $emailSummary['providers_paused'] > 0 ? 'text-danger' : 'text-secondary' }}">{{ $emailSummary['providers_paused'] }}</div>
                        <div class="text-muted small" style="font-size: 0.7rem;">Rate Limited / Policy</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-xl">
                    <div class="p-2 border rounded-3 bg-light">
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.68rem;">Unknown Quota</div>
                        <div class="fs-5 fw-bold text-info">{{ $emailSummary['providers_with_unknown_quota'] }}</div>
                        <div class="text-muted small" style="font-size: 0.7rem;">API Without Counters</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Automatic Email Switching & Failover Monitor (Section 5) -->
    <div class="card border-0 shadow-sm rounded-4 mb-4" id="emailSwitchingMonitor">
        <div class="card-header bg-white border-0 py-3 px-4 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h5 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-arrow-left-right text-primary"></i>
                    AUTOMATIC EMAIL PROVIDER SWITCHING MONITOR
                </h5>
                <p class="text-muted small mb-0">Real-time health verification, failover dispatch, and zero-duplicate state reconciliation.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge {{ $switchingMonitor['settings']->automatic_switching ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border' }} px-3 py-1 rounded-pill small">
                    <i class="bi {{ $switchingMonitor['settings']->automatic_switching ? 'bi-check2-circle' : 'bi-pause-circle' }} me-1"></i>
                    Auto-Switch: {{ $switchingMonitor['settings']->automatic_switching ? 'ACTIVE' : 'MANUAL' }}
                </span>
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#switchingSettingsCollapse">
                    <i class="bi bi-sliders me-1"></i>Switching Controls
                </button>
            </div>
        </div>

        <!-- Dynamic Switching Notification Banner -->
        @if($switchingMonitor['notification'])
            <div class="px-4 pt-2">
                <div class="alert alert-{{ $switchingMonitor['notification']['type'] }} py-2 px-3 small d-flex align-items-center gap-2 mb-0">
                    <i class="bi bi-info-circle-fill flex-shrink-0 fs-5"></i>
                    <span><strong>{{ $switchingMonitor['notification']['message'] }}</strong></span>
                </div>
            </div>
        @endif

        <!-- User Controls Collapse (Section 6) -->
        <div class="collapse px-4 pt-3" id="switchingSettingsCollapse">
            <div class="p-3 bg-light rounded border mb-3">
                <h6 class="fw-bold text-dark mb-2"><i class="bi bi-gear-wide-connected me-1"></i>Failover & Policy Controls</h6>
                <form action="{{ route('email.update-switching-settings') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="automatic_switching" value="1" id="swCheck" {{ $switchingMonitor['settings']->automatic_switching ? 'checked' : '' }}>
                                <label class="form-check-label small fw-semibold" for="swCheck">Automatic Switching</label>
                            </div>
                            <div class="form-text" style="font-size: 0.72rem;">Automatically fail over when primary provider is unavailable.</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold mb-1">Provider Selection Mode</label>
                            <select name="provider_mode" class="form-select form-select-sm">
                                <option value="AUTOMATIC" {{ $switchingMonitor['settings']->provider_mode === 'AUTOMATIC' ? 'selected' : '' }}>AUTOMATIC (Priority-Based)</option>
                                <option value="MANUAL" {{ $switchingMonitor['settings']->provider_mode === 'MANUAL' ? 'selected' : '' }}>MANUAL (Selected Mailbox Only)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="allow_technical_failover" value="1" id="techFailCheck" {{ $switchingMonitor['settings']->allow_technical_failover ? 'checked' : '' }}>
                                <label class="form-check-label small fw-semibold" for="techFailCheck">Technical Failover</label>
                            </div>
                            <div class="form-text" style="font-size: 0.72rem;">Switch on 5xx errors or network drops.</div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="allow_quota_switch" value="1" id="quotaSwCheck" {{ $switchingMonitor['settings']->allow_quota_switch ? 'checked' : '' }}>
                                <label class="form-check-label small fw-semibold text-danger" for="quotaSwCheck">Switch on Normal Quota Exhaustion</label>
                            </div>
                            <div class="form-text" style="font-size: 0.72rem;">Disabled by default. Only enabled when alternative is authorized.</div>
                        </div>
                    </div>
                    <div class="mt-2 text-end">
                        <button type="submit" class="btn btn-sm btn-primary">Save Switching Policy</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card-body p-4 pt-3">
            <!-- Active Provider & Alternative Hero Row -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="p-3 border rounded-3 bg-white h-100 border-start border-4 border-primary">
                        <span class="text-muted small text-uppercase fw-semibold" style="font-size: 0.68rem;">Current Active Dispatcher</span>
                        @if($switchingMonitor['active_provider'])
                            <div class="d-flex align-items-center gap-2 mt-1">
                                <i class="bi {{ $switchingMonitor['active_provider']['icon'] }} text-primary fs-4"></i>
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">{{ $switchingMonitor['active_provider']['name'] }}</h6>
                                    <span class="small font-monospace text-muted">{{ $switchingMonitor['active_provider']['email'] }}</span>
                                </div>
                            </div>
                        @else
                            <div class="text-danger fw-semibold small mt-1">No eligible active provider</div>
                        @endif
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="p-3 border rounded-3 bg-white h-100 border-start border-4 border-success">
                        <span class="text-muted small text-uppercase fw-semibold" style="font-size: 0.68rem;">Configured Primary Provider</span>
                        @if($switchingMonitor['primary_provider'])
                            <div class="d-flex align-items-center gap-2 mt-1">
                                <i class="bi bi-star-fill text-warning fs-5"></i>
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">{{ $switchingMonitor['primary_provider']['name'] }}</h6>
                                    <span class="small font-monospace text-muted">{{ $switchingMonitor['primary_provider']['email'] }}</span>
                                </div>
                            </div>
                        @else
                            <div class="text-muted small mt-1">Not configured</div>
                        @endif
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="p-3 border rounded-3 bg-white h-100 border-start border-4 border-info">
                        <span class="text-muted small text-uppercase fw-semibold" style="font-size: 0.68rem;">Selected Alternative (Failover Target)</span>
                        @if($switchingMonitor['selected_alternative'])
                            <div class="d-flex align-items-center gap-2 mt-1">
                                <i class="bi bi-shield-check text-info fs-5"></i>
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">{{ $switchingMonitor['selected_alternative']['name'] }}</h6>
                                    <span class="small font-monospace text-muted">{{ $switchingMonitor['selected_alternative']['email'] }}</span>
                                </div>
                            </div>
                        @else
                            <div class="text-muted small mt-1">No alternative ready</div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Provider Health & Priority Scorecard (Section 2) -->
            <div class="table-responsive border rounded-3 mb-4">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.82rem;">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 80px;">Priority</th>
                            <th>Provider & Connected Account</th>
                            <th>Eligibility Status</th>
                            <th>Remaining Allowance</th>
                            <th>Policy Status</th>
                            <th>Health & Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($switchingMonitor['provider_scorecard'] as $score)
                            <tr>
                                <td class="text-center">
                                    <span class="badge {{ $score['priority'] === 1 ? 'bg-primary' : 'bg-secondary' }} rounded-pill">
                                        #{{ $score['priority'] }} {{ $score['is_primary'] ? '(Primary)' : '' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi {{ $score['icon'] }} text-primary fs-5"></i>
                                        <div>
                                            <div class="fw-bold text-dark">{{ $score['provider_name'] }}</div>
                                            <span class="font-monospace text-muted" style="font-size: 0.72rem;">{{ $score['account_email'] }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $badgeClass = match($score['eligibility_status']) {
                                            'ELIGIBLE' => 'bg-success-subtle text-success border border-success-subtle',
                                            'TEMPORARILY_UNAVAILABLE' => 'bg-warning-subtle text-warning border border-warning-subtle',
                                            'QUOTA_EXHAUSTED' => 'bg-danger-subtle text-danger border border-danger-subtle',
                                            'AUTHENTICATION_REQUIRED' => 'bg-danger-subtle text-danger border border-danger-subtle',
                                            'POLICY_NOT_ALLOWED' => 'bg-secondary text-white',
                                            default => 'bg-light text-muted border',
                                        };
                                    @endphp
                                    <span class="badge rounded-pill {{ $badgeClass }}">
                                        {{ $score['eligibility_status'] }}
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-semibold {{ str_contains($score['remaining_display'], 'unavailable') ? 'text-muted fst-italic' : 'text-dark' }}">
                                        {{ $score['remaining_display'] }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge rounded-pill {{ $score['policy_status'] === 'VERIFIED_ALLOWED' ? 'bg-success' : ($score['policy_status'] === 'PROHIBITED' ? 'bg-danger' : 'bg-warning text-dark') }}">
                                        {{ $score['policy_status'] }}
                                    </span>
                                </td>
                                <td class="small text-secondary">
                                    {{ $score['reason'] }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Switching History Audit Trail (Section 7) -->
            <div class="border rounded-3 p-3 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-clock-history me-1 text-primary"></i>Recent Provider Switching History
                    </h6>
                    <span class="text-muted small">Idempotency-indexed &bull; Zero Duplicate Guarantee</span>
                </div>
                @if($switchingMonitor['history']->isEmpty())
                    <div class="text-center py-3 text-muted small">
                        <i class="bi bi-shield-check me-1 text-success"></i>No provider failovers recorded. Primary mailer has been operating normally.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle mb-0" style="font-size: 0.78rem;">
                            <thead>
                                <tr>
                                    <th>Timestamp</th>
                                    <th>Transition</th>
                                    <th>Reason</th>
                                    <th>Attempt UUID</th>
                                    <th>Result</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($switchingMonitor['history'] as $hist)
                                    <tr>
                                        <td>{{ $hist->created_at->diffForHumans() }}</td>
                                        <td>
                                            <span class="font-monospace text-secondary">{{ $hist->previous_provider }}</span>
                                            <i class="bi bi-arrow-right mx-1 text-primary"></i>
                                            <span class="font-monospace fw-bold text-dark">{{ $hist->new_provider }}</span>
                                        </td>
                                        <td class="text-truncate" style="max-width: 250px;" title="{{ $hist->reason }}">{{ $hist->reason }}</td>
                                        <td class="font-monospace small text-muted">{{ Str::limit($hist->email_attempt_id, 12) }}</td>
                                        <td>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">
                                                {{ $hist->result }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- FREE EMAIL PROVIDER ALLOWANCE MONITOR (Separate Cards for Every Connected Provider) -->
    <div class="card border-0 shadow-sm rounded-4 mb-4" id="providerAllowanceMonitor">
        <div class="card-header bg-white border-0 py-3 px-4 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h5 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-envelope-paper-heart-fill text-primary"></i>
                    FREE EMAIL PROVIDER ALLOWANCE MONITOR
                </h5>
                <p class="text-muted small mb-0">Dynamic allowances monitored per provider account. Values reflect exact provider readings or documented plan limits without hardcoded universal caps.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ url('/settings#email') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-gear me-1"></i>Manage Accounts
                </a>
            </div>
        </div>
        <div class="card-body p-4 pt-0">
            <div class="row g-4">
                @foreach($emailSummary['provider_cards'] as $card)
                    <div class="col-xl-6 col-12">
                        <div class="card h-100 border shadow-sm rounded-3 {{ $card['is_paused'] ? 'border-danger-subtle bg-danger-subtle bg-opacity-10' : '' }}">
                            <div class="card-body p-3 d-flex flex-column">
                                <!-- Card Header: Provider & Status -->
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="stat-icon bg-light text-primary border" style="width: 40px; height: 40px; font-size: 1.25rem;">
                                            <i class="bi {{ $card['icon'] }}"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold mb-0 text-dark">{{ $card['provider_name'] }}</h6>
                                            <span class="small font-monospace text-muted">{{ $card['account_email'] }}</span>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-column align-items-end gap-1">
                                        <div class="d-flex align-items-center gap-1">
                                            <span class="badge border rounded-pill {{ $card['connection_status'] === 'CONNECTED' ? 'bg-success-subtle text-success border-success-subtle' : 'bg-danger-subtle text-danger border-danger-subtle' }}">
                                                <i class="bi {{ $card['connection_status'] === 'CONNECTED' ? 'bi-link-45deg' : 'bi-slash-circle' }} me-1"></i>{{ $card['connection_status'] }}
                                            </span>
                                            <span class="badge rounded-pill {{ $card['policy_badge']['class'] }}">
                                                <i class="bi {{ $card['policy_badge']['icon'] }} me-1"></i>{{ $card['policy_badge']['label'] }}
                                            </span>
                                        </div>
                                        <span class="badge rounded-pill {{ $card['source_badge']['class'] }}" style="font-size: 0.65rem;">
                                            {{ $card['source_badge']['label'] }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Policy Notice / Guidance -->
                                <div class="p-2 rounded bg-light small mb-3 border" style="font-size: 0.78rem;">
                                    <i class="bi bi-info-circle text-primary me-1"></i>{{ $card['guidance'] }}
                                </div>

                                @if($card['shared_pool_warning'])
                                    <div class="alert alert-warning py-1 px-2 small mb-2 d-flex align-items-center gap-2" style="font-size: 0.75rem;">
                                        <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                                        <span>{{ $card['shared_pool_warning'] }}</span>
                                    </div>
                                @endif

                                @if($card['is_paused'])
                                    <div class="alert alert-danger py-1 px-2 small mb-2 d-flex align-items-center gap-2" style="font-size: 0.75rem;">
                                        <i class="bi bi-pause-circle-fill flex-shrink-0"></i>
                                        <span><strong>Sending Paused:</strong> {{ $card['pause_reason'] }}</span>
                                    </div>
                                @endif

                                <!-- Quota & Allowance Progress -->
                                <div class="bg-white p-3 rounded border mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="small fw-semibold text-secondary">
                                            Sent Today: <strong class="text-dark">{{ $card['sent_today'] }} recipients</strong>
                                        </span>
                                        <span class="small fw-semibold {{ $card['is_remaining_unknown'] ? 'text-muted fst-italic' : 'text-success' }}">
                                            {{ $card['remaining_display'] }}
                                        </span>
                                    </div>

                                    @if(!$card['is_remaining_unknown'] && $card['documented_limit'])
                                        <div class="progress my-2" style="height: 8px;">
                                            <div class="progress-bar {{ $card['usage_percentage'] > 85 ? 'bg-danger' : ($card['usage_percentage'] > 50 ? 'bg-warning' : 'bg-primary') }}" role="progressbar" style="width: {{ $card['usage_percentage'] }}%"></div>
                                        </div>
                                        <div class="d-flex justify-content-between small text-muted" style="font-size: 0.72rem;">
                                            <span>Documented allowance: {{ number_format($card['documented_limit']) }} {{ $card['allowance_type'] }}</span>
                                            <span>{{ $card['usage_percentage'] }}% utilized</span>
                                        </div>
                                    @else
                                        <div class="py-1 px-2 bg-light rounded text-center small text-muted my-2 border border-dashed" style="font-size: 0.76rem;">
                                            <i class="bi bi-shield-slash me-1"></i>Provider does not expose live remaining counter. JobPilot AI locally logs all outbound recipients.
                                        </div>
                                    @endif
                                </div>

                                <!-- Card Metadata Footer -->
                                <div class="mt-auto pt-2 border-top small text-secondary d-flex flex-wrap justify-content-between gap-2" style="font-size: 0.75rem;">
                                    <div>
                                        <span class="text-muted">Reset window:</span>
                                        <span class="text-dark fw-medium">{{ $card['reset_description'] }}</span>
                                    </div>
                                    <div>
                                        <span class="text-muted">Sent this month:</span>
                                        <span class="text-dark fw-semibold">{{ number_format($card['sent_this_month']) }}</span>
                                    </div>
                                    <div>
                                        <span class="text-muted">Last activity:</span>
                                        <span class="text-dark fw-medium">{{ $card['last_successful_send'] }}</span>
                                    </div>
                                    @if($card['latest_error'])
                                        <div class="w-100 text-danger text-truncate" title="{{ $card['latest_error'] }}">
                                            <i class="bi bi-x-circle me-1"></i>{{ $card['latest_error'] }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- AI Free-First Gateway & Deduplication Bar -->
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                    <h6 class="fw-bold mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-cpu text-info"></i>
                        Free-First AI Intelligence Gateway
                    </h6>
                    <span class="badge bg-info-subtle text-info px-2 py-1 rounded-pill">
                        FREE_RESOURCES_ONLY
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush rounded-bottom-4">
                        @foreach($aiGateway['providers'] as $provider)
                            <div class="list-group-item d-flex align-items-center justify-content-between py-2 px-3">
                                <div>
                                    <div class="fw-semibold small">{{ $provider['name'] }}</div>
                                    <span class="text-muted font-monospace" style="font-size: 0.75rem;">{{ $provider['model'] }}</span>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-light text-dark border small">{{ $provider['quota_tier'] }}</span>
                                    <span class="badge {{ $provider['status'] === 'READY' ? 'bg-success' : 'bg-secondary' }} ms-1" style="font-size: 0.65rem;">
                                        {{ $provider['status'] }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                    <h6 class="fw-bold mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-database-check text-success"></i>
                        Cold Storage & Deduplication Engine
                    </h6>
                    <span class="badge bg-success-subtle text-success px-2 py-1 rounded-pill">
                        Fingerprint Indexed
                    </span>
                </div>
                <div class="card-body p-3">
                    <div class="mb-3 d-flex align-items-center gap-3">
                        <div class="stat-icon bg-primary-subtle text-primary" style="width: 44px; height: 44px;">
                            <i class="bi bi-hdd-network-fill"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-dark small">TiDB Cloud Starter (Free MySQL-Compatible)</div>
                            <div class="text-muted" style="font-size: 0.75rem;">ACID compliant, zero-spill regional clustering with SHA-256 fingerprint deduplication.</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon bg-success-subtle text-success" style="width: 44px; height: 44px;">
                            <i class="bi bi-google"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-dark small">Google Drive & Sheets Long-Term Vault</div>
                            <div class="text-muted" style="font-size: 0.75rem;">Monthly JSONL.gz archive export with SHA-256 integrity verification.</div>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-top d-flex justify-content-end">
                        <a href="{{ url('/settings') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-sliders me-1"></i>Configure Vault & Providers
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
