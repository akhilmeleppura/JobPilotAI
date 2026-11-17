@if(auth()->check())
<!-- ================= GLOBAL PILO AI COPILOT & VOICE ASSISTANT ================= -->
<div id="jobpilotCopilotContainer">
    <!-- Floating Trigger Launcher Button with Smart Notification Badge -->
    <button id="copilotLauncherBtn" class="pilo-launcher-pill shadow-lg d-flex align-items-center gap-2 px-3 py-2 position-relative" 
            title="Open Pilo AI (Voice, Mail, Notifications & Reports)"
            onclick="toggleCopilotWidget()">
        <span class="pilo-neural-dot"></span>
        <i class="bi bi-robot text-warning fs-5"></i>
        <span class="fw-semibold text-white d-none d-sm-inline" style="font-size: 0.88rem; letter-spacing: 0.02em;">Pilo AI</span>
        <span id="piloFloatingNotifBadge" class="badge rounded-pill bg-danger d-none" style="font-size: 0.65rem;">
            0
        </span>
    </button>

    <!-- Floating Chat Window Card -->
    <div id="copilotChatCard" class="card shadow-2xl border-0 rounded-4 overflow-hidden d-none">
        <!-- Sleek Modern Header -->
        <div class="card-header py-3 px-3 d-flex justify-content-between align-items-center border-0"
             style="background: #0f172a; color: #f8fafc; border-top-left-radius: 16px; border-top-right-radius: 16px;">
            <div class="d-flex align-items-center gap-2.5">
                <div class="pilo-avatar-ring">
                    <i class="bi bi-cpu-fill text-warning fs-6"></i>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-1.5">
                        <span class="fw-bold" style="font-size: 0.95rem; color: #f8fafc;">Pilo AI</span>
                        <span class="pilo-status-tag">Live Neural</span>
                    </div>
                    <div class="text-white-50" style="font-size: 0.72rem;">Voice &bull; Notifications &bull; Dispatch</div>
                </div>
            </div>

            <!-- Header Action Controls: Mute/Sound, Notifications, Reset, and Close -->
            <div class="d-flex align-items-center gap-1">
                <!-- Sound Mute/Unmute Button -->
                <button type="button" id="btnVoiceToggle" class="pilo-header-action-btn" onclick="toggleVoiceOutput()" title="Mute / Unmute Voice Audio (TTS)">
                    <i class="bi bi-volume-up-fill" id="voiceToggleIcon"></i>
                </button>

                <!-- Smart Alerts / Notification shortcut -->
                <button type="button" id="btnCopilotNotifToggle" class="pilo-header-action-btn position-relative" onclick="showPiloNotificationTab()" title="Check Smart Alerts & Notifications">
                    <i class="bi bi-bell-fill"></i>
                    <span id="piloHeaderNotifBadge" class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle d-none"></span>
                </button>

                <!-- Reset / New Briefing -->
                <button type="button" class="pilo-header-action-btn" onclick="restartCopilotGuide()" title="Executive Briefing / Reset View">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>

                <!-- Dedicated Visible Close Button -->
                <button type="button" class="pilo-header-close-btn" onclick="toggleCopilotWidget()" title="Close Pilo Assistant">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>

        <!-- Chat Conversation Area -->
        <div id="copilotMessagesArea" class="card-body p-3 overflow-y-auto" style="height: 380px; background: #f8fafc;">
            <!-- Messages populated dynamically -->
        </div>

        <!-- Quick Suggestions Chips Bar -->
        <div id="copilotQuickChips" class="px-3 py-2 bg-white border-top border-bottom d-flex gap-1.5 overflow-x-auto" style="white-space: nowrap;">
            <button class="pilo-chip-btn pilo-chip-danger" onclick="sendQuickPrompt('Check smart notifications and urgent alerts')">
                <i class="bi bi-bell-fill"></i> Alerts
            </button>
            <button class="pilo-chip-btn pilo-chip-primary" onclick="sendQuickPrompt('Analyze match and ATS score for Full Stack Developer')">
                <i class="bi bi-bullseye"></i> ATS Score
            </button>
            <button class="pilo-chip-btn" onclick="sendQuickPrompt('Generate comprehensive executive report')">
                <i class="bi bi-bar-chart-fill"></i> Report
            </button>
            <button class="pilo-chip-btn" onclick="sendQuickPrompt('Check mailbox and collect unread emails')">
                <i class="bi bi-inbox-fill"></i> Inbox
            </button>
            <button class="pilo-chip-btn" onclick="sendQuickPrompt('Draft cover letter for Stripe')">
                <i class="bi bi-pen-fill"></i> Draft Letter
            </button>
            <button class="pilo-chip-btn" onclick="sendQuickPrompt('Send email to 2020with22@gmail.com subject Interview Availability body Ready for interview Friday')">
                <i class="bi bi-send-fill"></i> Send Mail
            </button>
        </div>

        <!-- Input Bar with Mic Dictation Trigger -->
        <div class="card-footer bg-white p-2.5 border-0">
            <form id="copilotForm" onsubmit="handleCopilotSubmit(event)" class="d-flex align-items-center gap-2 m-0">
                <!-- Voice Dictation Button (Web Speech API) -->
                <button type="button" id="btnVoiceRecord" class="btn btn-sm btn-light rounded-circle border shadow-xs" style="width: 36px; height: 36px; flex-shrink: 0;" 
                        title="Voice Input (Speak to Pilo)" onclick="toggleVoiceDictation()">
                    <i class="bi bi-mic-fill text-secondary" id="micIcon"></i>
                </button>
                <div class="position-relative flex-grow-1">
                    <input type="text" id="copilotInput" class="form-control form-control-sm border-0 bg-light rounded-pill px-3 py-2 pe-4" 
                           placeholder="Ask Pilo or click mic to speak..." autocomplete="off">
                </div>
                <button type="submit" id="copilotSendBtn" class="btn btn-sm btn-primary rounded-circle shadow-xs" style="width: 36px; height: 36px; flex-shrink: 0;" title="Send">
                    <i class="bi bi-arrow-up-short fs-5"></i>
                </button>
            </form>
            <div id="voiceStatusIndicator" class="small text-danger mt-1.5 text-center d-none" style="font-size: 0.72rem;">
                <i class="bi bi-record-circle-fill me-1"></i> Listening to your voice... Speak now!
            </div>
        </div>
    </div>
</div>

<style>
/* Modern Pilo UI Styles */
#jobpilotCopilotContainer {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 1060;
    font-family: inherit;
}

.pilo-launcher-pill {
    background: #0f172a;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 9999px;
    box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.4), 0 8px 10px -6px rgba(15, 23, 42, 0.2);
    transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.2s ease;
    cursor: pointer;
}
.pilo-launcher-pill:hover {
    transform: translateY(-2px) scale(1.03);
    box-shadow: 0 14px 28px -5px rgba(15, 23, 42, 0.5);
}

.pilo-neural-dot {
    width: 8px;
    height: 8px;
    background: #10b981;
    border-radius: 50%;
    box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
    animation: piloPulse 1.8s infinite;
}

@keyframes piloPulse {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 7px rgba(16, 185, 129, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}

#copilotChatCard {
    width: 420px;
    max-width: 92vw;
    background: #ffffff;
    box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.35) !important;
    animation: piloCardFadeIn 0.2s ease-out;
}

@keyframes piloCardFadeIn {
    from { opacity: 0; transform: translateY(16px) scale(0.97); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.pilo-avatar-ring {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
}

.pilo-status-tag {
    font-size: 0.65rem;
    font-weight: 600;
    padding: 1px 6px;
    border-radius: 9999px;
    background: rgba(16, 185, 129, 0.15);
    color: #34d399;
    border: 1px solid rgba(16, 185, 129, 0.3);
}

.pilo-header-action-btn {
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.12);
    color: #e2e8f0;
    width: 30px;
    height: 30px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 0.82rem;
    transition: background 0.15s, color 0.15s;
}
.pilo-header-action-btn:hover {
    background: rgba(255, 255, 255, 0.18);
    color: #ffffff;
}

.pilo-header-close-btn {
    background: rgba(239, 68, 68, 0.15);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #fca5a5;
    width: 30px;
    height: 30px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 0.82rem;
    margin-left: 2px;
    transition: background 0.15s, color 0.15s;
}
.pilo-header-close-btn:hover {
    background: rgba(239, 68, 68, 0.8);
    color: #ffffff;
}

.pilo-chip-btn {
    font-size: 0.74rem;
    font-weight: 500;
    padding: 4px 10px;
    border-radius: 9999px;
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
    cursor: pointer;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.pilo-chip-btn:hover {
    background: #e2e8f0;
    color: #0f172a;
}
.pilo-chip-primary {
    background: #eff6ff;
    color: #2563eb;
    border-color: #bfdbfe;
}
.pilo-chip-primary:hover {
    background: #dbeafe;
    color: #1d4ed8;
}
.pilo-chip-danger {
    background: #fef2f2;
    color: #dc2626;
    border-color: #fecaca;
}
.pilo-chip-danger:hover {
    background: #fee2e2;
    color: #b91c1c;
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
    background: #0f172a;
    color: #ffffff;
    border-bottom-right-radius: 2px;
    margin-left: auto;
}
.copilot-msg-bot {
    background: #ffffff;
    color: #1e293b;
    border-bottom-left-radius: 2px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}

.mic-recording-pulse {
    background-color: #ef4444 !important;
    border-color: #dc2626 !important;
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
        document.getElementById('micIcon')?.classList.replace('text-secondary', 'text-white');
        document.getElementById('voiceStatusIndicator')?.classList.remove('d-none');
    } catch(e) {
        console.warn('Voice start exception:', e);
    }
}

function stopVoiceDictation() {
    isRecordingVoice = false;
    document.getElementById('btnVoiceRecord')?.classList.remove('mic-recording-pulse');
    document.getElementById('micIcon')?.classList.replace('text-white', 'text-secondary');
    document.getElementById('voiceStatusIndicator')?.classList.add('d-none');
    try { recognition.stop(); } catch(e) {}
}

function toggleVoiceOutput() {
    voiceOutputEnabled = !voiceOutputEnabled;
    const icon = document.getElementById('voiceToggleIcon');
    const btn = document.getElementById('btnVoiceToggle');
    if (voiceOutputEnabled) {
        icon.className = 'bi bi-volume-up-fill text-success';
        btn.setAttribute('title', 'Voice Audio Active (Click to Mute)');
        speakWithPilo('Voice audio unmuted.');
    } else {
        icon.className = 'bi bi-volume-mute-fill text-danger';
        btn.setAttribute('title', 'Voice Audio Muted (Click to Unmute)');
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
        <div class="d-flex gap-2.5 mb-3">
            <div class="stat-icon bg-warning text-dark rounded-circle flex-shrink-0" style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem;">
                <i class="bi bi-robot"></i>
            </div>
            <div class="copilot-msg-bubble copilot-msg-bot">
                <div class="fw-bold mb-1 d-flex align-items-center gap-1.5">
                    <span>Pilo AI Online</span>
                    <span class="badge bg-success-subtle text-success py-0" style="font-size: 0.65rem;">Zero Key Ready</span>
                </div>
                Speak or type naturally without needing any API keys:
                <ul class="mb-2 ps-3 mt-1.5" style="font-size: 0.8rem;">
                    <li><strong>Smart Alerts:</strong> <code>check notifications</code> or <code>alerts</code></li>
                    <li><strong>ATS Match Score:</strong> <code>analyze match for <role></code></li>
                    <li><strong>Draft Cover Letter:</strong> <code>draft cover letter for <company></code></li>
                    <li><strong>Direct Email:</strong> <code>send email to <email> subject ... body ...</code></li>
                    <li><strong>Executive Briefing:</strong> <code>generate report</code></li>
                </ul>
                <div class="d-flex flex-wrap gap-1.5 mt-2 pt-1 border-top">
                    <button class="pilo-chip-btn pilo-chip-danger" onclick="sendQuickPrompt('Check smart notifications')">
                        <i class="bi bi-bell-fill"></i> Alerts
                    </button>
                    <button class="pilo-chip-btn pilo-chip-primary" onclick="sendQuickPrompt('Analyze match for Full Stack Developer')">
                        <i class="bi bi-bullseye"></i> Match Score
                    </button>
                    <button class="pilo-chip-btn" onclick="sendQuickPrompt('Generate comprehensive executive report')">
                        <i class="bi bi-bar-chart-fill"></i> Report
                    </button>
                </div>
            </div>
        </div>
    `;
    speakWithPilo("Pilo online. Proactive monitoring and smart features active.");
}

function restartCopilotGuide() {
    initCopilotGuide();
}

function showPiloNotificationTab() {
    sendQuickPrompt('check notifications');
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
        <div id="${loadingId}" class="d-flex gap-2.5 mb-3">
            <div class="stat-icon bg-warning text-dark rounded-circle flex-shrink-0" style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem;">
                <i class="bi bi-robot"></i>
            </div>
            <div class="copilot-msg-bubble copilot-msg-bot text-muted">
                <span class="spinner-border spinner-border-sm text-primary me-2"></span> Pilo processing neural analysis...
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
        actionsHtml = '<div class="d-flex flex-wrap gap-1.5 mt-2 pt-2 border-top">';
        data.quick_actions.forEach(act => {
            if (act.url && act.url !== '#') {
                actionsHtml += `
                    <a href="${act.url}" class="pilo-chip-btn pilo-chip-primary" style="font-size: 0.76rem; text-decoration: none;">
                        <i class="bi ${act.icon || 'bi-arrow-right'}"></i> ${escapeHtml(act.label)}
                    </a>
                `;
            } else {
                actionsHtml += `
                    <button class="pilo-chip-btn" style="font-size: 0.76rem;" onclick="sendQuickPrompt('${escapeHtml(act.label)}')">
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
        .replace(/```text\n([\s\S]*?)```/g, '<pre class="bg-light p-2 rounded small text-dark border">$1</pre>')
        .replace(/\[(.*?)\]\((.*?)\)/g, '<a href="$2" class="text-primary fw-semibold">$1</a>')
        .replace(/\n/g, '<br>');

    area.innerHTML += `
        <div class="d-flex gap-2.5 mb-3">
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

// Proactive Polling for Notification Counts
async function pollPiloNotifications() {
    try {
        const res = await fetch("{{ route('ai.widget.notifications') }}");
        const json = await res.json();
        if (json.success && json.data) {
            const count = json.data.unread_count || 0;
            const floatingBadge = document.getElementById('piloFloatingNotifBadge');
            const headerBadge = document.getElementById('piloHeaderNotifBadge');
            const navBadge = document.getElementById('piloNavNotificationBadge');

            if (count > 0) {
                if (floatingBadge) {
                    floatingBadge.innerText = count;
                    floatingBadge.classList.remove('d-none');
                }
                if (headerBadge) {
                    headerBadge.classList.remove('d-none');
                }
                if (navBadge) {
                    navBadge.innerText = count;
                    navBadge.classList.remove('d-none');
                }
            } else {
                if (floatingBadge) floatingBadge.classList.add('d-none');
                if (headerBadge) headerBadge.classList.add('d-none');
                if (navBadge) navBadge.classList.add('d-none');
            }
        }
    } catch(e) {
        // Silent catch for background poll
    }
}

document.addEventListener('DOMContentLoaded', () => {
    pollPiloNotifications();
    // Poll every 60 seconds
    setInterval(pollPiloNotifications, 60000);
});
</script>
@endif
