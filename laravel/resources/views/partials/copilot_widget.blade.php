@if(auth()->check())
<!-- ================= GLOBAL PILO AI COPILOT & VOICE ASSISTANT ================= -->
<div id="jobpilotCopilotContainer">
    <!-- Floating Trigger Launcher Button -->
    <button id="copilotLauncherBtn" class="btn btn-primary rounded-pill shadow-lg d-flex align-items-center gap-2 px-3 py-2" 
            title="Open Pilo AI (Voice, Mail Dispatch, Status Check & Reports)"
            onclick="toggleCopilotWidget()">
        <span class="copilot-pulse"></span>
        <i class="bi bi-mic-fill text-warning fs-5"></i>
        <span class="fw-bold d-none d-sm-inline" style="font-size: 0.9rem;">Pilo AI & Voice</span>
    </button>

    <!-- Floating Chat Window Card -->
    <div id="copilotChatCard" class="card shadow-lg border-0 rounded-4 d-none">
        <!-- Header -->
        <div class="card-header bg-gradient text-white py-3 px-3 d-flex justify-content-between align-items-center"
             style="background: linear-gradient(135deg, #091e3a, #102a43); border-top-left-radius: 16px; border-top-right-radius: 16px;">
            <div class="d-flex align-items-center gap-2">
                <div class="stat-icon bg-warning text-dark rounded-circle" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-robot fs-6"></i>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-1">
                        <h6 class="mb-0 fw-bold" style="font-size: 0.95rem;">Pilo AI Copilot</h6>
                        <span class="badge bg-success-subtle text-success border border-success-subtle py-0 px-1" style="font-size: 0.65rem;">Voice Ready</span>
                    </div>
                    <small class="text-white-50" style="font-size: 0.72rem;">Mail Dispatch &bull; Status &bull; Reports &bull; Free Inference</small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <!-- Voice Mute / Unmute Toggle -->
                <button type="button" id="btnVoiceToggle" class="btn btn-sm btn-icon text-white p-0" onclick="toggleVoiceOutput()" title="Toggle Voice Output (TTS)">
                    <i class="bi bi-volume-up-fill" id="voiceToggleIcon"></i>
                </button>
                <button type="button" class="btn btn-sm btn-icon text-white p-0" onclick="restartCopilotGuide()" title="Executive Briefing">
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
            <button class="btn btn-xs btn-outline-primary rounded-pill py-1 px-2" style="font-size: 0.75rem;" onclick="sendQuickPrompt('Generate comprehensive executive report')">
                📊 Generate Report
            </button>
            <button class="btn btn-xs btn-outline-secondary rounded-pill py-1 px-2" style="font-size: 0.75rem;" onclick="sendQuickPrompt('Check mailbox and collect unread emails')">
                📬 Check Mailbox
            </button>
            <button class="btn btn-xs btn-outline-secondary rounded-pill py-1 px-2" style="font-size: 0.75rem;" onclick="sendQuickPrompt('Send email to 2020with22@gmail.com subject Real Test body Greetings from Pilo AI')">
                ⚡ Send Mail via Pilo
            </button>
            <button class="btn btn-xs btn-outline-secondary rounded-pill py-1 px-2" style="font-size: 0.75rem;" onclick="sendQuickPrompt('Setup API keys order-wise')">
                🔑 Setup API Keys
            </button>
        </div>

        <!-- Input Bar with Mic Recording Trigger -->
        <div class="card-footer bg-white border-top p-2" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
            <form id="copilotForm" onsubmit="handleCopilotSubmit(event)" class="d-flex align-items-center gap-2 m-0">
                <!-- Voice Dictation Button (Web Speech API) -->
                <button type="button" id="btnVoiceRecord" class="btn btn-sm btn-outline-secondary rounded-circle" style="width: 34px; height: 34px; flex-shrink: 0;" 
                        title="Voice Input (Speak to Pilo)" onclick="toggleVoiceDictation()">
                    <i class="bi bi-mic-fill" id="micIcon"></i>
                </button>
                <input type="text" id="copilotInput" class="form-control form-control-sm border-0 bg-light rounded-pill px-3 py-2" 
                       placeholder="Speak or ask Pilo (e.g. 'send mail to...', 'report', 'check mail')..." autocomplete="off">
                <button type="submit" id="copilotSendBtn" class="btn btn-sm btn-primary rounded-circle" style="width: 34px; height: 34px; flex-shrink: 0;" title="Send">
                    <i class="bi bi-arrow-up-short fs-5"></i>
                </button>
            </form>
            <div id="voiceStatusIndicator" class="small text-danger mt-1 text-center d-none" style="font-size: 0.72rem;">
                <i class="bi bi-record-circle-fill me-1"></i> Listening to your voice... Speak now!
            </div>
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
    background: linear-gradient(135deg, #091e3a, #1d4ed8);
    box-shadow: 0 10px 25px -5px rgba(16, 42, 67, 0.4), 0 8px 10px -6px rgba(37, 99, 235, 0.3);
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
    width: 410px;
    max-width: 92vw;
    box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25) !important;
    animation: copilotFadeIn 0.25s ease-out;
}

@keyframes copilotFadeIn {
    from { opacity: 0; transform: translateY(20px) scale(0.95); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.copilot-msg-bubble {
    max-width: 88%;
    padding: 10px 14px;
    border-radius: 14px;
    font-size: 0.86rem;
    line-height: 1.5;
    word-break: break-word;
}
.copilot-msg-user {
    background: #1d4ed8;
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

.mic-recording-pulse {
    background-color: #ef4444 !important;
    color: #ffffff !important;
    animation: micBlink 1s infinite alternate;
}
@keyframes micBlink {
    from { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
    to { box-shadow: 0 0 0 8px rgba(239, 68, 68, 0); }
}
</style>

<script>
let copilotInitialized = false;
let voiceOutputEnabled = true;
let recognition = null;
let isRecordingVoice = false;

// Initialize Speech Recognition if supported
if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    recognition = new SpeechRecognition();
    recognition.continuous = false;
    recognition.interimResults = false;
    recognition.lang = 'en-US';

    recognition.onresult = function(event) {
        const transcript = event.results[0][0].transcript;
        const input = document.getElementById('copilotInput');
        if (input) {
            input.value = transcript;
            stopVoiceDictation();
            handleCopilotSubmit(new Event('submit'));
        }
    };

    recognition.onerror = function(event) {
        console.warn('Speech recognition error:', event.error);
        stopVoiceDictation();
    };

    recognition.onend = function() {
        stopVoiceDictation();
    };
}

function toggleVoiceDictation() {
    if (!recognition) {
        alert('Voice dictation is supported in modern browsers like Google Chrome and Microsoft Edge.');
        return;
    }
    if (isRecordingVoice) {
        stopVoiceDictation();
    } else {
        startVoiceDictation();
    }
}

function startVoiceDictation() {
    try {
        recognition.start();
        isRecordingVoice = true;
        document.getElementById('btnVoiceRecord')?.classList.add('mic-recording-pulse');
        document.getElementById('voiceStatusIndicator')?.classList.remove('d-none');
    } catch(e) {
        console.warn('Voice start exception:', e);
    }
}

function stopVoiceDictation() {
    isRecordingVoice = false;
    document.getElementById('btnVoiceRecord')?.classList.remove('mic-recording-pulse');
    document.getElementById('voiceStatusIndicator')?.classList.add('d-none');
    try { recognition.stop(); } catch(e) {}
}

function toggleVoiceOutput() {
    voiceOutputEnabled = !voiceOutputEnabled;
    const icon = document.getElementById('voiceToggleIcon');
    if (voiceOutputEnabled) {
        icon.className = 'bi bi-volume-up-fill';
        speakWithPilo('Voice output activated.');
    } else {
        icon.className = 'bi bi-volume-mute-fill';
        if ('speechSynthesis' in window) {
            window.speechSynthesis.cancel();
        }
    }
}

function speakWithPilo(text) {
    if (!voiceOutputEnabled || !('speechSynthesis' in window)) return;
    try {
        window.speechSynthesis.cancel();
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.rate = 1.05;
        utterance.pitch = 0.95;
        window.speechSynthesis.speak(utterance);
    } catch(e) {
        console.warn('TTS error:', e);
    }
}

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
        stopVoiceDictation();
        if ('speechSynthesis' in window) window.speechSynthesis.cancel();
    }
}

function initCopilotGuide() {
    copilotInitialized = true;
    const area = document.getElementById('copilotMessagesArea');
    area.innerHTML = `
        <div class="d-flex gap-2 mb-3">
            <div class="stat-icon bg-warning text-dark rounded-circle flex-shrink-0" style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem;">
                <i class="bi bi-robot"></i>
            </div>
            <div class="copilot-msg-bubble copilot-msg-bot">
                <div class="fw-bold mb-1">🤖 Pilo AI Online: Ready to assist.</div>
                You can speak or type commands directly:
                <ul class="mb-2 ps-3 mt-1" style="font-size: 0.8rem;">
                    <li><strong>Send Mails:</strong> <code>send email to <email> subject ... body ...</code></li>
                    <li><strong>Inbox Check:</strong> <code>check mail</code> or <code>collect unread emails</code></li>
                    <li><strong>Executive Briefing:</strong> <code>generate report</code></li>
                    <li><strong>API Auto-Setup:</strong> <code>my gemini key is <KEY></code></li>
                </ul>
                <div class="d-flex flex-wrap gap-1 mt-2">
                    <button class="btn btn-xs btn-outline-primary py-1 px-2" style="font-size: 0.75rem;" onclick="sendQuickPrompt('Generate comprehensive executive report')">
                        📊 Executive Report
                    </button>
                    <button class="btn btn-xs btn-outline-primary py-1 px-2" style="font-size: 0.75rem;" onclick="sendQuickPrompt('Check mailbox and collect unread emails')">
                        📬 Collect Mail
                    </button>
                    <a href="{{ url('/outlook') }}" class="btn btn-xs btn-outline-secondary py-1 px-2" style="font-size: 0.75rem;">
                        ✉️ Outlook Hub
                    </a>
                </div>
            </div>
        </div>
    `;
    speakWithPilo("Pilo online. All systems nominal. How may I assist you today?");
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
                <i class="bi bi-robot"></i>
            </div>
            <div class="copilot-msg-bubble copilot-msg-bot text-muted">
                <span class="spinner-border spinner-border-sm text-primary me-2"></span> Pilo analyzing telemetry & executing commands...
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
            if (data.data.speak) {
                speakWithPilo(data.data.speak);
            }
        } else {
            renderCopilotBotReply({ reply: "Sorry, I could not process your request. Please try again." });
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
            if (act.url && act.url !== '#') {
                actionsHtml += `
                    <a href="${act.url}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 py-1" style="font-size: 0.78rem;">
                        <i class="bi ${act.icon || 'bi-arrow-right'}"></i> ${escapeHtml(act.label)}
                    </a>
                `;
            } else {
                actionsHtml += `
                    <button class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 py-1" style="font-size: 0.78rem;" onclick="sendQuickPrompt('${escapeHtml(act.label)}')">
                        <i class="bi ${act.icon || 'bi-arrow-right'}"></i> ${escapeHtml(act.label)}
                    </button>
                `;
            }
        });
        actionsHtml += '</div>';
    }

    // Convert basic markdown format
    let formattedText = escapeHtml(data.reply || '')
        .replace(/### (.*?)\n/g, '<h6 class="fw-bold mt-2 mb-1">$1</h6>')
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.*?)\*/g, '<em>$1</em>')
        .replace(/`([^`]+)`/g, '<code class="bg-light px-1 rounded text-primary">$1</code>')
        .replace(/\n/g, '<br>');

    area.innerHTML += `
        <div class="d-flex gap-2 mb-3">
            <div class="stat-icon bg-warning text-dark rounded-circle flex-shrink-0" style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem;">
                <i class="bi bi-robot"></i>
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
