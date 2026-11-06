@extends('layouts.app')

@section('title', 'Email Outreach & Mail Automation Engine')

@section('content')
    <div class="container-fluid px-0">
        <!-- Header -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h3 class="fw-bold text-dark mb-1">
                    <i class="bi bi-envelope-at-fill text-primary me-2"></i>Email Automation & Quota Engine
                </h3>
                <p class="text-secondary small mb-0">
                    Personalized email dispatch with dynamic free email allowance detection, multi-recipient (To/CC/BCC)
                    tracking, and human-in-the-loop review. Zero hardcoded application caps.
                </p>
            </div>
            <div>
                <button class="btn btn-primary d-inline-flex align-items-center gap-2" type="button"
                    data-bs-toggle="collapse" data-bs-target="#composeCollapse">
                    <i class="bi bi-send-plus-fill"></i>
                    <span>Compose Outreach Email</span>
                </button>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
                <i class="bi bi-check-circle-fill fs-5"></i>
                <div>{{ session('success') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                <div>{{ $errors->first() }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Automatic Failover Banner -->
        <div class="alert alert-primary py-2 px-3 small d-flex align-items-center justify-content-between mb-4 rounded-3 border-0 shadow-sm">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-shield-check fs-5"></i>
                <span><strong>AUTOMATIC FAILOVER & FAILSAFE DISPATCH ACTIVE:</strong> Outbound emails automatically route through priority free providers with zero-duplicate protection.</span>
            </div>
            <a href="{{ route('dashboard') }}#emailSwitchingMonitor" class="btn btn-sm btn-outline-primary bg-white fw-semibold">View Switching Monitor</a>
        </div>

        <!-- Dynamic Metrics Row -->
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-sm-6">
                <div class="stat-card">
                    <div class="text-muted small fw-semibold">RECIPIENTS SENT TODAY</div>
                    <div class="fs-4 fw-bold text-dark mt-1">{{ number_format($metrics['total_sent_today']) }} Recipients
                    </div>
                    <div class="text-muted small mt-2" style="font-size: 0.75rem;">Across all connected mailboxes</div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="stat-card">
                    <div class="text-primary small fw-semibold">RECIPIENTS SENT THIS MONTH</div>
                    <div class="fs-4 fw-bold text-primary mt-1">{{ number_format($metrics['total_sent_this_month']) }}
                        Recipients</div>
                    <div class="text-muted small mt-2" style="font-size: 0.75rem;">Monthly rolling volume</div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="stat-card">
                    <div class="text-success small fw-semibold">ELIGIBLE FREE PROVIDERS</div>
                    <div class="fs-4 fw-bold text-success mt-1">{{ $metrics['free_providers_eligible'] }} /
                        {{ $metrics['connected_providers'] }} Ready</div>
                    <div class="text-muted small mt-2" style="font-size: 0.75rem;">Policy compliant mailers</div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="stat-card">
                    <div class="text-warning small fw-semibold">PENDING HUMAN REVIEW</div>
                    <div class="fs-4 fw-bold text-warning mt-1">{{ $metrics['pending_review_count'] }} Staged</div>
                    <div class="text-muted small mt-2" style="font-size: 0.75rem;">Awaiting your approval</div>
                </div>
            </div>
        </div>

        <!-- Collapsible Email Composer -->
        <div class="collapse mb-4" id="composeCollapse">
            <div class="card shadow-sm border-0 border-top border-primary border-4" style="border-radius: 14px;">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-dark mb-3">Compose Job Application Outreach</h5>
                    <form action="{{ route('email.send') }}" method="POST">
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Sending Mailbox</label>
                                <select name="email_account_id" class="form-select form-select-sm">
                                    @foreach($accounts as $acc)
                                        <option value="{{ $acc->id }}">
                                            {{ $acc->email_address }} ({{ $acc->provider_type }}) — {{ $acc->remaining_quota }}
                                            quota left
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Primary Recipient (To) <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="to" class="form-control form-control-sm"
                                    placeholder="hiring.manager@acme.com" value="{{ old('to') }}" required>
                                <div class="form-text" style="font-size: 0.75rem;">Multiple emails separated by comma.
                                    Zero-guessed email policy enforced.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">CC Recipients (Optional)</label>
                                <input type="text" name="cc" class="form-control form-control-sm"
                                    placeholder="talent.lead@acme.com" value="{{ old('cc') }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">BCC Tracking (Optional)</label>
                                <input type="text" name="bcc" class="form-control form-control-sm"
                                    placeholder="outreach.archive@jobpilot.test" value="{{ old('bcc') }}">
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold small">Subject Line <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="subject" class="form-control form-control-sm"
                                    placeholder="Application for Senior Laravel Backend Specialist — Candidate Name"
                                    value="{{ old('subject') }}" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold small">Email Body (HTML / Formatted) <span
                                        class="text-danger">*</span></label>
                                <textarea name="body" rows="6" class="form-control"
                                    placeholder="Dear Hiring Team,&#10;&#10;I am writing to express my strong interest in the Senior Laravel Developer position..."
                                    required>{{ old('body') }}</textarea>
                            </div>

                            <div class="col-12">
                                <div class="form-check p-3 bg-light rounded border">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" name="require_review"
                                        value="1" id="reviewCheck" checked>
                                    <label class="form-check-label fw-semibold small text-dark" for="reviewCheck">
                                        Stage for Human Review (Hold in outbox for final visual inspection before
                                        transmission)
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-3 pt-3 border-top">
                            <button type="button" class="btn btn-sm btn-light border" data-bs-toggle="collapse"
                                data-bs-target="#composeCollapse">Cancel</button>
                            <button type="submit" class="btn btn-sm btn-primary px-4">Queue Outreach</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Pending Review Stage Section -->
        @if($pendingReviews->isNotEmpty())
            <div class="card shadow-sm border-0 mb-4 border-warning border-start border-4" style="border-radius: 14px;">
                <div class="card-header bg-white border-0 pt-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-eye-fill text-warning me-2"></i>Human-in-the-Loop Review Queue
                        ({{ $pendingReviews->count() }})
                    </h6>
                    <span class="text-muted small">Confirm and approve emails before they are dispatched.</span>
                </div>
                <div class="card-body p-4 pt-2">
                    <div class="list-group list-group-flush">
                        @foreach($pendingReviews as $rev)
                            <div
                                class="list-group-item px-0 py-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
                                <div style="max-width: 75%;">
                                    <div class="fw-bold text-dark">{{ $rev->subject }}</div>
                                    <div class="small text-secondary">
                                        To: <span class="text-dark fw-semibold">{{ implode(', ', $rev->to_recipients) }}</span>
                                        &bull;
                                        From: <span class="text-dark">{{ $rev->account?->email_address }}</span>
                                    </div>
                                    <div class="mt-2 p-2 bg-light rounded small text-secondary border"
                                        style="max-height: 80px; overflow-y: auto;">
                                        {{ Str::limit(strip_tags($rev->body_html), 200) }}
                                    </div>
                                </div>
                                <div>
                                    <form action="{{ route('email.approve-review', $rev->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success d-inline-flex align-items-center gap-1">
                                            <i class="bi bi-check2-circle"></i>
                                            <span>Approve & Send</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <!-- Connected Sender Mailboxes -->
        <div class="card shadow-sm border-0 mb-4" style="border-radius: 14px;">
            <div class="card-header bg-white border-0 pt-3 px-4 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-dark mb-0">Connected Outreach Mailboxes</h6>
                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse"
                    data-bs-target="#newAccountCollapse">
                    <i class="bi bi-plus-lg me-1"></i>Add Mailbox
                </button>
            </div>
            <div class="card-body p-4">
                <!-- Add Account Form Collapse -->
                <div class="collapse mb-4" id="newAccountCollapse">
                    <div class="p-3 bg-light rounded border">
                        <form action="{{ route('email.store-account') }}" method="POST">
                            @csrf
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Email Address</label>
                                    <input type="email" name="email_address" class="form-control form-control-sm"
                                        placeholder="candidate@gmail.com" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">Provider Protocol</label>
                                    <select name="provider_type" class="form-select form-select-sm" required>
                                        <option value="Gmail">Gmail API (OAuth2)</option>
                                        <option value="MicrosoftGraph">Microsoft Graph</option>
                                        <option value="LogRelay">Log Relay (Sandbox)</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">Custom Safety Cap (Optional)</label>
                                    <input type="number" name="custom_safety_cap" class="form-control form-control-sm"
                                        placeholder="Disabled (Uncapped)" min="1" max="2000">
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-sm btn-primary w-100">Connect</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Mailbox Cards Row -->
                <div class="row g-3">
                    @foreach($accounts as $acc)
                        <div class="col-md-6 col-12">
                            <div class="p-3 border rounded h-100 {{ $acc->is_active ? 'bg-white' : 'bg-light' }}">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-envelope-check text-primary fs-5"></i>
                                        <div>
                                            <div class="fw-bold text-dark mb-0">{{ $acc->email_address }}</div>
                                            <span class="badge bg-secondary-subtle text-dark border"
                                                style="font-size: 0.65rem;">{{ $acc->provider_type }}</span>
                                        </div>
                                    </div>
                                    <form action="{{ route('email.toggle-account', $acc->id) }}" method="POST">
                                        @csrf
                                        <button type="submit"
                                            class="btn btn-sm {{ $acc->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                            style="font-size: 0.75rem;">
                                            {{ $acc->is_active ? 'Pause Sending' : 'Activate' }}
                                        </button>
                                    </form>
                                </div>

                                <div class="mt-2 small text-secondary">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Sent Today:</span>
                                        <span class="fw-semibold text-dark">{{ $acc->sent_today }} recipients</span>
                                    </div>
                                    <div class="text-muted" style="font-size: 0.72rem;">
                                        @if($acc->daily_cap)
                                            User custom safety cap: {{ $acc->daily_cap }} recipients/day
                                        @else
                                            Allowance: Dynamic Provider Policy (Uncapped application-side)
                                        @endif
                                    </div>
                                </div>

                                @if($acc->pause_reason)
                                    <div class="mt-2 text-danger small" style="font-size: 0.75rem;">
                                        <i class="bi bi-exclamation-circle me-1"></i>{{ $acc->pause_reason }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Dispatch History Table -->
        <div class="card shadow-sm border-0" style="border-radius: 14px;">
            <div class="card-header bg-white border-0 pt-3 px-4">
                <h6 class="fw-bold text-dark mb-0">Outbound Outreach History</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                        <thead class="table-light text-muted text-uppercase" style="font-size: 0.75rem;">
                            <tr>
                                <th class="ps-4">Recipient (To)</th>
                                <th>Subject</th>
                                <th>Sender Account</th>
                                <th>Recipients Count</th>
                                <th>Status</th>
                                <th>Dispatched At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark">{{ implode(', ', $log->to_recipients) }}</div>
                                        @if(!empty($log->cc_recipients))
                                            <div class="text-muted" style="font-size: 0.7rem;">CC:
                                                {{ implode(', ', $log->cc_recipients) }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="text-dark">{{ Str::limit($log->subject, 40) }}</span>
                                    </td>
                                    <td>
                                        <span class="text-secondary small">{{ $log->account?->email_address }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">{{ $log->total_recipients_count }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $badge = match ($log->status) {
                                                'Sent' => 'bg-success-subtle text-success border-success-subtle',
                                                'PendingReview' => 'bg-warning-subtle text-warning border-warning-subtle',
                                                'QuotaPaused' => 'bg-danger text-white',
                                                default => 'bg-danger-subtle text-danger border-danger-subtle'
                                            };
                                        @endphp
                                        <span class="badge border rounded-pill {{ $badge }}">
                                            {{ $log->status }}
                                        </span>
                                    </td>
                                    <td class="text-secondary small">
                                        {{ $log->sent_at?->format('M d, H:i') ?? 'Pending' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        No outreach emails sent yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($logs->hasPages())
                <div class="card-footer bg-white border-0 py-3 px-4">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection