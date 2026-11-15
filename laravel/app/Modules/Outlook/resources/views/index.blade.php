@extends('layouts.app')

@section('content')
<div class="outlook-container">
    <!-- Outlook Top App Bar -->
    <div class="outlook-topbar">
        <div class="d-flex align-items-center gap-3">
            <div class="outlook-app-launcher" title="Microsoft 365 Apps">
                <i class="bi bi-grid-3x3-gap-fill fs-5 text-white"></i>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="outlook-brand-icon">
                    <i class="bi bi-envelope-fill"></i>
                </div>
                <span class="outlook-brand-text">Outlook</span>
            </div>
        </div>

        <!-- Universal Outlook Search -->
        <div class="outlook-search-wrapper">
            <i class="bi bi-search text-muted me-2"></i>
            <form action="{{ route('outlook.index') }}" method="GET" class="w-100 d-flex m-0">
                <input type="hidden" name="tab" value="{{ $currentTab }}">
                <input type="hidden" name="folder" value="{{ $currentFolder }}">
                <input type="text" name="search" class="outlook-search-input" 
                       placeholder="Search mail, people, and calendar..." 
                       value="{{ $search }}">
            </form>
            @if(!empty($search))
                <a href="{{ route('outlook.index', ['tab' => $currentTab, 'folder' => $currentFolder]) }}" class="text-secondary small ms-2" title="Clear Search">
                    <i class="bi bi-x-circle-fill"></i>
                </a>
            @endif
        </div>

        <!-- Right Quick Tools -->
        <div class="d-flex align-items-center gap-2 text-white">
            <button class="btn btn-sm btn-outline-light d-none d-md-inline-flex align-items-center gap-1 border-0" 
                    data-bs-toggle="modal" data-bs-target="#newComposeModal">
                <i class="bi bi-plus-circle me-1"></i> New
            </button>
            <a href="{{ route('outlook.index', ['tab' => 'calendar']) }}" class="btn btn-sm btn-icon text-white" title="Calendar">
                <i class="bi bi-calendar3"></i>
            </a>
            <a href="{{ route('outlook.index', ['tab' => 'todo']) }}" class="btn btn-sm btn-icon text-white" title="To Do">
                <i class="bi bi-check2-circle"></i>
            </a>
            <span class="badge bg-light text-dark ms-2 fw-normal px-2 py-1" style="font-size: 0.75rem;">
                <i class="bi bi-shield-check text-primary me-1"></i>Connected (TiDB)
            </span>
        </div>
    </div>

    <!-- Main Workspace -->
    <div class="outlook-workspace">
        <!-- 1. Left Vertical App Rail (Dock) -->
        <div class="outlook-rail">
            <a href="{{ route('outlook.index', ['tab' => 'mail']) }}" 
               class="outlook-rail-item {{ $currentTab === 'mail' ? 'active' : '' }}" title="Mail">
                <i class="bi bi-envelope"></i>
                @if($counts['inbox_unread'] > 0)
                    <span class="outlook-rail-badge">{{ $counts['inbox_unread'] }}</span>
                @endif
                <span class="rail-label">Mail</span>
            </a>
            <a href="{{ route('outlook.index', ['tab' => 'calendar']) }}" 
               class="outlook-rail-item {{ $currentTab === 'calendar' ? 'active' : '' }}" title="Calendar">
                <i class="bi bi-calendar3"></i>
                <span class="rail-label">Calendar</span>
            </a>
            <a href="{{ route('outlook.index', ['tab' => 'people']) }}" 
               class="outlook-rail-item {{ $currentTab === 'people' ? 'active' : '' }}" title="People / Contacts">
                <i class="bi bi-people"></i>
                <span class="rail-label">People</span>
            </a>
            <a href="{{ route('outlook.index', ['tab' => 'todo']) }}" 
               class="outlook-rail-item {{ $currentTab === 'todo' ? 'active' : '' }}" title="To Do">
                <i class="bi bi-check2-square"></i>
                <span class="rail-label">To Do</span>
            </a>
            <div class="mt-auto mb-2 text-center">
                <a href="{{ route('settings.index') }}" class="outlook-rail-item" title="Settings">
                    <i class="bi bi-gear"></i>
                </a>
            </div>
        </div>

        <!-- ======================= APP 1: MAIL ======================= -->
        @if($currentTab === 'mail')
        <!-- Mail Action Ribbon -->
        <div class="outlook-mail-body d-flex flex-column flex-grow-1 overflow-hidden">
            <div class="outlook-ribbon">
                <button class="btn btn-primary btn-sm px-3 fw-semibold d-flex align-items-center gap-1"
                        data-bs-toggle="modal" data-bs-target="#newComposeModal">
                    <i class="bi bi-pencil-square"></i> New mail
                </button>
                <div class="vr mx-2 text-secondary opacity-25"></div>
                @if($selectedMessage)
                    <form action="{{ route('outlook.mail.delete', $selectedMessage->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-light btn-sm text-secondary" title="Delete">
                            <i class="bi bi-trash3 me-1"></i> Delete
                        </button>
                    </form>
                    <button class="btn btn-light btn-sm text-secondary" onclick="toggleRead({{ $selectedMessage->id }})" title="Mark Read / Unread">
                        <i class="bi bi-envelope-open me-1"></i> Mark as unread
                    </button>
                    <button class="btn btn-light btn-sm text-secondary" onclick="toggleFlag({{ $selectedMessage->id }})" title="Flag message">
                        <i class="bi bi-flag me-1 text-danger"></i> Flag
                    </button>
                @endif
                <div class="ms-auto d-flex align-items-center gap-2">
                    <span class="text-muted small">Filter:</span>
                    <div class="btn-group btn-group-sm">
                        <a href="{{ route('outlook.index', ['tab' => 'mail', 'folder' => $currentFolder, 'filter' => 'all']) }}" 
                           class="btn btn-outline-secondary {{ $filter === 'all' ? 'active' : '' }}">All</a>
                        <a href="{{ route('outlook.index', ['tab' => 'mail', 'folder' => $currentFolder, 'filter' => 'unread']) }}" 
                           class="btn btn-outline-secondary {{ $filter === 'unread' ? 'active' : '' }}">Unread</a>
                        <a href="{{ route('outlook.index', ['tab' => 'mail', 'folder' => $currentFolder, 'filter' => 'flagged']) }}" 
                           class="btn btn-outline-secondary {{ $filter === 'flagged' ? 'active' : '' }}">Flagged</a>
                    </div>
                </div>
            </div>

            <!-- Mail Columns: Folders + List + Reading Pane -->
            <div class="d-flex flex-grow-1 overflow-hidden">
                <!-- 2. Folder Navigation Sidebar -->
                <div class="outlook-folder-sidebar">
                    <div class="px-3 pt-3 pb-2 text-uppercase text-muted fw-bold" style="font-size: 0.7rem; letter-spacing: 0.05em;">
                        Folders
                    </div>
                    <ul class="list-unstyled mb-0">
                        <li>
                            <a href="{{ route('outlook.index', ['folder' => 'inbox']) }}" 
                               class="outlook-folder-link {{ $currentFolder === 'inbox' ? 'active' : '' }}">
                                <i class="bi bi-inbox me-2"></i>
                                <span>Inbox</span>
                                @if($counts['inbox_unread'] > 0)
                                    <span class="badge rounded-pill bg-primary ms-auto" style="font-size: 0.7rem;">{{ $counts['inbox_unread'] }}</span>
                                @endif
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('outlook.index', ['folder' => 'sent']) }}" 
                               class="outlook-folder-link {{ $currentFolder === 'sent' ? 'active' : '' }}">
                                <i class="bi bi-send me-2"></i>
                                <span>Sent Items</span>
                                <span class="text-muted small ms-auto">{{ $counts['sent_total'] }}</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('outlook.index', ['folder' => 'drafts']) }}" 
                               class="outlook-folder-link {{ $currentFolder === 'drafts' ? 'active' : '' }}">
                                <i class="bi bi-file-earmark me-2"></i>
                                <span>Drafts</span>
                                <span class="text-muted small ms-auto">{{ $counts['drafts_total'] }}</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('outlook.index', ['folder' => 'archive']) }}" 
                               class="outlook-folder-link {{ $currentFolder === 'archive' ? 'active' : '' }}">
                                <i class="bi bi-archive me-2"></i>
                                <span>Archive</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('outlook.index', ['folder' => 'deleted']) }}" 
                               class="outlook-folder-link {{ $currentFolder === 'deleted' ? 'active' : '' }}">
                                <i class="bi bi-trash3 me-2"></i>
                                <span>Deleted Items</span>
                            </a>
                        </li>
                    </ul>

                    <div class="px-3 pt-4 pb-2 text-uppercase text-muted fw-bold" style="font-size: 0.7rem; letter-spacing: 0.05em;">
                        Smart Job Categories
                    </div>
                    <ul class="list-unstyled mb-0">
                        <li>
                            <a href="{{ route('outlook.index', ['folder' => 'inbox', 'search' => 'Interview']) }}" class="outlook-folder-link">
                                <span class="category-dot bg-primary me-2"></span>
                                <span>Interviews & Screens</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('outlook.index', ['folder' => 'inbox', 'search' => 'Recruiter']) }}" class="outlook-folder-link">
                                <span class="category-dot bg-success me-2"></span>
                                <span>Recruiter Inquiries</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('outlook.index', ['folder' => 'inbox', 'search' => 'Offer']) }}" class="outlook-folder-link">
                                <span class="category-dot bg-warning me-2"></span>
                                <span>Offers & Negotiations</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- 3. Message List Column -->
                <div class="outlook-messagelist-pane">
                    <!-- Focused / Other Tabs -->
                    <div class="outlook-tab-toggle px-3 pt-2 pb-1 border-bottom d-flex gap-3">
                        <a href="{{ route('outlook.index', ['folder' => $currentFolder, 'filter' => 'focused']) }}" 
                           class="tab-sublink {{ $filter === 'focused' || $filter === 'all' ? 'active' : '' }}">
                            Focused
                        </a>
                        <a href="{{ route('outlook.index', ['folder' => $currentFolder, 'filter' => 'other']) }}" 
                           class="tab-sublink {{ $filter === 'other' ? 'active' : '' }}">
                            Other
                        </a>
                    </div>

                    <!-- Message Items -->
                    <div class="outlook-messages-scroll">
                        @forelse($messages as $msg)
                            <div class="outlook-msg-item {{ !$msg->is_read ? 'unread' : '' }} {{ $selectedMessage && $selectedMessage->id === $msg->id ? 'selected' : '' }}"
                                 onclick="window.location='{{ route('outlook.index', ['folder' => $currentFolder, 'message_id' => $msg->id]) }}'">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <div class="d-flex align-items-center gap-2 overflow-hidden">
                                        @if(!$msg->is_read)
                                            <span class="unread-blue-dot"></span>
                                        @endif
                                        <span class="msg-sender-name text-truncate {{ !$msg->is_read ? 'fw-bold text-dark' : 'text-secondary' }}">
                                            {{ $msg->sender_name ?: $msg->sender_email }}
                                        </span>
                                    </div>
                                    <span class="msg-date text-muted" style="font-size: 0.72rem;">
                                        {{ $msg->received_at->format('M j') }}
                                    </span>
                                </div>
                                <div class="msg-subject text-truncate mb-1 {{ !$msg->is_read ? 'fw-semibold text-dark' : 'text-secondary' }}">
                                    {{ $msg->subject }}
                                </div>
                                <div class="msg-snippet text-truncate text-muted small">
                                    {{ $msg->snippet }}
                                </div>
                                <div class="d-flex align-items-center justify-content-between mt-2 pt-1 border-top border-light">
                                    <span class="badge rounded-pill bg-light text-secondary border" style="font-size: 0.65rem;">
                                        {{ $msg->category }}
                                    </span>
                                    <div class="msg-hover-actions">
                                        @if($msg->is_flagged)
                                            <i class="bi bi-flag-fill text-danger" title="Flagged"></i>
                                        @endif
                                        @if($msg->has_attachments)
                                            <i class="bi bi-paperclip text-muted ms-1" title="Has attachment"></i>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                <h6>No messages in {{ ucfirst($currentFolder) }}</h6>
                                <p class="small text-muted">Select another folder or clear your search query.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- 4. Reading Pane -->
                <div class="outlook-reading-pane flex-grow-1">
                    @if($selectedMessage)
                        <!-- Message Header -->
                        <div class="reading-header p-4 border-bottom bg-white">
                            <div class="d-flex align-items-start justify-content-between">
                                <h5 class="fw-bold text-dark mb-2 reading-subject">
                                    {{ $selectedMessage->subject }}
                                </h5>
                                <div class="d-flex align-items-center gap-2">
                                    <!-- AI Reply Trigger -->
                                    <button class="btn btn-sm btn-outline-primary fw-semibold d-flex align-items-center gap-1"
                                            onclick="openAIReplyModal({{ $selectedMessage->id }})">
                                        <i class="bi bi-stars"></i> ✨ AI Smart Reply
                                    </button>
                                    <button class="btn btn-sm btn-light border" title="Reply" 
                                            onclick="openReplyCompose('{{ addslashes($selectedMessage->sender_email) }}', '{{ addslashes($selectedMessage->subject) }}')">
                                        <i class="bi bi-reply-fill"></i> Reply
                                    </button>
                                </div>
                            </div>

                            <div class="d-flex align-items-center justify-content-between mt-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="outlook-avatar">
                                        {{ strtoupper(substr($selectedMessage->sender_name ?: $selectedMessage->sender_email, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">{{ $selectedMessage->sender_name }}</div>
                                        <div class="text-muted small">
                                            &lt;{{ $selectedMessage->sender_email }}&gt; 
                                            <span class="ms-2 text-secondary">&bull; To: {{ $selectedMessage->recipient_email }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end text-muted small">
                                    <div>{{ $selectedMessage->received_at->format('D, M j, Y g:i A') }}</div>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle mt-1" style="font-size: 0.7rem;">
                                        {{ $selectedMessage->category }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Message Body Content -->
                        <div class="reading-body p-4 overflow-auto bg-white" style="min-height: 400px;">
                            {!! $selectedMessage->body_html !!}
                        </div>

                        <!-- Quick Reply Bottom Bar -->
                        <div class="reading-bottom-actions p-3 bg-light border-top d-flex gap-2">
                            <button class="btn btn-sm btn-white border px-3" 
                                    onclick="openReplyCompose('{{ addslashes($selectedMessage->sender_email) }}', '{{ addslashes($selectedMessage->subject) }}')">
                                <i class="bi bi-reply me-1"></i> Reply
                            </button>
                            <button class="btn btn-sm btn-white border px-3" 
                                    onclick="openReplyCompose('{{ addslashes($selectedMessage->sender_email) }}', 'Fwd: {{ addslashes($selectedMessage->subject) }}')">
                                <i class="bi bi-forward me-1"></i> Forward
                            </button>
                            <button class="btn btn-sm btn-outline-success border px-3 ms-auto"
                                    data-bs-toggle="modal" data-bs-target="#newCalendarModal"
                                    onclick="document.getElementById('eventTitle').value = 'Interview with {{ addslashes($selectedMessage->sender_name) }}'">
                                <i class="bi bi-calendar-plus me-1"></i> Schedule on Calendar
                            </button>
                        </div>
                    @else
                        <div class="h-100 d-flex flex-column align-items-center justify-content-center text-muted p-5">
                            <i class="bi bi-envelope-open fs-1 text-secondary opacity-50 mb-3"></i>
                            <h5>Select an item to read</h5>
                            <p class="small text-secondary">Click on any email in the message list to view its contents.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        <!-- ======================= APP 2: CALENDAR ======================= -->
        @if($currentTab === 'calendar')
        <div class="outlook-calendar-view flex-grow-1 d-flex flex-column overflow-hidden bg-white">
            <!-- Calendar Ribbon -->
            <div class="outlook-ribbon">
                <button class="btn btn-primary btn-sm px-3 fw-semibold d-flex align-items-center gap-1"
                        data-bs-toggle="modal" data-bs-target="#newCalendarModal">
                    <i class="bi bi-calendar-plus"></i> New event
                </button>
                <div class="vr mx-2 text-secondary opacity-25"></div>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-light active">Month</button>
                    <button class="btn btn-light">Week</button>
                    <button class="btn btn-light">Work Week</button>
                    <button class="btn btn-light">Day</button>
                </div>
                <div class="ms-auto d-flex align-items-center gap-3">
                    <span class="fw-bold fs-6 text-dark">{{ Carbon\Carbon::now()->format('F Y') }}</span>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-light border"><i class="bi bi-chevron-left"></i></button>
                        <button class="btn btn-light border"><i class="bi bi-chevron-right"></i></button>
                    </div>
                </div>
            </div>

            <!-- Calendar Content -->
            <div class="p-4 overflow-auto flex-grow-1">
                <div class="row g-4">
                    <!-- Left: Mini Calendar + Upcoming Agenda -->
                    <div class="col-lg-4 col-12">
                        <div class="card border shadow-sm rounded-3 mb-4">
                            <div class="card-header bg-white fw-bold py-2 border-0">
                                <i class="bi bi-clock-history text-primary me-2"></i>Upcoming Interviews & Syncs
                            </div>
                            <div class="list-group list-group-flush">
                                @forelse($events as $event)
                                    <div class="list-group-item p-3">
                                        <div class="d-flex align-items-start justify-content-between">
                                            <div>
                                                <span class="badge mb-1 text-white" style="background-color: {{ $event->color }}; font-size: 0.68rem;">
                                                    {{ $event->category }}
                                                </span>
                                                <h6 class="fw-bold text-dark mb-1">{{ $event->title }}</h6>
                                                <div class="text-muted small">
                                                    <i class="bi bi-calendar-event me-1"></i>{{ $event->start_time->format('M j, Y — g:i A') }}
                                                </div>
                                                @if($event->meeting_link)
                                                    <div class="mt-2">
                                                        <a href="{{ $event->meeting_link }}" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size: 0.75rem;">
                                                            <i class="bi bi-camera-video me-1"></i>Join Video Call
                                                        </a>
                                                    </div>
                                                @endif
                                            </div>
                                            <form action="{{ route('outlook.calendar.delete', $event->id) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="Delete Event">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @empty
                                    <div class="p-4 text-center text-muted small">
                                        No scheduled events. Click "+ New event" above to schedule an interview.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- Right: Visual Month Grid Preview -->
                    <div class="col-lg-8 col-12">
                        <div class="card border shadow-sm rounded-3 p-3">
                            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                                <h6 class="fw-bold text-dark mb-0">Interview Calendar Matrix</h6>
                                <span class="badge bg-light text-secondary border">Real-Time Sync</span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered text-center align-middle mb-0" style="font-size: 0.85rem;">
                                    <thead class="bg-light text-muted">
                                        <tr>
                                            <th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @for($week = 0; $week < 4; $week++)
                                            <tr style="height: 90px;">
                                                @for($day = 1; $day <= 7; $day++)
                                                    @php $currentDayNum = ($week * 7 + $day); @endphp
                                                    <td class="text-start p-2 align-top position-relative {{ $currentDayNum === 18 ? 'bg-primary-subtle bg-opacity-25' : '' }}">
                                                        <span class="fw-bold text-secondary" style="font-size: 0.75rem;">{{ $currentDayNum <= 30 ? $currentDayNum : '' }}</span>
                                                        @if($currentDayNum === 18)
                                                            <div class="mt-1 p-1 rounded small text-white" style="background-color: #0078d4; font-size: 0.65rem; line-height: 1.2;">
                                                                <strong>Google</strong> Tech Screen
                                                            </div>
                                                        @elseif($currentDayNum === 19)
                                                            <div class="mt-1 p-1 rounded small text-white" style="background-color: #107c41; font-size: 0.65rem; line-height: 1.2;">
                                                                <strong>Stripe</strong> Intro Call
                                                            </div>
                                                        @endif
                                                    </td>
                                                @endfor
                                            </tr>
                                        @endfor
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- ======================= APP 3: PEOPLE (CONTACTS) ======================= -->
        @if($currentTab === 'people')
        <div class="outlook-people-view flex-grow-1 d-flex flex-column overflow-hidden bg-white">
            <div class="outlook-ribbon">
                <button class="btn btn-primary btn-sm px-3 fw-semibold d-flex align-items-center gap-1"
                        data-bs-toggle="modal" data-bs-target="#newContactModal">
                    <i class="bi bi-person-plus"></i> New contact
                </button>
                <div class="vr mx-2 text-secondary opacity-25"></div>
                <span class="text-muted small">Showing {{ count($contacts) }} Recruiter & Interview Contacts</span>
            </div>

            <div class="p-4 overflow-auto flex-grow-1">
                <div class="row g-4">
                    @forelse($contacts as $contact)
                        <div class="col-xl-4 col-md-6 col-12">
                            <div class="card h-100 border shadow-sm rounded-3 p-3">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="outlook-avatar-lg">
                                        {{ strtoupper(substr($contact->name, 0, 2)) }}
                                    </div>
                                    <div class="overflow-hidden flex-grow-1">
                                        <h6 class="fw-bold text-dark mb-0">{{ $contact->name }}</h6>
                                        <div class="text-primary small fw-semibold">{{ $contact->job_title ?: 'Recruiter' }}</div>
                                        <div class="text-muted small mb-2">{{ $contact->company }}</div>
                                        <div class="small text-secondary mb-1">
                                            <i class="bi bi-envelope me-1"></i> <a href="mailto:{{ $contact->email }}" class="text-decoration-none">{{ $contact->email }}</a>
                                        </div>
                                        @if($contact->phone)
                                            <div class="small text-secondary mb-1">
                                                <i class="bi bi-telephone me-1"></i> {{ $contact->phone }}
                                            </div>
                                        @endif
                                    </div>
                                    <form action="{{ route('outlook.people.delete', $contact->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                                @if($contact->notes)
                                    <div class="mt-3 p-2 bg-light rounded small text-secondary border">
                                        <i class="bi bi-info-circle me-1"></i> {{ $contact->notes }}
                                    </div>
                                @endif
                                <div class="mt-3 pt-2 border-top d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-primary w-100" 
                                            onclick="openDirectCompose('{{ addslashes($contact->email) }}', 'Discussion regarding {{ addslashes($contact->company) }} engineering roles')">
                                        <i class="bi bi-send me-1"></i> Send Mail
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-people fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            <h6>No contacts yet</h6>
                            <p class="small text-muted">Click "+ New contact" to save recruiter details.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
        @endif

        <!-- ======================= APP 4: TO DO (TASKS) ======================= -->
        @if($currentTab === 'todo')
        <div class="outlook-todo-view flex-grow-1 d-flex flex-column overflow-hidden bg-white">
            <div class="p-4 overflow-auto flex-grow-1" style="max-width: 900px; margin: 0 auto; width: 100%;">
                <div class="d-flex align-items-center justify-content-between mb-4 border-bottom pb-3">
                    <div>
                        <h4 class="fw-bold text-dark mb-1">
                            <i class="bi bi-sun text-warning me-2"></i>My Day
                        </h4>
                        <div class="text-muted small">{{ Carbon\Carbon::now()->format('l, F j, Y') }}</div>
                    </div>
                    <span class="badge bg-primary px-3 py-2 rounded-pill">
                        {{ $tasks->where('is_completed', false)->count() }} Tasks Remaining
                    </span>
                </div>

                <!-- Add Task Inline Bar -->
                <form action="{{ route('outlook.todo.store') }}" method="POST" class="card shadow-sm border p-2 mb-4 rounded-3">
                    @csrf
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-plus-lg text-primary fs-5 ms-2"></i>
                        <input type="text" name="title" class="form-control border-0 shadow-none" 
                               placeholder="Add a task for your job search (e.g. Prepare system design for Google interview)..." required>
                        <input type="date" name="due_date" class="form-control form-control-sm border-0 text-muted" style="max-width: 150px;">
                        <button type="submit" class="btn btn-primary btn-sm px-3 fw-semibold">Add</button>
                    </div>
                </form>

                <!-- Task List Items -->
                <div class="card border shadow-sm rounded-3 overflow-hidden">
                    <div class="list-group list-group-flush">
                        @forelse($tasks as $task)
                            <div class="list-group-item d-flex align-items-center justify-content-between p-3 {{ $task->is_completed ? 'bg-light' : '' }}">
                                <div class="d-flex align-items-center gap-3 flex-grow-1">
                                    <input type="checkbox" class="form-check-input mt-0 fs-5" 
                                           {{ $task->is_completed ? 'checked' : '' }}
                                           onchange="toggleTask({{ $task->id }})">
                                    <div>
                                        <div class="fw-semibold {{ $task->is_completed ? 'text-decoration-line-through text-muted' : 'text-dark' }}">
                                            {{ $task->title }}
                                        </div>
                                        @if($task->due_date)
                                            <div class="small text-danger">
                                                <i class="bi bi-calendar-event me-1"></i>Due {{ $task->due_date->format('M j') }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <button class="btn btn-sm btn-link text-warning p-0" onclick="toggleTaskImportant({{ $task->id }})" title="Mark Important">
                                        <i class="bi {{ $task->is_important ? 'bi-star-fill' : 'bi-star' }} fs-5"></i>
                                    </button>
                                    <form action="{{ route('outlook.todo.delete', $task->id) }}" method="POST" class="m-0">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-link text-secondary p-0" title="Delete">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-check2-circle fs-1 text-success d-block mb-2"></i>
                                <h6>All caught up!</h6>
                                <p class="small text-muted">No pending tasks for today.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- ======================= MODAL: COMPOSE MAIL ======================= -->
<div class="modal fade" id="newComposeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white py-2 px-3">
                <h6 class="modal-title fw-bold" id="composeModalTitle">New Message</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('outlook.mail.send') }}" method="POST">
                @csrf
                <div class="modal-body p-3">
                    <div class="mb-2">
                        <label class="small text-muted fw-semibold">To</label>
                        <input type="email" name="to" id="composeTo" class="form-control form-control-sm border-0 border-bottom rounded-0" placeholder="recruiter@company.com" required>
                    </div>
                    <div class="mb-2">
                        <label class="small text-muted fw-semibold">Subject</label>
                        <input type="text" name="subject" id="composeSubject" class="form-control form-control-sm border-0 border-bottom rounded-0" placeholder="Subject line..." required>
                    </div>
                    <div class="mt-3">
                        <textarea name="body" id="composeBody" rows="10" class="form-control border rounded-3 p-3" 
                                  placeholder="Write your email here..." required style="font-family: inherit; font-size: 0.95rem;"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-3 border-0 d-flex justify-content-between">
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-secondary me-1" title="Attach file">
                            <i class="bi bi-paperclip"></i> Attach CV
                        </button>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Discard</button>
                        <button type="submit" class="btn btn-sm btn-primary px-4 fw-semibold">
                            <i class="bi bi-send-fill me-1"></i> Send
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ======================= MODAL: AI SMART REPLY ======================= -->
<div class="modal fade" id="aiReplyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-gradient text-white py-2 px-3" style="background: linear-gradient(135deg, #0078d4, #106ebe);">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-stars fs-5"></i>
                    <h6 class="modal-title fw-bold">✨ AI Recruiter Response Assistant</h6>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex gap-2 mb-3">
                    <button class="btn btn-sm btn-outline-primary active" id="btnToneAccept" onclick="refreshAIDraft('accept')">
                        Accept Interview Invitation
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" id="btnToneReschedule" onclick="refreshAIDraft('reschedule')">
                        Request Reschedule
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" id="btnToneInfo" onclick="refreshAIDraft('info')">
                        Inquire for More Details
                    </button>
                </div>

                <div class="p-3 bg-light rounded border">
                    <div class="small fw-semibold text-muted mb-1">Generated Response Preview:</div>
                    <textarea id="aiDraftOutput" class="form-control bg-white" rows="8" style="font-size: 0.95rem;"></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-3 border-0">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary px-4 fw-semibold" onclick="useAIDraftInCompose()">
                    Use This Draft in Composer &rarr;
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ======================= MODAL: NEW CALENDAR EVENT ======================= -->
<div class="modal fade" id="newCalendarModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white py-2 px-3">
                <h6 class="modal-title fw-bold">Schedule Event / Interview</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('outlook.calendar.store') }}" method="POST">
                @csrf
                <div class="modal-body p-3">
                    <div class="mb-3">
                        <label class="small text-muted fw-semibold">Event Title</label>
                        <input type="text" name="title" id="eventTitle" class="form-control form-control-sm" placeholder="e.g. Google Cloud Tech Screen" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="small text-muted fw-semibold">Start Time</label>
                            <input type="datetime-local" name="start_time" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-6">
                            <label class="small text-muted fw-semibold">End Time</label>
                            <input type="datetime-local" name="end_time" class="form-control form-control-sm" required>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="small text-muted fw-semibold">Category</label>
                            <select name="category" class="form-select form-select-sm">
                                <option value="Interview">Interview</option>
                                <option value="Call">Recruiter Call</option>
                                <option value="Deadline">Deadline</option>
                                <option value="FollowUp">Follow-up</option>
                                <option value="Personal">Personal</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="small text-muted fw-semibold">Company Name</label>
                            <input type="text" name="company_name" class="form-control form-control-sm" placeholder="e.g. Google">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="small text-muted fw-semibold">Video Meeting URL (Google Meet / Teams / Zoom)</label>
                        <input type="url" name="meeting_link" class="form-control form-control-sm" placeholder="https://meet.google.com/...">
                    </div>
                    <div>
                        <label class="small text-muted fw-semibold">Notes / Description</label>
                        <textarea name="description" rows="3" class="form-control form-control-sm" placeholder="Topics to cover, interviewer names..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-3 border-0">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary px-3 fw-semibold">Save Event</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ======================= MODAL: NEW CONTACT ======================= -->
<div class="modal fade" id="newContactModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white py-2 px-3">
                <h6 class="modal-title fw-bold">New Recruiter Contact</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('outlook.people.store') }}" method="POST">
                @csrf
                <div class="modal-body p-3">
                    <div class="mb-2">
                        <label class="small text-muted fw-semibold">Full Name</label>
                        <input type="text" name="name" class="form-control form-control-sm" placeholder="Sarah Jenkins" required>
                    </div>
                    <div class="mb-2">
                        <label class="small text-muted fw-semibold">Email Address</label>
                        <input type="email" name="email" class="form-control form-control-sm" placeholder="sarah.jenkins@company.com" required>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="small text-muted fw-semibold">Company</label>
                            <input type="text" name="company" class="form-control form-control-sm" placeholder="Google">
                        </div>
                        <div class="col-6">
                            <label class="small text-muted fw-semibold">Job Title</label>
                            <input type="text" name="job_title" class="form-control form-control-sm" placeholder="Talent Lead">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="small text-muted fw-semibold">Phone (Optional)</label>
                        <input type="text" name="phone" class="form-control form-control-sm" placeholder="+1 ...">
                    </div>
                    <div>
                        <label class="small text-muted fw-semibold">Notes</label>
                        <textarea name="notes" rows="2" class="form-control form-control-sm" placeholder="Recruiting preferences, interview focus..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-3 border-0">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary px-3 fw-semibold">Save Contact</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Authentic Microsoft Outlook Web Styling */
.outlook-container {
    display: flex;
    flex-direction: column;
    height: calc(100vh - 75px);
    background: #ffffff;
    font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, sans-serif;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.outlook-topbar {
    height: 48px;
    background: #0078d4;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 16px;
    flex-shrink: 0;
}

.outlook-brand-icon {
    width: 24px;
    height: 24px;
    background: #ffffff;
    color: #0078d4;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
}

.outlook-brand-text {
    color: #ffffff;
    font-weight: 700;
    font-size: 1.05rem;
    letter-spacing: -0.02em;
}

.outlook-search-wrapper {
    background: #ffffff;
    border-radius: 4px;
    display: flex;
    align-items: center;
    padding: 4px 12px;
    width: 450px;
    max-width: 45%;
}

.outlook-search-input {
    border: none;
    outline: none;
    width: 100%;
    font-size: 0.85rem;
}

.outlook-workspace {
    display: flex;
    flex-grow: 1;
    overflow: hidden;
}

/* 1. Left Vertical App Rail */
.outlook-rail {
    width: 50px;
    background: #f3f4f6;
    border-right: 1px solid #e5e7eb;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding-top: 8px;
    flex-shrink: 0;
}

.outlook-rail-item {
    width: 40px;
    height: 42px;
    border-radius: 6px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #4b5563;
    text-decoration: none;
    margin-bottom: 8px;
    position: relative;
    font-size: 1.15rem;
    transition: all 0.15s ease;
}

.outlook-rail-item:hover {
    background: #e5e7eb;
    color: #111827;
}

.outlook-rail-item.active {
    background: #ffffff;
    color: #0078d4;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.rail-label {
    font-size: 0.6rem;
    margin-top: -2px;
    font-weight: 600;
}

.outlook-rail-badge {
    position: absolute;
    top: 2px;
    right: 4px;
    background: #0078d4;
    color: #ffffff;
    font-size: 0.6rem;
    padding: 0 4px;
    border-radius: 10px;
    font-weight: bold;
}

/* Ribbon */
.outlook-ribbon {
    height: 44px;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    padding: 0 16px;
    flex-shrink: 0;
}

/* 2. Folder Navigation Sidebar */
.outlook-folder-sidebar {
    width: 200px;
    background: #f9fafb;
    border-right: 1px solid #e5e7eb;
    overflow-y: auto;
    flex-shrink: 0;
}

.outlook-folder-link {
    display: flex;
    align-items: center;
    padding: 8px 16px;
    color: #374151;
    text-decoration: none;
    font-size: 0.85rem;
    border-radius: 0;
    transition: background 0.1s;
}

.outlook-folder-link:hover {
    background: #e5e7eb;
    color: #111827;
}

.outlook-folder-link.active {
    background: #e0f2fe;
    color: #0078d4;
    font-weight: 600;
}

.category-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
}

/* 3. Message List Column */
.outlook-messagelist-pane {
    width: 340px;
    background: #ffffff;
    border-right: 1px solid #e5e7eb;
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
}

.tab-sublink {
    color: #6b7280;
    text-decoration: none;
    font-size: 0.82rem;
    font-weight: 600;
    padding-bottom: 6px;
    border-bottom: 2px solid transparent;
}

.tab-sublink.active {
    color: #0078d4;
    border-bottom-color: #0078d4;
}

.outlook-messages-scroll {
    overflow-y: auto;
    flex-grow: 1;
}

.outlook-msg-item {
    padding: 12px 16px;
    border-bottom: 1px solid #f3f4f6;
    cursor: pointer;
    transition: background 0.1s;
    position: relative;
}

.outlook-msg-item:hover {
    background: #f8fafc;
}

.outlook-msg-item.selected {
    background: #eff6ff;
    border-left: 3px solid #0078d4;
}

.unread-blue-dot {
    width: 7px;
    height: 7px;
    background: #0078d4;
    border-radius: 50%;
    display: inline-block;
    flex-shrink: 0;
}

/* 4. Reading Pane */
.outlook-reading-pane {
    background: #ffffff;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
}

.outlook-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #0078d4;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 0.85rem;
}

.outlook-avatar-lg {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0078d4, #106ebe);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.1rem;
    flex-shrink: 0;
}
</style>

<script>
let currentActiveMessageId = null;

function toggleFlag(id) {
    fetch(`/outlook/mail/${id}/flag`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    }).then(r => r.json()).then(data => {
        if(data.success) {
            location.reload();
        }
    });
}

function toggleRead(id) {
    fetch(`/outlook/mail/${id}/read`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    }).then(r => r.json()).then(data => {
        if(data.success) {
            location.reload();
        }
    });
}

function toggleTask(id) {
    fetch(`/outlook/todo/tasks/${id}/toggle`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    }).then(r => r.json()).then(data => {
        if(data.success) {
            location.reload();
        }
    });
}

function toggleTaskImportant(id) {
    fetch(`/outlook/todo/tasks/${id}/important`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    }).then(r => r.json()).then(data => {
        if(data.success) {
            location.reload();
        }
    });
}

function openReplyCompose(toEmail, subject) {
    document.getElementById('composeTo').value = toEmail;
    document.getElementById('composeSubject').value = subject.startsWith('Re:') ? subject : 'Re: ' + subject;
    document.getElementById('composeBody').value = '\n\n---\nOn {{ Carbon\Carbon::now()->format("M j, Y") }}, recruiter wrote:';
    new bootstrap.Modal(document.getElementById('newComposeModal')).show();
}

function openDirectCompose(toEmail, subject) {
    document.getElementById('composeTo').value = toEmail;
    document.getElementById('composeSubject').value = subject;
    document.getElementById('composeBody').value = '';
    new bootstrap.Modal(document.getElementById('newComposeModal')).show();
}

function openAIReplyModal(messageId) {
    currentActiveMessageId = messageId;
    refreshAIDraft('accept');
    new bootstrap.Modal(document.getElementById('aiReplyModal')).show();
}

function refreshAIDraft(tone) {
    if(!currentActiveMessageId) return;
    
    // Update active button state
    ['btnToneAccept', 'btnToneReschedule', 'btnToneInfo'].forEach(id => {
        document.getElementById(id).classList.remove('active', 'btn-primary');
        document.getElementById(id).classList.add('btn-outline-secondary');
    });

    if(tone === 'accept') {
        document.getElementById('btnToneAccept').classList.add('active', 'btn-primary');
        document.getElementById('btnToneAccept').classList.remove('btn-outline-secondary');
    } else if(tone === 'reschedule') {
        document.getElementById('btnToneReschedule').classList.add('active', 'btn-primary');
        document.getElementById('btnToneReschedule').classList.remove('btn-outline-secondary');
    } else {
        document.getElementById('btnToneInfo').classList.add('active', 'btn-primary');
        document.getElementById('btnToneInfo').classList.remove('btn-outline-secondary');
    }

    document.getElementById('aiDraftOutput').value = 'Generating smart response with JobPilot AI...';

    fetch(`/outlook/mail/${currentActiveMessageId}/ai-reply?tone=${tone}`, {
        headers: { 'Accept': 'application/json' }
    }).then(r => r.json()).then(data => {
        if(data.success) {
            document.getElementById('aiDraftOutput').value = data.draft.body;
            document.getElementById('composeTo').value = data.draft.to;
            document.getElementById('composeSubject').value = data.draft.subject;
        }
    });
}

function useAIDraftInCompose() {
    const draftText = document.getElementById('aiDraftOutput').value;
    document.getElementById('composeBody').value = draftText;
    bootstrap.Modal.getInstance(document.getElementById('aiReplyModal')).hide();
    new bootstrap.Modal(document.getElementById('newComposeModal')).show();
}
</script>
@endsection
