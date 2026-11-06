@extends('layouts.app')

@section('title', 'AI Career Copilot Workspace')

@section('content')
<div class="container-fluid px-0">
    <!-- Top Bar with Free Policy Guarantee -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-2 border-bottom gap-2">
        <div class="d-flex align-items-center gap-2">
            <h4 class="fw-bold text-dark mb-0">
                <i class="bi bi-chat-square-dots-fill text-primary me-2"></i>Career Copilot Workspace
            </h4>
            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">
                <i class="bi bi-shield-check me-1"></i>Free-Only AI Gateway
            </span>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">
                Zero-Spend Enforcement Active
            </span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="small text-muted d-none d-md-flex align-items-center gap-1">
                <span>Active Models:</span>
                <span class="badge bg-light text-dark border">gemini-2.5-flash</span>
                <span class="badge bg-light text-dark border">llama-3.3-70b-versatile</span>
            </div>
            <a href="{{ route('ai.playground') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-terminal me-1"></i>Dev Playground
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 py-2" role="alert">
            <i class="bi bi-check-circle-fill"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Workspace Main Layout -->
    <div class="row g-3" style="min-height: calc(100vh - 180px);">
        <!-- Column 1: Conversations Sidebar -->
        <div class="col-lg-3 col-md-4">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 12px; display: flex; flex-direction: column;">
                <div class="card-header bg-white py-3 px-3 border-bottom d-flex justify-content-between align-items-center">
                    <span class="small fw-bold text-dark text-uppercase letter-spacing-1">Conversations</span>
                    <button type="button" class="btn btn-xs btn-primary py-1 px-2 d-flex align-items-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#newSessionModal">
                        <i class="bi bi-plus-lg"></i>New Chat
                    </button>
                </div>
                <div class="card-body p-2 flex-grow-1" style="overflow-y: auto; max-height: 580px;">
                    <div class="list-group list-group-flush">
                        @forelse($sessions as $s)
                            <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-start p-2 rounded mb-1 border-0 {{ $s->id === $activeSession->id ? 'bg-primary-subtle text-primary fw-bold' : '' }}">
                                <a href="{{ route('ai.chat.index', ['session_id' => $s->id]) }}" class="text-decoration-none text-dark flex-grow-1 me-2 text-truncate">
                                    <div class="small text-truncate" title="{{ $s->title }}">
                                        <i class="bi bi-chat-text me-1 text-primary"></i>{{ $s->title }}
                                    </div>
                                    <div class="text-muted" style="font-size: 0.7rem;">
                                        {{ $s->messages_count }} messages &bull; {{ $s->updated_at->diffForHumans(null, true) }}
                                    </div>
                                </a>
                                @if($sessions->count() > 1)
                                    <form action="{{ route('ai.chat.delete-session', $s->id) }}" method="POST" onsubmit="return confirm('Delete this conversation?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-link text-danger p-0 opacity-50 hover-opacity-100" title="Delete conversation">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @empty
                            <div class="text-center py-4 text-muted small">No conversations yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Column 2: Chat Stream & Message Input -->
        <div class="col-lg-6 col-md-8">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 12px; display: flex; flex-direction: column;">
                <!-- Chat Stream Header -->
                <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <span class="fw-bold text-dark small d-block">{{ $activeSession->title }}</span>
                        <span class="text-muted" style="font-size: 0.72rem;">
                            Persona: <strong>{{ $activeSession->roleProfile?->name ?? 'Default Career Persona' }}</strong>
                        </span>
                    </div>
                    <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.7rem;">
                        {{ $messages->count() }} messages
                    </span>
                </div>

                <!-- Messages Stream Scroll Area -->
                <div class="card-body p-3 flex-grow-1" id="chatStreamBox" style="overflow-y: auto; max-height: 480px; min-height: 400px; background-color: #f8fafc;">
                    @forelse($messages as $msg)
                        <div class="d-flex mb-3 {{ $msg->sender === 'user' ? 'justify-content-end' : 'justify-content-start' }}">
                            @if($msg->sender !== 'user')
                                <div class="me-2 mt-1">
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center shadow-sm" style="width: 32px; height: 32px; font-size: 0.85rem;">
                                        <i class="bi bi-stars"></i>
                                    </div>
                                </div>
                            @endif

                            <div style="max-width: 82%;">
                                <div class="p-3 rounded shadow-sm {{ $msg->sender === 'user' ? 'bg-primary text-white' : 'bg-white border text-dark' }}" style="border-radius: 12px; line-height: 1.6; font-size: 0.9rem; white-space: pre-wrap;">{{ $msg->message }}</div>

                                <!-- Attached Files Pills -->
                                @if(!empty($msg->attachments))
                                    <div class="mt-1 d-flex flex-wrap gap-1 {{ $msg->sender === 'user' ? 'justify-content-end' : '' }}">
                                        @foreach($msg->attachments as $att)
                                            <span class="badge bg-light text-dark border font-monospace" style="font-size: 0.7rem;">
                                                <i class="bi bi-paperclip me-1"></i>{{ $att['filename'] }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="text-muted mt-1 {{ $msg->sender === 'user' ? 'text-end' : '' }}" style="font-size: 0.68rem;">
                                    @if($msg->model)
                                        <span class="badge bg-light text-secondary border me-1">{{ $msg->model }}</span>
                                    @endif
                                    {{ $msg->created_at->format('H:i') }}
                                </div>
                            </div>

                            @if($msg->sender === 'user')
                                <div class="ms-2 mt-1">
                                    <div class="rounded-circle bg-dark text-white d-flex align-items-center justify-content-center shadow-sm" style="width: 32px; height: 32px; font-size: 0.85rem;">
                                        <i class="bi bi-person-fill"></i>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-chat-quote fs-1 d-block mb-2 text-secondary"></i>
                            Start typing below to consult with your Career Copilot.
                        </div>
                    @endforelse
                </div>

                <!-- Quick Prompt Helpers -->
                <div class="bg-light px-3 py-2 border-top d-flex flex-wrap align-items-center gap-1">
                    <span class="small text-muted me-1" style="font-size: 0.72rem;">Suggested:</span>
                    <button type="button" class="btn btn-xs btn-white border rounded-pill py-0 px-2 text-secondary" style="font-size: 0.72rem;" onclick="setPrompt('Prepare me for a technical system design interview on high-concurrency Laravel APIs.')">
                        Technical Screen Prep
                    </button>
                    <button type="button" class="btn btn-xs btn-white border rounded-pill py-0 px-2 text-secondary" style="font-size: 0.72rem;" onclick="setPrompt('How do I negotiate my compensation from $120k to $145k for a Senior Backend role?')">
                        Salary Counter-Offer
                    </button>
                    <button type="button" class="btn btn-xs btn-white border rounded-pill py-0 px-2 text-secondary" style="font-size: 0.72rem;" onclick="setPrompt('Critique my technical skills and highlight gaps against Staff Architect positions.')">
                        Staff Gap Analysis
                    </button>
                </div>

                <!-- Message Input Card Footer -->
                <div class="card-footer bg-white p-3 border-top">
                    <form action="{{ route('ai.chat.send-message', $activeSession->id) }}" method="POST" enctype="multipart/form-data" id="chatForm">
                        @csrf
                        <div class="mb-2 d-none" id="attachmentPreview">
                            <span class="badge bg-light text-dark border" id="attachmentLabel">
                                <i class="bi bi-paperclip me-1"></i><span id="attachmentFileName"></span>
                            </span>
                            <button type="button" class="btn btn-xs btn-link text-danger p-0 ms-1" onclick="clearAttachments()">&times;</button>
                        </div>

                        <div class="input-group">
                            <label class="btn btn-light border" title="Attach Document (PDF, TXT, DOC)" for="fileAttachmentInput" style="cursor: pointer;">
                                <i class="bi bi-paperclip"></i>
                            </label>
                            <input type="file" name="attachments[]" id="fileAttachmentInput" class="d-none" multiple onchange="handleFileSelect(this)">
                            
                            <textarea name="message" id="chatMessageInput" class="form-control" rows="2" placeholder="Ask your Career Copilot anything or attach a job description... (Enter sends, Shift+Enter for newline)" required style="resize: none; font-size: 0.88rem;"></textarea>
                            
                            <button type="submit" class="btn btn-primary px-3 d-flex align-items-center gap-1" id="chatSendBtn">
                                <i class="bi bi-send-fill"></i>
                                <span class="d-none d-sm-inline">Send</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Column 3: Live Career Context Drawer -->
        <div class="col-lg-3 d-none d-lg-block">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 12px;">
                <div class="card-header bg-white py-3 px-3 border-bottom">
                    <span class="small fw-bold text-dark text-uppercase letter-spacing-1"><i class="bi bi-info-circle me-1 text-primary"></i>Career Context</span>
                </div>
                <div class="card-body p-3 small" style="overflow-y: auto; max-height: 580px;">
                    <!-- Persona Card -->
                    <div class="p-2 mb-3 rounded border bg-light">
                        <div class="fw-bold text-dark mb-1">Active Career Persona</div>
                        <div class="text-primary fw-semibold">{{ $activeSession->roleProfile?->name ?? 'Default Backend Persona' }}</div>
                        <div class="text-muted mt-1" style="font-size: 0.72rem;">
                            {{ $activeSession->roleProfile?->experience_years ?? 6.0 }} Years Experience &bull; Remote
                        </div>
                    </div>

                    <!-- Master CV Card -->
                    <div class="p-2 mb-3 rounded border bg-light">
                        <div class="fw-bold text-dark mb-1">Master ATS Resume</div>
                        <div class="text-dark fw-semibold text-truncate">{{ $defaultResume?->title ?? 'Senior Laravel Specialist CV' }}</div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle mt-1">96% ATS Compliant</span>
                    </div>

                    <!-- Pipeline Stats -->
                    <div class="p-2 mb-3 rounded border bg-light">
                        <div class="fw-bold text-dark mb-2">Application Pipeline</div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Total Staged/Sent:</span>
                            <span class="fw-bold text-dark">{{ $contextMetrics['total_applications'] }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Active Interviews:</span>
                            <span class="badge bg-info-subtle text-info">{{ $contextMetrics['active_interviews'] }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Offers:</span>
                            <span class="badge bg-success-subtle text-success">{{ $contextMetrics['active_offers'] }}</span>
                        </div>
                    </div>

                    <!-- Policy Info -->
                    <div class="p-2 rounded bg-light border text-muted" style="font-size: 0.72rem;">
                        <i class="bi bi-shield-check text-success me-1"></i>
                        Zero cost policy active: All completions powered by free-tier LLM endpoints. Your career data remains strictly private.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: New Conversation Session -->
<div class="modal fade" id="newSessionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('ai.chat.create-session') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-chat-plus-fill text-primary me-2"></i>New Copilot Conversation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Conversation Objective / Title</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. Stripe Interview Preparation" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Target Career Persona</label>
                    <select name="role_profile_id" class="form-select form-select-sm">
                        @foreach($roleProfiles as $prof)
                            <option value="{{ $prof->id }}" {{ $prof->is_default ? 'selected' : '' }}>
                                {{ $prof->name }} {{ $prof->is_default ? '(Default)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm">Start Conversation</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const streamBox = document.getElementById('chatStreamBox');
    if (streamBox) {
        streamBox.scrollTop = streamBox.scrollHeight;
    }

    const input = document.getElementById('chatMessageInput');
    if (input) {
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                if (input.value.trim() !== '') {
                    document.getElementById('chatForm').submit();
                }
            }
        });
    }
});

function setPrompt(text) {
    const input = document.getElementById('chatMessageInput');
    input.value = text;
    input.focus();
}

function handleFileSelect(input) {
    if (input.files && input.files.length > 0) {
        document.getElementById('attachmentFileName').textContent = input.files[0].name + (input.files.length > 1 ? ' (+' + (input.files.length - 1) + ' more)' : '');
        document.getElementById('attachmentPreview').classList.remove('d-none');
    }
}

function clearAttachments() {
    document.getElementById('fileAttachmentInput').value = '';
    document.getElementById('attachmentPreview').classList.add('d-none');
}
</script>
@endsection

