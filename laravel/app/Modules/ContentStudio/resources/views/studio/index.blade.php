@extends('layouts.app')

@section('title', 'Content & Image Studio')

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold text-dark mb-1">
                <i class="bi bi-palette2 text-primary me-2"></i>LinkedIn Content & Image Studio
            </h3>
            <p class="text-secondary small mb-0">
                AI-driven technical post drafting, free Cloudflare Workers AI banner generation, and content calendar scheduling.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-dark border px-3 py-2">
                <i class="bi bi-cpu-fill text-info me-1"></i>Cloudflare Workers AI (10k Free Neurons/Day)
            </span>
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="fetchTopics('all')">
                <i class="bi bi-lightbulb-fill text-warning me-1"></i>Ideate Topics
            </button>
            <button type="button" class="btn btn-primary btn-sm" onclick="resetForm()">
                <i class="bi bi-plus-lg me-1"></i>New Post
            </button>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- 4 Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 12px;">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="stat-icon bg-primary-subtle text-primary">
                        <i class="bi bi-file-earmark-post-fill"></i>
                    </div>
                    <div>
                        <span class="text-secondary small fw-medium">Total Posts</span>
                        <h4 class="fw-bold mb-0 text-dark">{{ $stats['total'] }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 12px;">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="stat-icon bg-info-subtle text-info">
                        <i class="bi bi-calendar-check-fill"></i>
                    </div>
                    <div>
                        <span class="text-secondary small fw-medium">Scheduled</span>
                        <h4 class="fw-bold mb-0 text-dark">{{ $stats['scheduled'] }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 12px;">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="stat-icon bg-success-subtle text-success">
                        <i class="bi bi-send-check-fill"></i>
                    </div>
                    <div>
                        <span class="text-secondary small fw-medium">Published</span>
                        <h4 class="fw-bold mb-0 text-dark">{{ $stats['published'] }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 12px;">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="stat-icon bg-warning-subtle text-warning">
                        <i class="bi bi-pencil-square"></i>
                    </div>
                    <div>
                        <span class="text-secondary small fw-medium">Drafts</span>
                        <h4 class="fw-bold mb-0 text-dark">{{ $stats['draft'] }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Studio Layout: Left Composer & Image Gen, Right Live Mock & Calendar -->
    <div class="row g-4">
        <!-- Left Column: Topic Ideator & Post Composer -->
        <div class="col-lg-7">
            <!-- 1. Topic Ideator Card -->
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 14px;">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-lightbulb-fill text-warning me-2"></i>Technical Topic Ideator
                        </h6>
                        <small class="text-muted">High-converting discussions on architecture, internals, and database scaling.</small>
                    </div>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary active" onclick="fetchTopics('all')">All</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="fetchTopics('architecture')">Architecture</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="fetchTopics('database')">Database</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="fetchTopics('php_internals')">PHP</button>
                    </div>
                </div>
                <div class="card-body p-3">
                    <div class="row g-2" id="topic-ideas-container">
                        <!-- Default Seed Topics -->
                        <div class="col-md-6">
                            <div class="p-3 border rounded bg-light h-100 d-flex flex-column justify-content-between cursor-pointer hover-shadow" onclick="useTopic('Why MySQL Composite Indexes Fail on Range Queries', 'database')">
                                <div>
                                    <span class="badge bg-primary-subtle text-primary small mb-1">Database</span>
                                    <p class="small fw-bold text-dark mb-1">Why MySQL Composite Indexes Fail on Range Queries</p>
                                    <p class="small text-secondary mb-2 fst-italic">"Most developers index (status, created_at) and wonder why it scans 500k rows."</p>
                                </div>
                                <button type="button" class="btn btn-sm btn-white border w-100"><i class="bi bi-pencil-fill me-1"></i>Draft This Topic</button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 border rounded bg-light h-100 d-flex flex-column justify-content-between cursor-pointer hover-shadow" onclick="useTopic('Preventing Redis Cache Stampedes in High-Concurrency APIs', 'architecture')">
                                <div>
                                    <span class="badge bg-info-subtle text-info small mb-1">Architecture</span>
                                    <p class="small fw-bold text-dark mb-1">Preventing Redis Cache Stampedes in High-Concurrency APIs</p>
                                    <p class="small text-secondary mb-2 fst-italic">"When hot keys expire during peak traffic, primary DBs crash in seconds."</p>
                                </div>
                                <button type="button" class="btn btn-sm btn-white border w-100"><i class="bi bi-pencil-fill me-1"></i>Draft This Topic</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Post Composer Form -->
            <form id="post-editor-form" action="{{ route('content.save') }}" method="POST">
                @csrf
                <input type="hidden" name="post_id" id="form-post-id" value="">
                <input type="hidden" name="image_path" id="form-image-path" value="">
                <input type="hidden" name="image_provider" id="form-image-provider" value="">

                <div class="card shadow-sm border-0 mb-4" style="border-radius: 14px;">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-bold mb-0 text-dark">
                                <i class="bi bi-pencil-square text-primary me-2"></i>Post Composer
                            </h6>
                            <small class="text-muted">Draft structured engineering posts with hooks, takeaways, and hashtags.</small>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="btn-ai-draft" onclick="draftWithAI()">
                                <i class="bi bi-stars me-1"></i>Draft with AI
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="copyPostContent()">
                                <i class="bi bi-clipboard me-1"></i>Copy Text
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3 mb-3">
                            <div class="col-md-8">
                                <label class="form-label small fw-bold text-dark mb-1">Post Title / Technical Subject:</label>
                                <input type="text" name="title" id="post_title" class="form-control" 
                                       placeholder="e.g. Why MySQL Composite Indexes Fail on Range Queries" 
                                       oninput="updatePreview()" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-dark mb-1">Technical Category:</label>
                                <select name="category" id="post_category" class="form-select">
                                    <option value="architecture">Architecture</option>
                                    <option value="database">Database</option>
                                    <option value="php_internals">PHP Internals</option>
                                    <option value="devops">DevOps & Queues</option>
                                    <option value="craftsmanship">Craftsmanship</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-bold text-dark mb-0">LinkedIn Post Copy:</label>
                                <span class="small text-muted" id="post-char-counter">0 / 3000 chars</span>
                            </div>
                            <textarea name="content" id="post_content" rows="12" class="form-control font-monospace small" 
                                      placeholder="Write your technical insight, architecture trade-offs, code snippet, and conversational call-to-action..."
                                      oninput="updatePreview(); updateCharCounter();" required></textarea>
                            <div class="form-text small text-muted">
                                <i class="bi bi-info-circle me-1"></i>LinkedIn posts between 1,200 and 1,800 characters typically achieve optimal algorithmic reach.
                            </div>
                        </div>

                        <!-- Image Prompt & Generation Studio -->
                        <div class="p-3 border rounded bg-light mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label small fw-bold text-dark mb-0">
                                    <i class="bi bi-image-fill text-primary me-1"></i>Visual Banner Asset (Cloudflare Workers AI / Vector):
                                </label>
                                <button type="button" class="btn btn-sm btn-primary" id="btn-gen-img" onclick="generateBannerImage()">
                                    <i class="bi bi-magic me-1"></i>Generate Banner
                                </button>
                            </div>
                            <div class="input-group input-group-sm mb-2">
                                <span class="input-group-text bg-white"><i class="bi bi-chat-left-quote"></i></span>
                                <input type="text" name="image_prompt" id="image_prompt" class="form-control" 
                                       placeholder="Visual description for Cloudflare SDXL (e.g. Modern isometric database node network with glowing lines, dark slate aesthetic)">
                            </div>
                            <!-- Image Status & Preview Thumbnail -->
                            <div id="image-status-bar" class="small text-muted d-flex align-items-center justify-content-between">
                                <span id="image-provider-label"><i class="bi bi-check-circle me-1"></i>Ready to generate 1200x630 LinkedIn promotional banner.</span>
                                <a href="#" id="download-banner-link" class="text-primary text-decoration-none fw-semibold d-none" target="_blank" download>
                                    <i class="bi bi-download me-1"></i>Download Asset
                                </a>
                            </div>
                        </div>

                        <!-- Schedule & Action Buttons -->
                        <div class="row g-3 align-items-end">
                            <div class="col-md-5">
                                <label class="form-label small fw-bold text-dark mb-1">Schedule Date & Time:</label>
                                <input type="datetime-local" name="scheduled_at" id="scheduled_at" class="form-control form-control-sm"
                                       value="{{ date('Y-m-d\TH:i', strtotime('+1 day 14:00')) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-dark mb-1">Status:</label>
                                <select name="status" id="post_status" class="form-select form-select-sm">
                                    <option value="draft">Draft</option>
                                    <option value="scheduled" selected>Scheduled</option>
                                    <option value="published">Published</option>
                                </select>
                            </div>
                            <div class="col-md-4 text-end">
                                <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold w-100">
                                    <i class="bi bi-cloud-arrow-up-fill me-1"></i>Save & Schedule
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Right Column: Live Feed Mock & Content Calendar -->
        <div class="col-lg-5">
            <!-- 1. Live LinkedIn Feed Mock Card -->
            <div class="card shadow-sm border-0 mb-4 sticky-top" style="border-radius: 14px; top: 90px;">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-linkedin text-primary me-2"></i>Live Post Feed Preview
                    </h6>
                    <span class="badge bg-light text-secondary small border">Desktop & Mobile View</span>
                </div>
                <div class="card-body p-4 bg-light">
                    <!-- LinkedIn Card Shell -->
                    <div class="card border shadow-xs bg-white" style="border-radius: 8px;">
                        <div class="card-body p-3">
                            <!-- Author Row -->
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 44px; height: 44px; font-size: 1.1rem;">
                                    {{ substr(auth()->user()->name, 0, 1) }}
                                </div>
                                <div>
                                    <div class="fw-bold text-dark small">{{ auth()->user()->name }}</div>
                                    <div class="text-secondary small" style="font-size: 0.75rem;">Software Engineer | Architecture Insights • 1h • <i class="bi bi-globe-americas"></i></div>
                                </div>
                            </div>

                            <!-- Post Text Preview -->
                            <div class="small text-dark mb-3" id="preview-text" style="white-space: pre-line; line-height: 1.5;">
                                Start typing in the post composer or click "Draft with AI" to preview your live LinkedIn post here...
                            </div>

                            <!-- Attached Image Banner Preview -->
                            <div id="preview-image-container" class="mb-3 text-center d-none">
                                <img src="" id="preview-banner-img" class="img-fluid rounded border shadow-xs" style="max-height: 260px; object-fit: cover; width: 100%;" alt="Banner Preview">
                            </div>

                            <!-- LinkedIn Engagement Bar -->
                            <div class="border-top pt-2 d-flex justify-content-around text-secondary small" style="font-size: 0.8rem;">
                                <span><i class="bi bi-hand-thumbs-up me-1"></i>Like</span>
                                <span><i class="bi bi-chat-text me-1"></i>Comment</span>
                                <span><i class="bi bi-repeat me-1"></i>Repost</span>
                                <span><i class="bi bi-send me-1"></i>Send</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Upcoming Schedule Slots & Post Library -->
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 14px;">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-calendar3 text-primary me-2"></i>Content Calendar & Library
                    </h6>
                    <div class="btn-group btn-group-sm">
                        <a href="{{ route('content.index') }}" class="btn btn-outline-secondary {{ empty($activeStatus) ? 'active' : '' }}">All</a>
                        <a href="{{ route('content.index', ['status' => 'scheduled']) }}" class="btn btn-outline-secondary {{ $activeStatus === 'scheduled' ? 'active' : '' }}">Scheduled</a>
                        <a href="{{ route('content.index', ['status' => 'draft']) }}" class="btn btn-outline-secondary {{ $activeStatus === 'draft' ? 'active' : '' }}">Drafts</a>
                    </div>
                </div>
                <div class="card-body p-3">
                    @forelse($posts as $p)
                        <div class="p-3 border rounded mb-2 bg-white hover-shadow d-flex justify-content-between align-items-start gap-2">
                            <div style="flex: 1;">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="badge {{ $p->status === 'scheduled' ? 'bg-info-subtle text-info' : ($p->status === 'published' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary') }} small text-capitalize">
                                        {{ $p->status }}
                                    </span>
                                    <span class="badge bg-light text-dark border small text-capitalize">{{ $p->category }}</span>
                                    @if($p->scheduled_at)
                                        <span class="small text-muted"><i class="bi bi-clock me-1"></i>{{ $p->scheduled_at->format('M d, H:i') }}</span>
                                    @endif
                                </div>
                                <h6 class="fw-bold text-dark small mb-1">{{ $p->title }}</h6>
                                <p class="small text-secondary mb-0 text-truncate" style="max-width: 320px;">
                                    {{ Str::limit($p->content, 90) }}
                                </p>
                            </div>
                            <div class="d-flex flex-column gap-1">
                                <button type="button" class="btn btn-sm btn-light border" title="Edit post"
                                        onclick="loadPost({{ json_encode($p) }})">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form action="{{ route('content.destroy', $p->id) }}" method="POST" onsubmit="return confirm('Delete this post?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-light border text-danger" title="Delete post">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted small">
                            <i class="bi bi-calendar-x fs-3 d-block mb-2"></i>
                            No posts found. Use "Ideate Topics" or "Draft with AI" to create your first LinkedIn post!
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function updatePreview() {
        const title = document.getElementById('post_title').value;
        const content = document.getElementById('post_content').value;
        const preview = document.getElementById('preview-text');

        if (content.trim()) {
            preview.textContent = content;
        } else if (title.trim()) {
            preview.textContent = `${title}\n\n[Post body will appear here as you type...]`;
        } else {
            preview.textContent = 'Start typing in the post composer or click "Draft with AI" to preview your live LinkedIn post here...';
        }
    }

    function updateCharCounter() {
        const text = document.getElementById('post_content').value;
        const counter = document.getElementById('post-char-counter');
        counter.textContent = `${text.length} / 3000 chars`;
    }

    function copyPostContent() {
        const text = document.getElementById('post_content').value;
        if (!text.trim()) {
            alert('Post content is empty.');
            return;
        }
        navigator.clipboard.writeText(text).then(() => {
            alert('Post text copied to clipboard!');
        });
    }

    function resetForm() {
        document.getElementById('form-post-id').value = '';
        document.getElementById('form-image-path').value = '';
        document.getElementById('form-image-provider').value = '';
        document.getElementById('post_title').value = '';
        document.getElementById('post_content').value = '';
        document.getElementById('image_prompt').value = '';
        document.getElementById('preview-image-container').classList.add('d-none');
        document.getElementById('download-banner-link').classList.add('d-none');
        updatePreview();
        updateCharCounter();
    }

    function useTopic(topicTitle, category) {
        document.getElementById('post_title').value = topicTitle;
        if (category) {
            document.getElementById('post_category').value = category;
        }
        updatePreview();
        draftWithAI();
    }

    function loadPost(post) {
        document.getElementById('form-post-id').value = post.id;
        document.getElementById('post_title').value = post.title;
        document.getElementById('post_content').value = post.content;
        document.getElementById('post_category').value = post.category || 'architecture';
        document.getElementById('post_status').value = post.status || 'draft';
        document.getElementById('image_prompt').value = post.image_prompt || '';

        if (post.image_path) {
            document.getElementById('form-image-path').value = post.image_path;
            const previewContainer = document.getElementById('preview-image-container');
            const previewImg = document.getElementById('preview-banner-img');
            const downloadLink = document.getElementById('download-banner-link');
            
            const storageUrl = `/storage/${post.image_path}`;
            previewImg.src = storageUrl;
            previewContainer.classList.remove('d-none');
            downloadLink.href = storageUrl;
            downloadLink.classList.remove('d-none');
        }

        updatePreview();
        updateCharCounter();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function fetchTopics(category) {
        fetch('{{ route("content.topics") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ category: category })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.topics) {
                const container = document.getElementById('topic-ideas-container');
                container.innerHTML = data.topics.map(t => `
                    <div class="col-md-6">
                        <div class="p-3 border rounded bg-light h-100 d-flex flex-column justify-content-between cursor-pointer hover-shadow" onclick="useTopic('${t.title.replace(/'/g, "\\'")}', '${t.category || 'architecture'}')">
                            <div>
                                <span class="badge bg-primary-subtle text-primary small mb-1 text-capitalize">${t.category || 'Architecture'}</span>
                                <p class="small fw-bold text-dark mb-1">${t.title}</p>
                                <p class="small text-secondary mb-2 fst-italic">"${t.hook || ''}"</p>
                            </div>
                            <button type="button" class="btn btn-sm btn-white border w-100"><i class="bi bi-pencil-fill me-1"></i>Draft This Topic</button>
                        </div>
                    </div>
                `).join('');
            }
        })
        .catch(err => console.error('Error fetching topics:', err));
    }

    function draftWithAI() {
        const topic = document.getElementById('post_title').value;
        if (!topic.trim()) {
            alert('Please enter or select a topic title first.');
            return;
        }

        const btn = document.getElementById('btn-ai-draft');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Drafting...';

        fetch('{{ route("content.draft") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ topic: topic })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.post) {
                document.getElementById('post_content').value = data.post.content;
                if (data.post.image_prompt) {
                    document.getElementById('image_prompt').value = data.post.image_prompt;
                }
                updatePreview();
                updateCharCounter();
            }
        })
        .catch(err => {
            console.error('Error drafting post:', err);
            alert('Failed to draft post. Please try again.');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-stars me-1"></i>Draft with AI';
        });
    }

    function generateBannerImage() {
        const prompt = document.getElementById('image_prompt').value || 'Modern software architecture';
        const title = document.getElementById('post_title').value || 'System Architecture';

        const btn = document.getElementById('btn-gen-img');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Rendering...';

        fetch('{{ route("content.generate-image") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ prompt: prompt, title: title })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.image) {
                document.getElementById('form-image-path').value = data.image.path;
                document.getElementById('form-image-provider').value = data.image.provider;

                const previewContainer = document.getElementById('preview-image-container');
                const previewImg = document.getElementById('preview-banner-img');
                const downloadLink = document.getElementById('download-banner-link');
                const providerLabel = document.getElementById('image-provider-label');

                previewImg.src = data.image.url;
                previewContainer.classList.remove('d-none');

                downloadLink.href = data.image.url;
                downloadLink.classList.remove('d-none');

                const provText = data.image.provider === 'cloudflare_workers_ai' ? 'Cloudflare Workers AI (SDXL)' : 'High-Res Vector Engine';
                providerLabel.innerHTML = `<i class="bi bi-check-circle-fill text-success me-1"></i>Banner generated via ${provText}`;
            }
        })
        .catch(err => {
            console.error('Error generating image:', err);
            alert('Failed to generate visual asset.');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-magic me-1"></i>Generate Banner';
        });
    }
</script>
@endpush
@endsection

