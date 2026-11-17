@if(auth()->check())
<!-- ================= GLOBAL FLOATING COPILOT & ONBOARDING ASSISTANT ================= -->
<div id="jobpilotCopilotContainer">
    <!-- Floating Trigger Launcher Button -->
    <button id="copilotLauncherBtn" class="btn btn-primary rounded-pill shadow-lg d-flex align-items-center gap-2 px-3 py-2" 
            title="Ask JobPilot AI Assistant (Guidance, API Setup, Auto-Fill)"
            onclick="toggleCopilotWidget()">
        <span class="copilot-pulse"></span>
        <i class="bi bi-stars fs-5 text-warning"></i>
        <span class="fw-bold d-none d-sm-inline" style="font-size: 0.9rem;">AI Copilot & Setup Guide</span>
    </button>

    <!-- Floating Chat Window Card -->
    <div id="copilotChatCard" class="card shadow-lg border-0 rounded-4 d-none">
        <!-- Header -->
        <div class="card-header bg-gradient text-white py-3 px-3 d-flex justify-content-between align-items-center"
             style="background: linear-gradient(135deg, #0f172a, #1e293b); border-top-left-radius: 16px; border-top-right-radius: 16px;">
            <div class="d-flex align-items-center gap-2">
                <div class="stat-icon bg-warning text-dark rounded-circle" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-stars fs-6"></i>
                </div>
                <div>
                    <h6 class="mb-0 fw-bold" style="font-size: 0.95rem;">JobPilot Assistant</h6>
                    <small class="text-white-50" style="font-size: 0.72rem;">Gemini / Free Inference & Setup Guide</small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-icon text-white p-0" onclick="restartCopilotGuide()" title="Restart Guide">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
                <button type="button" class="btn-close btn-close-white btn-sm" onclick="toggleCopilotWidget()"></button>
            </div>
        </div>

        <!-- Chat Conversation Area -->
        <div id="copilotMessagesArea" class="card-body p-3 overflow-y-auto" style="height: 380px; background: #f8fafc;">
            <!-- Messages populated dynamically -->
        </div>

        <!-- Quick Suggestions Chips Bar -->
        <div id="copilotQuickChips" class="px-3 py-2 bg-light border-top d-flex gap-1 overflow-x-auto" style="white-space: nowrap;">
            <button class="btn btn-xs btn-outline-secondary rounded-pill py-1 px-2" style="font-size: 0.75rem;" onclick="sendQuickPrompt('Where do I start as a new user?')">
                🚀 Where do I start?
            </button>
            <button class="btn btn-xs btn-outline-secondary rounded-pill py-1 px-2" style="font-size: 0.75rem;" onclick="sendQuickPrompt('How do I setup API keys order-wise?')">
                🔑 Setup API Keys
            </button>
            <button class="btn btn-xs btn-outline-secondary rounded-pill py-1 px-2" style="font-size: 0.75rem;" onclick="sendQuickPrompt('Open Outlook Hub')">
                ✉️ Open Outlook
            </button>
            <button class="btn btn-xs btn-outline-secondary rounded-pill py-1 px-2" style="font-size: 0.75rem;" onclick="sendQuickPrompt('Tailor my CV')">
                📄 Tailor CV
            </button>
        </div>

        <!-- Input Bar -->
        <div class="card-footer bg-white border-top p-2" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
            <form id="copilotForm" onsubmit="handleCopilotSubmit(event)" class="d-flex align-items-center gap-2 m-0">
                <input type="text" id="copilotInput" class="form-control form-control-sm border-0 bg-light rounded-pill px-3 py-2" 
                       placeholder="Ask guide, paste API key, or say 'open settings'..." autocomplete="off">
                <button type="submit" id="copilotSendBtn" class="btn btn-sm btn-primary rounded-circle" style="width: 34px; height: 34px; flex-shrink: 0;" title="Send">
                    <i class="bi bi-arrow-up-short fs-5"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<style>
/* Floating Assistant Styling */
#jobpilotCopilotContainer {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 1060;
    font-family: inherit;
}

#copilotLauncherBtn {
    border: none;
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    box-shadow: 0 10px 25px -5px rgba(37, 99, 235, 0.4), 0 8px 10px -6px rgba(37, 99, 235, 0.3);
    transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
}
#copilotLauncherBtn:hover {
    transform: translateY(-3px) scale(1.02);
}

.copilot-pulse {
    width: 9px;
    height: 9px;
    background: #10b981;
    border-radius: 50%;
    box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
    animation: copilotPulse 1.8s infinite;
}

@keyframes copilotPulse {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}

#copilotChatCard {
    width: 380px;
    max-width: 90vw;
    box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25) !important;
    animation: copilotFadeIn 0.25s ease-out;
}

@keyframes copilotFadeIn {
    from { opacity: 0; transform: translateY(20px) scale(0.95); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.copilot-msg-bubble {
    max-width: 85%;
    padding: 10px 14px;
    border-radius: 14px;
    font-size: 0.86rem;
    line-height: 1.5;
    word-break: break-word;
}
.copilot-msg-user {
    background: #2563eb;
    color: #ffffff;
    border-bottom-right-radius: 2px;
    margin-left: auto;
}
.copilot-msg-bot {
    background: #ffffff;
    color: #1e293b;
    border-bottom-left-radius: 2px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
</style>

<script>
let copilotInitialized = false;

function toggleCopilotWidget() {
    const card = document.getElementById('copilotChatCard');
    const btn = document.getElementById('copilotLauncherBtn');
    
    if (card.classList.contains('d-none')) {
        card.classList.remove('d-none');
        btn.classList.add('d-none');
        if (!copilotInitialized) {
            initCopilotGuide();
        }
        setTimeout(() => document.getElementById('copilotInput')?.focus(), 200);
    } else {
        card.classList.add('d-none');
        btn.classList.remove('d-none');
    }
}

function initCopilotGuide() {
    copilotInitialized = true;
    const area = document.getElementById('copilotMessagesArea');
    area.innerHTML = `
        <div class="d-flex gap-2 mb-3">
            <div class="stat-icon bg-warning text-dark rounded-circle flex-shrink-0" style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem;">
                <i class="bi bi-stars"></i>
            </div>
            <div class="copilot-msg-bubble copilot-msg-bot">
                <div class="fw-bold mb-1">👋 Hi {{ auth()->user()->name }}!</div>
                I am your <strong>JobPilot AI Copilot</strong>. I guide you step-by-step, help configure free API keys (Gemini / Groq), and open any page with auto-filled settings.<br><br>
                <strong>Where would you like to start?</strong>
                <div class="d-flex flex-column gap-2 mt-2">
                    <a href="{{ url('/role-profiles') }}" class="btn btn-sm btn-outline-primary text-start d-flex align-items-center gap-2 py-1">
                        <i class="bi bi-person-badge"></i> 1. Setup Role Profile & Baseline CV
                    </a>
                    <a href="{{ url('/settings') }}" class="btn btn-sm btn-outline-primary text-start d-flex align-items-center gap-2 py-1">
                        <i class="bi bi-key-fill"></i> 2. Connect Free Gemini / Email Keys
                    </a>
                    <a href="{{ url('/outlook') }}" class="btn btn-sm btn-outline-primary text-start d-flex align-items-center gap-2 py-1">
                        <i class="bi bi-envelope-open-fill"></i> 3. Open Microsoft Outlook Hub
                    </a>
                </div>
            </div>
        </div>
    `;
}

function restartCopilotGuide() {
    initCopilotGuide();
}

function sendQuickPrompt(promptText) {
    document.getElementById('copilotInput').value = promptText;
    handleCopilotSubmit(new Event('submit'));
}

async function handleCopilotSubmit(e) {
    e.preventDefault();
    const input = document.getElementById('copilotInput');
    const message = input.value.trim();
    if (!message) return;

    input.value = '';
    const area = document.getElementById('copilotMessagesArea');

    // 1. Render User Bubble
    area.innerHTML += `
        <div class="d-flex justify-content-end mb-3">
            <div class="copilot-msg-bubble copilot-msg-user">
                ${escapeHtml(message)}
            </div>
        </div>
    `;
    area.scrollTop = area.scrollHeight;

    // 2. Render Loading Indicator
    const loadingId = 'copilotLoading_' + Date.now();
    area.innerHTML += `
        <div id="${loadingId}" class="d-flex gap-2 mb-3">
            <div class="stat-icon bg-warning text-dark rounded-circle flex-shrink-0" style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem;">
                <i class="bi bi-stars"></i>
            </div>
            <div class="copilot-msg-bubble copilot-msg-bot text-muted">
                <span class="spinner-border spinner-border-sm text-primary me-2"></span> Consulting JobPilot AI & Checking Settings...
            </div>
        </div>
    `;
    area.scrollTop = area.scrollHeight;

    try {
        const res = await fetch("{{ route('ai.widget.message') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ message: message })
        });

        const data = await res.json();
        const loadingEl = document.getElementById(loadingId);
        if (loadingEl) loadingEl.remove();

        if (data.success && data.data) {
            renderCopilotBotReply(data.data);
        } else {
            renderCopilotBotReply({ reply: "Sorry, I could not process your request. Please try again or open Settings directly." });
        }
    } catch (err) {
        const loadingEl = document.getElementById(loadingId);
        if (loadingEl) loadingEl.remove();
        renderCopilotBotReply({ reply: "Network connection hiccup. Please try again." });
    }
}

function renderCopilotBotReply(data) {
    const area = document.getElementById('copilotMessagesArea');
    let actionsHtml = '';
    
    if (data.quick_actions && data.quick_actions.length > 0) {
        actionsHtml = '<div class="d-flex flex-wrap gap-2 mt-2 pt-2 border-top">';
        data.quick_actions.forEach(act => {
            actionsHtml += `
                <a href="${act.url}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 py-1" style="font-size: 0.78rem;">
                    <i class="bi ${act.icon || 'bi-arrow-right'}"></i> ${escapeHtml(act.label)}
                </a>
            `;
        });
        actionsHtml += '</div>';
    }

    // Convert basic markdown format
    let formattedText = escapeHtml(data.reply || '')
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.*?)\*/g, '<em>$1</em>')
        .replace(/`([^`]+)`/g, '<code class="bg-light px-1 rounded text-primary">$1</code>')
        .replace(/\n/g, '<br>');

    area.innerHTML += `
        <div class="d-flex gap-2 mb-3">
            <div class="stat-icon bg-warning text-dark rounded-circle flex-shrink-0" style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem;">
                <i class="bi bi-stars"></i>
            </div>
            <div class="copilot-msg-bubble copilot-msg-bot">
                <div>${formattedText}</div>
                ${actionsHtml}
            </div>
        </div>
    `;
    area.scrollTop = area.scrollHeight;

    // Check if auto-navigation requested
    if (data.navigate_to && window.location.pathname !== data.navigate_to) {
        setTimeout(() => {
            window.location.href = data.navigate_to;
        }, 1500);
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.innerText = text;
    return div.innerHTML;
}
</script>
@endif
