@extends('layouts.app')

@section('title', 'Authorized LinkedIn Publishing')

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold text-dark mb-1">
                <i class="bi bi-send-check text-primary me-2"></i>Authorized LinkedIn Publishing
            </h3>
            <p class="text-secondary small mb-0">
                Official LinkedIn Member API v2 with mandatory human-in-the-loop review safeguards and zero-spam enforcement.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-dark border px-3 py-2">
                <i class="bi bi-shield-lock-fill text-success me-1"></i>Zero-Bot Guarantee (Official API Only)
            </span>
            <a href="{{ route('content.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-palette2 me-1"></i>Content Studio
            </a>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- 1. LinkedIn Connection Status Card -->
    <div class="card shadow-sm border-0 mb-4" style="border-radius: 14px;">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon {{ $status['connected'] ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}" style="width: 56px; height: 56px; border-radius: 14px; font-size: 1.8rem; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-linkedin"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="fw-bold mb-0 text-dark">
                                {{ $status['connected'] ? 'LinkedIn Member Account Connected' : 'LinkedIn Account Not Connected' }}
                            </h5>
                            @if($status['connected'])
                                <span class="badge bg-success-subtle text-success small"><i class="bi bi-check-circle me-1"></i>Active OAuth2</span>
                            @else
                                <span class="badge bg-warning-subtle text-warning small"><i class="bi bi-clock-history me-1"></i>Authorization Required</span>
                            @endif
                        </div>
                        <p class="text-secondary small mb-0 mt-1">
                            @if($status['connected'])
                                Authorized as <strong class="text-dark">{{ $status['member_name'] }}</strong> ({{ $status['member_urn'] }}). Token valid until {{ date('M d, Y', strtotime($status['expires_at'])) }}.
                            @else
                                Connect your profile to authorize JobPilot AI to publish technical posts on your behalf using official LinkedIn Member APIs.
                            @endif
                        </p>
                    </div>
                </div>

                <div>
                    @if($status['connected'])
                        <form action="{{ route('linkedin.auth.disconnect') }}" method="POST" onsubmit="return confirm('Disconnect your LinkedIn account?')">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger btn-sm px-3">
                                <i class="bi bi-plug-fill me-1"></i>Disconnect Account
                            </button>
                        </form>
                    @else
                        <a href="{{ route('linkedin.auth.redirect') }}" class="btn btn-primary px-4 fw-semibold">
                            <i class="bi bi-linkedin me-2"></i>Connect with LinkedIn
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Publishing Governance & Anti-Spam Guard -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm bg-light p-3" style="border-radius: 12px;">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-person-check-fill text-primary fs-5"></i>
                    <h6 class="fw-bold text-dark mb-0">Human-in-the-Loop Review</h6>
                </div>
                <p class="small text-secondary mb-0">
                    JobPilot AI never dispatches posts in the background without explicit human confirmation in this review queue.
                </p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm bg-light p-3" style="border-radius: 12px;">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-shield-slash-fill text-danger fs-5"></i>
                    <h6 class="fw-bold text-dark mb-0">Zero-Bot Guarantee</h6>
                </div>
                <p class="small text-secondary mb-0">
                    Strictly prohibits automated connection requests, cold DMs, or browser automation bots that risk account restrictions.
                </p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm bg-light p-3" style="border-radius: 12px;">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-speedometer2 text-success fs-5"></i>
                    <h6 class="fw-bold text-dark mb-0">Quota Protection</h6>
                </div>
                <p class="small text-secondary mb-0">
                    Published today: <strong class="text-dark">{{ $status['posts_today'] }} / {{ $status['daily_limit'] }}</strong> posts. Safe pacing ensures your professional reputation remains organic.
                </p>
            </div>
        </div>
    </div>

    <!-- 3. Human Review Queue (Pending Dispatch) -->
    <div class="card shadow-sm border-0 mb-4" style="border-radius: 14px;">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <div>
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-clock-history text-primary me-2"></i>Human-in-the-Loop Review Queue
                </h6>
                <small class="text-muted">Posts awaiting final candidate review and authorization before dispatch.</small>
            </div>
            <span class="badge bg-primary-subtle text-primary">{{ $pendingPosts->count() }} Pending</span>
        </div>
        <div class="card-body p-4">
            @forelse($pendingPosts as $post)
                <div class="p-4 border rounded bg-white mb-3 shadow-xs hover-shadow">
                    <div class="row g-4 align-items-center">
                        <div class="col-lg-8">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge {{ $post->status === 'scheduled' ? 'bg-info-subtle text-info' : 'bg-secondary-subtle text-secondary' }} small text-capitalize">
                                    {{ $post->status }}
                                </span>
                                <span class="badge bg-light text-dark border small text-capitalize">{{ $post->category }}</span>
                                @if($post->scheduled_at)
                                    <span class="small text-muted"><i class="bi bi-calendar-event me-1"></i>Scheduled for: {{ $post->scheduled_at->format('M d, Y H:i') }}</span>
                                @endif
                            </div>
                            <h5 class="fw-bold text-dark mb-2">{{ $post->title }}</h5>
                            <div class="p-3 bg-light rounded text-dark small font-monospace mb-2" style="white-space: pre-line; max-height: 140px; overflow-y: auto;">
                                {{ $post->content }}
                            </div>
                        </div>

                        <div class="col-lg-4 text-center">
                            @if($post->image_path)
                                <img src="/storage/{{ $post->image_path }}" class="img-fluid rounded border shadow-xs mb-3" style="max-height: 120px; object-fit: cover; width: 100%;" alt="Banner Preview">
                            @else
                                <div class="p-3 border rounded bg-light text-muted small mb-3">
                                    <i class="bi bi-file-text me-1"></i>Text-Only Post (No Image)
                                </div>
                            @endif

                            <div class="d-flex justify-content-center gap-2">
                                <form action="{{ route('linkedin.publishing.approve', $post->id) }}" method="POST" onsubmit="return confirm('Approve and publish this post to LinkedIn immediately?')">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm px-3 fw-semibold" {{ ! $status['connected'] ? 'disabled' : '' }}>
                                        <i class="bi bi-check2-circle me-1"></i>Approve & Publish
                                    </button>
                                </form>
                                <form action="{{ route('linkedin.publishing.reject', $post->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-secondary btn-sm" title="Keep as Draft">
                                        <i class="bi bi-x-circle me-1"></i>Draft
                                    </button>
                                </form>
                            </div>
                            @if(! $status['connected'])
                                <div class="form-text text-danger small mt-1">Connect LinkedIn to enable publishing.</div>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-check-all fs-1 text-success d-block mb-2"></i>
                    <h6 class="fw-bold text-dark">Review Queue is Clear!</h6>
                    <p class="small text-secondary mb-3">No posts currently pending human authorization.</p>
                    <a href="{{ route('content.index') }}" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-lg me-1"></i>Create New Post in Content Studio
                    </a>
                </div>
            @endforelse
        </div>
    </div>

    <!-- 4. Published Posts Archive -->
    <div class="card shadow-sm border-0 mb-4" style="border-radius: 14px;">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-dark">
                <i class="bi bi-journal-check text-primary me-2"></i>Published Posts Archive
            </h6>
            <span class="badge bg-success-subtle text-success">{{ $publishedPosts->count() }} Live on LinkedIn</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th class="ps-4">Title & Subject</th>
                            <th>Category</th>
                            <th>Published Date</th>
                            <th>LinkedIn URN</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        @forelse($publishedPosts as $post)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark">{{ $post->title }}</div>
                                    <div class="text-secondary text-truncate" style="max-width: 340px;">
                                        {{ Str::limit($post->content, 80) }}
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border text-capitalize">{{ $post->category }}</span>
                                </td>
                                <td>
                                    {{ $post->published_at ? $post->published_at->format('M d, Y H:i') : 'Recently' }}
                                </td>
                                <td>
                                    <span class="font-monospace text-muted small" style="font-size: 0.75rem;">
                                        {{ $post->metrics['linkedin_post_urn'] ?? 'urn:li:share:...' }}
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    @php
                                        $url = $post->metrics['linkedin_url'] ?? '#';
                                    @endphp
                                    <a href="{{ $url }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-box-arrow-up-right me-1"></i>View on LinkedIn
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    No posts published yet. Approve a post from the review queue to publish your first post!
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

