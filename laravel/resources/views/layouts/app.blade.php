<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — JobPilot AI</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --sidebar-width: 280px;
            --brand-primary: #0f172a;
            --brand-secondary: #1e293b;
            --brand-accent: #2563eb;
            --brand-success: #10b981;
            --brand-warning: #f59e0b;
        }
        body {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        /* Full App Shell */
        .app-shell {
            display: flex;
            min-height: 100vh;
            min-height: 100dvh;
            width: 100%;
        }

        /* Desktop Sticky Sidebar */
        .app-sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            height: 100dvh;
            position: sticky;
            top: 0;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            overflow: hidden;
            background: #0f172a;
            color: #94a3b8;
            border-right: 1px solid #1e293b;
            z-index: 1020;
        }

        /* Brand Area (Fixed Header) */
        .sidebar-brand {
            flex-shrink: 0;
            padding: 1.15rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #1e293b;
            background: #0f172a;
        }
        .sidebar-brand a {
            color: #ffffff;
            text-decoration: none;
            font-size: 1.15rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.65rem;
        }
        .sidebar-brand a i {
            color: #38bdf8;
            font-size: 1.35rem;
        }

        /* Search / Filter Area */
        .sidebar-search-box {
            flex-shrink: 0;
            padding: 0.65rem 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            background: #0b1324;
        }
        .sidebar-search-input {
            background-color: #1e293b !important;
            border: 1px solid #334155 !important;
            color: #f1f5f9 !important;
            font-size: 0.8rem;
            border-radius: 6px;
            padding: 0.35rem 0.65rem;
        }
        .sidebar-search-input::placeholder {
            color: #64748b;
        }
        .sidebar-search-input:focus {
            box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.3);
            border-color: #38bdf8 !important;
        }

        /* Independently Scrollable Navigation Area */
        .sidebar-navigation {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            overscroll-behavior: contain;
            scrollbar-gutter: stable;
            padding: 0.5rem 0.75rem;
        }

        /* Slim Scrollbar */
        .sidebar-navigation::-webkit-scrollbar,
        .offcanvas-body::-webkit-scrollbar {
            width: 5px;
        }
        .sidebar-navigation::-webkit-scrollbar-track,
        .offcanvas-body::-webkit-scrollbar-track {
            background: transparent;
        }
        .sidebar-navigation::-webkit-scrollbar-thumb,
        .offcanvas-body::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 9999px;
        }
        .sidebar-navigation::-webkit-scrollbar-thumb:hover,
        .offcanvas-body::-webkit-scrollbar-thumb:hover {
            background: #475569;
        }

        /* Collapsible Navigation Groups */
        .nav-group {
            margin-bottom: 0.4rem;
        }
        .nav-group-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            padding: 0.55rem 0.75rem;
            background: transparent;
            border: none;
            color: #94a3b8;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.15s ease;
            text-align: left;
        }
        .nav-group-header:hover {
            color: #f1f5f9;
            background: #1e293b;
        }
        .nav-group-header .group-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .nav-group-header .group-title i {
            font-size: 0.9rem;
            color: #64748b;
        }
        .nav-group-header:hover .group-title i {
            color: #38bdf8;
        }
        .nav-group-header .chevron-icon {
            font-size: 0.75rem;
            transition: transform 0.2s ease;
        }
        .nav-group-header[aria-expanded="true"] .chevron-icon {
            transform: rotate(180deg);
        }
        .nav-group-header[aria-expanded="true"] {
            color: #e2e8f0;
        }

        /* Nav Links */
        .nav-sub-list {
            list-style: none;
            padding: 0.2rem 0 0.35rem 0.5rem;
            margin: 0;
        }
        .nav-sub-item {
            margin-bottom: 0.15rem;
        }
        .nav-link-item {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            padding: 0.45rem 0.75rem;
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.84rem;
            font-weight: 500;
            border-radius: 6px;
            transition: all 0.15s ease;
        }
        .nav-link-item:hover {
            color: #ffffff;
            background: #1e293b;
        }
        .nav-link-item.active {
            color: #ffffff;
            background: #2563eb;
            font-weight: 600;
            box-shadow: 0 1px 3px rgba(37, 99, 235, 0.3);
        }
        .nav-link-item i {
            font-size: 1rem;
            width: 18px;
            text-align: center;
            flex-shrink: 0;
            color: #64748b;
        }
        .nav-link-item:hover i,
        .nav-link-item.active i {
            color: #ffffff;
        }

        /* Fixed Settings Area */
        .sidebar-settings {
            flex-shrink: 0;
            border-top: 1px solid #1e293b;
            padding: 0.5rem 0.75rem;
            background: #0b1324;
        }
        .sidebar-settings-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.5rem 0.75rem;
            color: #cbd5e1;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            border-radius: 6px;
            transition: all 0.15s ease;
        }
        .sidebar-settings-link:hover {
            color: #ffffff;
            background: #1e293b;
        }
        .sidebar-settings-link.active {
            color: #ffffff;
            background: #2563eb;
        }

        /* Fixed Footer User Profile Area */
        .sidebar-footer {
            flex-shrink: 0;
            border-top: 1px solid #1e293b;
            padding: 0.75rem 1rem;
            background: #090e1a;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .user-profile-badge {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            text-decoration: none;
            color: #e2e8f0;
            overflow: hidden;
        }
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #2563eb;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.8rem;
            flex-shrink: 0;
        }
        .user-info {
            display: flex;
            flex-direction: column;
            overflow: hidden;
            line-height: 1.2;
        }
        .user-name {
            font-size: 0.82rem;
            font-weight: 600;
            color: #ffffff;
            white-space: nowrap;
            text-overflow: ellipsis;
            overflow: hidden;
        }
        .user-role {
            font-size: 0.68rem;
            color: #64748b;
        }

        /* Main Wrapper */
        .app-main {
            flex: 1 1 auto;
            min-width: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-color: #f8fafc;
        }
        .top-navbar {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 900;
        }
        .content-area {
            padding: 1.5rem;
            flex: 1;
        }

        /* Policy & Status Badges */
        .badge-free-policy {
            background-color: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
            font-size: 0.75rem;
            padding: 0.35rem 0.65rem;
            border-radius: 9999px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }
        .stat-card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            padding: 1.25rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
            transition: transform 0.15s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        /* Offcanvas Drawer for Mobile */
        .offcanvas.offcanvas-start {
            width: 280px;
            background: #0f172a;
            border-right: 1px solid #1e293b;
        }

        /* Quick Filter Match Highlighting */
        .nav-item-hidden {
            display: none !important;
        }

        /* Responsive Breakpoints */
        @media (max-width: 991.98px) {
            .app-sidebar {
                display: none !important;
            }
            .content-area {
                padding: 1rem;
            }
            .top-navbar {
                padding: 0.65rem 1rem;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="app-shell">
        <!-- Desktop Sticky App Sidebar -->
        <aside class="app-sidebar d-none d-lg-flex" id="desktopSidebar" aria-label="Main Sidebar Navigation">
            <!-- 1. Fixed Brand Area -->
            <div class="sidebar-brand">
                <a href="{{ url('/') }}" title="JobPilot AI Homepage">
                    <i class="bi bi-send-check-fill"></i>
                    <span>JobPilot AI</span>
                </a>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill font-monospace" style="font-size: 0.65rem;">v4.0</span>
            </div>

            <!-- Quick Search Input -->
            <div class="sidebar-search-box">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-transparent border-0 text-muted ps-0 pe-2"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control sidebar-search-input" id="sidebarSearchDesktop" placeholder="Filter navigation (Ctrl+K)..." aria-label="Filter navigation">
                </div>
            </div>

            <!-- 2. Independently Scrollable Navigation Area (10 Groups) -->
            <nav class="sidebar-navigation" id="sidebarNavigationDesktop">
                @php
                    $isOverview = request()->is('/') || request()->is('dashboard*');
                    $isCareer = request()->is('role-profiles*') || request()->is('cv-studio*') || request()->is('linkedin-optimizer*');
                    $isJobs = request()->is('jobs*') || request()->is('applications*') || request()->is('import*');
                    $isComms = request()->is('email-studio*');
                    $isAI = request()->is('ai-chat*');
                    $isContent = request()->is('content-studio*') || request()->is('linkedin/publishing*');
                    $isArchives = request()->is('archives*');
                    $isAutomation = request()->is('system-health*');
                    $isSettings = request()->is('settings*');
                @endphp

                <!-- Group 1: OVERVIEW -->
                <div class="nav-group">
                    <button class="nav-group-header" type="button" data-bs-toggle="collapse" data-bs-target="#group-overview" aria-expanded="{{ $isOverview ? 'true' : 'false' }}" aria-controls="group-overview">
                        <span class="group-title">
                            <i class="bi bi-grid-1x2-fill"></i>
                            <span>1. Overview</span>
                        </span>
                        <i class="bi bi-chevron-down chevron-icon"></i>
                    </button>
                    <div class="collapse {{ $isOverview ? 'show' : '' }}" id="group-overview">
                        <ul class="nav-sub-list">
                            <li class="nav-sub-item">
                                <a href="{{ url('/') }}" class="nav-link-item {{ $isOverview ? 'active' : '' }}">
                                    <i class="bi bi-speedometer2"></i>
                                    <span>Dashboard</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Group 2: CAREER & PROFILE -->
                <div class="nav-group">
                    <button class="nav-group-header" type="button" data-bs-toggle="collapse" data-bs-target="#group-career" aria-expanded="{{ $isCareer ? 'true' : 'false' }}" aria-controls="group-career">
                        <span class="group-title">
                            <i class="bi bi-person-workspace"></i>
                            <span>2. Career & Profile</span>
                        </span>
                        <i class="bi bi-chevron-down chevron-icon"></i>
                    </button>
                    <div class="collapse {{ $isCareer ? 'show' : '' }}" id="group-career">
                        <ul class="nav-sub-list">
                            <li class="nav-sub-item">
                                <a href="{{ url('/role-profiles') }}" class="nav-link-item {{ request()->is('role-profiles*') ? 'active' : '' }}">
                                    <i class="bi bi-person-badge-fill"></i>
                                    <span>Role Profiles</span>
                                </a>
                            </li>
                            <li class="nav-sub-item">
                                <a href="{{ url('/cv-studio') }}" class="nav-link-item {{ (request()->is('cv-studio') || request()->is('cv-studio/editor*')) ? 'active' : '' }}">
                                    <i class="bi bi-file-earmark-text-fill"></i>
                                    <span>CV Studio & LaTeX</span>
                                </a>
                            </li>
                            <li class="nav-sub-item">
                                <a href="{{ url('/cv-studio/assignment') }}" class="nav-link-item {{ request()->is('cv-studio/assignment*') ? 'active' : '' }}">
                                    <i class="bi bi-diagram-3-fill"></i>
                                    <span>CV Assignment Manager</span>
                                </a>
                            </li>
                            <li class="nav-sub-item">
                                <a href="{{ url('/linkedin-optimizer') }}" class="nav-link-item {{ request()->is('linkedin-optimizer*') ? 'active' : '' }}">
                                    <i class="bi bi-linkedin"></i>
                                    <span>LinkedIn Optimizer</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Group 3: JOBS & APPLICATIONS -->
                <div class="nav-group">
                    <button class="nav-group-header" type="button" data-bs-toggle="collapse" data-bs-target="#group-jobs" aria-expanded="{{ $isJobs ? 'true' : 'false' }}" aria-controls="group-jobs">
                        <span class="group-title">
                            <i class="bi bi-briefcase-fill"></i>
                            <span>3. Jobs & Registry</span>
                        </span>
                        <i class="bi bi-chevron-down chevron-icon"></i>
                    </button>
                    <div class="collapse {{ $isJobs ? 'show' : '' }}" id="group-jobs">
                        <ul class="nav-sub-list">
                            <li class="nav-sub-item">
                                <a href="{{ url('/jobs') }}" class="nav-link-item {{ request()->is('jobs') ? 'active' : '' }}">
                                    <i class="bi bi-search"></i>
                                    <span>Job Discovery</span>
                                </a>
                            </li>
                            <li class="nav-sub-item">
                                <a href="{{ url('/jobs/ai-search') }}" class="nav-link-item {{ request()->is('jobs/ai-search*') ? 'active' : '' }}">
                                    <i class="bi bi-stars"></i>
                                    <span>AI Job Search</span>
                                </a>
                            </li>
                            <li class="nav-sub-item">
                                <a href="{{ url('/applications') }}" class="nav-link-item {{ request()->is('applications*') ? 'active' : '' }}">
                                    <i class="bi bi-journal-check"></i>
                                    <span>Application Registry</span>
                                </a>
                            </li>
                            <li class="nav-sub-item">
                                <a href="{{ url('/import') }}" class="nav-link-item {{ request()->is('import*') ? 'active' : '' }}">
                                    <i class="bi bi-file-earmark-spreadsheet-fill"></i>
                                    <span>Spreadsheet Import</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Group 4: COMMUNICATIONS -->
                <div class="nav-group">
                    <button class="nav-group-header" type="button" data-bs-toggle="collapse" data-bs-target="#group-comms" aria-expanded="{{ $isComms ? 'true' : 'false' }}" aria-controls="group-comms">
                        <span class="group-title">
                            <i class="bi bi-envelope-at-fill"></i>
                            <span>4. Communications</span>
                        </span>
                        <i class="bi bi-chevron-down chevron-icon"></i>
                    </button>
                    <div class="collapse {{ $isComms ? 'show' : '' }}" id="group-comms">
                        <ul class="nav-sub-list">
                            <li class="nav-sub-item">
                                <a href="{{ url('/email-studio') }}" class="nav-link-item {{ request()->is('email-studio*') ? 'active' : '' }}">
                                    <i class="bi bi-envelope-paper-fill"></i>
                                    <span>Email Studio & Mailers</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Group 5: AI WORKSPACE -->
                <div class="nav-group">
                    <button class="nav-group-header" type="button" data-bs-toggle="collapse" data-bs-target="#group-ai" aria-expanded="{{ $isAI ? 'true' : 'false' }}" aria-controls="group-ai">
                        <span class="group-title">
                            <i class="bi bi-cpu-fill"></i>
                            <span>5. AI Workspace</span>
                        </span>
                        <i class="bi bi-chevron-down chevron-icon"></i>
                    </button>
                    <div class="collapse {{ $isAI ? 'show' : '' }}" id="group-ai">
                        <ul class="nav-sub-list">
                            <li class="nav-sub-item">
                                <a href="{{ url('/ai-chat') }}" class="nav-link-item {{ (request()->is('ai-chat') || request()->is('ai-chat/session*')) ? 'active' : '' }}">
                                    <i class="bi bi-chat-square-dots-fill"></i>
                                    <span>AI Chat Assistant</span>
                                </a>
                            </li>
                            <li class="nav-sub-item">
                                <a href="{{ url('/ai-chat/playground') }}" class="nav-link-item {{ request()->is('ai-chat/playground*') ? 'active' : '' }}">
                                    <i class="bi bi-terminal-fill"></i>
                                    <span>Model Playground</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Group 6: CONTENT & LINKEDIN -->
                <div class="nav-group">
                    <button class="nav-group-header" type="button" data-bs-toggle="collapse" data-bs-target="#group-content" aria-expanded="{{ $isContent ? 'true' : 'false' }}" aria-controls="group-content">
                        <span class="group-title">
                            <i class="bi bi-palette2"></i>
                            <span>6. Content & Social</span>
                        </span>
                        <i class="bi bi-chevron-down chevron-icon"></i>
                    </button>
                    <div class="collapse {{ $isContent ? 'show' : '' }}" id="group-content">
                        <ul class="nav-sub-list">
                            <li class="nav-sub-item">
                                <a href="{{ url('/content-studio') }}" class="nav-link-item {{ request()->is('content-studio*') ? 'active' : '' }}">
                                    <i class="bi bi-image-fill"></i>
                                    <span>Content & Image Studio</span>
                                </a>
                            </li>
                            <li class="nav-sub-item">
                                <a href="{{ url('/linkedin/publishing') }}" class="nav-link-item {{ request()->is('linkedin/publishing*') ? 'active' : '' }}">
                                    <i class="bi bi-send-check"></i>
                                    <span>LinkedIn Publishing</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Group 7: DATA & ARCHIVES -->
                <div class="nav-group">
                    <button class="nav-group-header" type="button" data-bs-toggle="collapse" data-bs-target="#group-archives" aria-expanded="{{ $isArchives ? 'true' : 'false' }}" aria-controls="group-archives">
                        <span class="group-title">
                            <i class="bi bi-cloud-arrow-up-fill"></i>
                            <span>7. Data & Archives</span>
                        </span>
                        <i class="bi bi-chevron-down chevron-icon"></i>
                    </button>
                    <div class="collapse {{ $isArchives ? 'show' : '' }}" id="group-archives">
                        <ul class="nav-sub-list">
                            <li class="nav-sub-item">
                                <a href="{{ url('/archives') }}" class="nav-link-item {{ request()->is('archives*') ? 'active' : '' }}">
                                    <i class="bi bi-google"></i>
                                    <span>Drive & Sheets Archive</span>
                                </a>
                            </li>
                            <li class="nav-sub-item">
                                <a href="{{ url('/import') }}" class="nav-link-item {{ request()->is('import*') ? 'active' : '' }}">
                                    <i class="bi bi-file-earmark-arrow-down-fill"></i>
                                    <span>Data Import Engine</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Group 8: AUTOMATION & MONITORING -->
                <div class="nav-group">
                    <button class="nav-group-header" type="button" data-bs-toggle="collapse" data-bs-target="#group-automation" aria-expanded="{{ $isAutomation ? 'true' : 'false' }}" aria-controls="group-automation">
                        <span class="group-title">
                            <i class="bi bi-activity"></i>
                            <span>8. Health & Automation</span>
                        </span>
                        <i class="bi bi-chevron-down chevron-icon"></i>
                    </button>
                    <div class="collapse {{ $isAutomation ? 'show' : '' }}" id="group-automation">
                        <ul class="nav-sub-list">
                            <li class="nav-sub-item">
                                <a href="{{ url('/system-health') }}" class="nav-link-item {{ request()->is('system-health*') ? 'active' : '' }}">
                                    <i class="bi bi-heart-pulse-fill"></i>
                                    <span>System Health & Backups</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Group 9: CENTRAL SETTINGS -->
                <div class="nav-group">
                    <button class="nav-group-header" type="button" data-bs-toggle="collapse" data-bs-target="#group-settings" aria-expanded="{{ $isSettings ? 'true' : 'false' }}" aria-controls="group-settings">
                        <span class="group-title">
                            <i class="bi bi-gear-fill"></i>
                            <span>9. Configuration</span>
                        </span>
                        <i class="bi bi-chevron-down chevron-icon"></i>
                    </button>
                    <div class="collapse {{ $isSettings ? 'show' : '' }}" id="group-settings">
                        <ul class="nav-sub-list">
                            <li class="nav-sub-item">
                                <a href="{{ url('/settings') }}" class="nav-link-item {{ request()->is('settings*') ? 'active' : '' }}">
                                    <i class="bi bi-shield-lock-fill"></i>
                                    <span>Integrations & Credential Vault</span>
                                </a>
                            </li>
                            <li class="nav-sub-item">
                                <a href="{{ url('/settings#ai') }}" class="nav-link-item">
                                    <i class="bi bi-robot"></i>
                                    <span>AI Providers (Free)</span>
                                </a>
                            </li>
                            <li class="nav-sub-item">
                                <a href="{{ url('/settings#email') }}" class="nav-link-item">
                                    <i class="bi bi-mailbox2"></i>
                                    <span>Email Provider Quotas</span>
                                </a>
                            </li>
                            <li class="nav-sub-item">
                                <a href="{{ url('/settings#system') }}" class="nav-link-item">
                                    <i class="bi bi-shield-check"></i>
                                    <span>Free-Only Policy</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Group 10: USER ACCOUNT -->
                <div class="nav-group">
                    <button class="nav-group-header" type="button" data-bs-toggle="collapse" data-bs-target="#group-user" aria-expanded="false" aria-controls="group-user">
                        <span class="group-title">
                            <i class="bi bi-person-circle"></i>
                            <span>10. User Account</span>
                        </span>
                        <i class="bi bi-chevron-down chevron-icon"></i>
                    </button>
                    <div class="collapse" id="group-user">
                        <ul class="nav-sub-list">
                            <li class="nav-sub-item">
                                <a href="{{ url('/settings') }}" class="nav-link-item">
                                    <i class="bi bi-person-lines-fill"></i>
                                    <span>Account & Security</span>
                                </a>
                            </li>
                            @if(auth()->check())
                            <li class="nav-sub-item">
                                <form action="{{ route('logout') }}" method="POST" class="m-0 p-0">
                                    @csrf
                                    <button type="submit" class="nav-link-item text-danger border-0 bg-transparent w-100 text-start">
                                        <i class="bi bi-box-arrow-right text-danger"></i>
                                        <span>Sign Out</span>
                                    </button>
                                </form>
                            </li>
                            @endif
                        </ul>
                    </div>
                </div>
            </nav>

            <!-- 3. Fixed Settings Shortcut (Always visible regardless of navigation scroll) -->
            <div class="sidebar-settings">
                <a href="{{ url('/settings') }}" class="sidebar-settings-link {{ request()->is('settings*') ? 'active' : '' }}" id="pinnedSettingsLink" title="Access Central Settings & Vault">
                    <span class="d-flex align-items-center gap-2">
                        <i class="bi bi-gear-wide-connected text-primary"></i>
                        <span>Settings & Vault</span>
                    </span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill" style="font-size: 0.65rem;">
                        Free Mode
                    </span>
                </a>
                <a href="{{ url('/system-health') }}" class="sidebar-settings-link mt-1 {{ request()->is('system-health*') ? 'active' : '' }}" id="pinnedHealthLink" title="System Health & Safety">
                    <span class="d-flex align-items-center gap-2">
                        <i class="bi bi-heart-pulse-fill text-danger"></i>
                        <span>System Health</span>
                    </span>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill" style="font-size: 0.65rem;">
                        Active
                    </span>
                </a>
            </div>

            <!-- 4. Fixed User Profile Footer -->
            <div class="sidebar-footer">
                <a href="{{ url('/settings') }}" class="user-profile-badge" title="View Account Profile">
                    <div class="user-avatar">
                        {{ strtoupper(substr(auth()->check() ? auth()->user()->name : 'P', 0, 1)) }}
                    </div>
                    <div class="user-info">
                        <span class="user-name">{{ auth()->check() ? auth()->user()->name : 'Personal Candidate' }}</span>
                        <span class="user-role">Candidate Admin</span>
                    </div>
                </a>
                @if(auth()->check())
                    <form action="{{ route('logout') }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-link text-secondary p-1" title="Sign Out">
                            <i class="bi bi-box-arrow-right fs-5"></i>
                        </button>
                    </form>
                @endif
            </div>
        </aside>

        <!-- Mobile Offcanvas Sidebar Drawer (< 992px) -->
        <div class="offcanvas offcanvas-start text-bg-dark" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
            <div class="offcanvas-header border-bottom border-secondary py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-send-check-fill text-info fs-4"></i>
                    <h5 class="offcanvas-title text-white" id="mobileSidebarLabel" style="font-weight: 700;">JobPilot AI</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body p-2 d-flex flex-column">
                <!-- Mobile Navigation Filter -->
                <div class="mb-3 px-2">
                    <input type="text" class="form-control sidebar-search-input" id="sidebarSearchMobile" placeholder="Filter navigation..." aria-label="Filter navigation">
                </div>

                <!-- Navigation List for Mobile -->
                <nav class="flex-grow-1" id="sidebarNavigationMobile">
                    <!-- Cloned navigation links for mobile offcanvas -->
                    <div class="nav-group mb-2">
                        <a href="{{ url('/') }}" class="nav-link-item {{ $isOverview ? 'active' : '' }}">
                            <i class="bi bi-speedometer2"></i>
                            <span>Dashboard</span>
                        </a>
                    </div>
                    <div class="nav-group mb-2">
                        <div class="text-uppercase text-secondary small fw-bold px-2 py-1">Career & Profile</div>
                        <a href="{{ url('/role-profiles') }}" class="nav-link-item {{ request()->is('role-profiles*') ? 'active' : '' }}">
                            <i class="bi bi-person-badge-fill"></i>
                            <span>Role Profiles</span>
                        </a>
                        <a href="{{ url('/cv-studio') }}" class="nav-link-item {{ (request()->is('cv-studio') || request()->is('cv-studio/editor*')) ? 'active' : '' }}">
                            <i class="bi bi-file-earmark-text-fill"></i>
                            <span>CV Studio & LaTeX</span>
                        </a>
                        <a href="{{ url('/cv-studio/assignment') }}" class="nav-link-item {{ request()->is('cv-studio/assignment*') ? 'active' : '' }}">
                            <i class="bi bi-diagram-3-fill"></i>
                            <span>CV Assignment</span>
                        </a>
                        <a href="{{ url('/linkedin-optimizer') }}" class="nav-link-item {{ request()->is('linkedin-optimizer*') ? 'active' : '' }}">
                            <i class="bi bi-linkedin"></i>
                            <span>LinkedIn Optimizer</span>
                        </a>
                    </div>
                    <div class="nav-group mb-2">
                        <div class="text-uppercase text-secondary small fw-bold px-2 py-1">Jobs & Registry</div>
                        <a href="{{ url('/jobs') }}" class="nav-link-item {{ request()->is('jobs') ? 'active' : '' }}">
                            <i class="bi bi-search"></i>
                            <span>Job Discovery</span>
                        </a>
                        <a href="{{ url('/jobs/ai-search') }}" class="nav-link-item {{ request()->is('jobs/ai-search*') ? 'active' : '' }}">
                            <i class="bi bi-stars"></i>
                            <span>AI Job Search</span>
                        </a>
                        <a href="{{ url('/applications') }}" class="nav-link-item {{ request()->is('applications*') ? 'active' : '' }}">
                            <i class="bi bi-journal-check"></i>
                            <span>Application Registry</span>
                        </a>
                        <a href="{{ url('/import') }}" class="nav-link-item {{ request()->is('import*') ? 'active' : '' }}">
                            <i class="bi bi-file-earmark-spreadsheet-fill"></i>
                            <span>Spreadsheet Import</span>
                        </a>
                    </div>
                    <div class="nav-group mb-2">
                        <div class="text-uppercase text-secondary small fw-bold px-2 py-1">Communications & AI</div>
                        <a href="{{ url('/email-studio') }}" class="nav-link-item {{ request()->is('email-studio*') ? 'active' : '' }}">
                            <i class="bi bi-envelope-paper-fill"></i>
                            <span>Email Studio</span>
                        </a>
                        <a href="{{ url('/ai-chat') }}" class="nav-link-item {{ (request()->is('ai-chat') || request()->is('ai-chat/session*')) ? 'active' : '' }}">
                            <i class="bi bi-chat-square-dots-fill"></i>
                            <span>AI Chat Assistant</span>
                        </a>
                        <a href="{{ url('/content-studio') }}" class="nav-link-item {{ request()->is('content-studio*') ? 'active' : '' }}">
                            <i class="bi bi-palette2"></i>
                            <span>Content & Image Studio</span>
                        </a>
                        <a href="{{ url('/linkedin/publishing') }}" class="nav-link-item {{ request()->is('linkedin/publishing*') ? 'active' : '' }}">
                            <i class="bi bi-send-check"></i>
                            <span>LinkedIn Publishing</span>
                        </a>
                    </div>
                    <div class="nav-group mb-2">
                        <div class="text-uppercase text-secondary small fw-bold px-2 py-1">System & Data</div>
                        <a href="{{ url('/archives') }}" class="nav-link-item {{ request()->is('archives*') ? 'active' : '' }}">
                            <i class="bi bi-cloud-arrow-up-fill"></i>
                            <span>Archives & Sheets</span>
                        </a>
                        <a href="{{ url('/system-health') }}" class="nav-link-item {{ request()->is('system-health*') ? 'active' : '' }}">
                            <i class="bi bi-activity"></i>
                            <span>System Health</span>
                        </a>
                        <a href="{{ url('/settings') }}" class="nav-link-item {{ request()->is('settings*') ? 'active' : '' }}">
                            <i class="bi bi-gear-fill"></i>
                            <span>Settings & Vault</span>
                        </a>
                    </div>
                </nav>

                <!-- Mobile Footer Action -->
                <div class="pt-3 border-top border-secondary mt-auto">
                    <a href="{{ url('/settings') }}" class="d-flex align-items-center gap-2 text-decoration-none text-light mb-2">
                        <div class="user-avatar" style="width: 28px; height: 28px; font-size: 0.75rem;">
                            {{ strtoupper(substr(auth()->check() ? auth()->user()->name : 'P', 0, 1)) }}
                        </div>
                        <span class="small fw-semibold">{{ auth()->check() ? auth()->user()->name : 'Candidate' }}</span>
                    </a>
                    @if(auth()->check())
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                                <i class="bi bi-box-arrow-right me-1"></i>Sign Out
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="app-main">
            <!-- Top Sticky Navbar -->
            <header class="top-navbar">
                <div class="d-flex align-items-center gap-2">
                    <!-- Mobile Hamburger Toggle (< 992px) -->
                    <button class="btn btn-sm btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar" aria-label="Toggle navigation menu" id="mobileMenuToggle">
                        <i class="bi bi-list fs-5"></i>
                    </button>

                    <span class="badge-free-policy">
                        <i class="bi bi-shield-check"></i>
                        FREE_RESOURCES_ONLY Active
                    </span>
                    <span class="text-muted small d-none d-md-inline">
                        <i class="bi bi-geo-alt"></i> Asia/Kolkata
                    </span>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <!-- Fast Shortcuts in Navbar -->
                    <a href="{{ url('/settings') }}" class="btn btn-sm btn-outline-secondary d-none d-sm-inline-flex align-items-center gap-1" title="Settings">
                        <i class="bi bi-gear-fill"></i>
                        <span class="d-none d-md-inline">Settings</span>
                    </a>
                    <a href="{{ url('/system-health') }}" class="btn btn-sm btn-outline-secondary d-none d-sm-inline-flex align-items-center gap-1" title="System Health">
                        <i class="bi bi-heart-pulse-fill text-danger"></i>
                        <span class="d-none d-md-inline">Health</span>
                    </a>

                    <!-- User Profile Dropdown -->
                    <div class="dropdown ms-1">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="userMenuDropdown">
                            <i class="bi bi-person-circle"></i>
                            <span class="d-none d-sm-inline">{{ auth()->check() ? auth()->user()->name : 'Personal Admin' }}</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="userMenuDropdown">
                            <li><h6 class="dropdown-header">System: Laravel 13 / TiDB</h6></li>
                            <li><a class="dropdown-item" href="{{ url('/settings') }}"><i class="bi bi-sliders me-2"></i>Settings & Vault</a></li>
                            <li><a class="dropdown-item" href="{{ url('/system-health') }}"><i class="bi bi-heart-pulse me-2"></i>System Health</a></li>
                            <li><hr class="dropdown-divider"></li>
                            @if(auth()->check())
                                <li>
                                    <form action="{{ route('logout') }}" method="POST">
                                        @csrf
                                        <button class="dropdown-item text-danger" type="submit" id="signOutButton">
                                            <i class="bi bi-box-arrow-right me-2"></i>Sign Out
                                        </button>
                                    </form>
                                </li>
                            @else
                                <li><a class="dropdown-item" href="{{ route('login') }}"><i class="bi bi-box-arrow-in-right me-2"></i>Login</a></li>
                            @endif
                        </ul>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="content-area" id="mainContent">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Sidebar Search & Keyboard Navigation Helper Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Setup live search filter for desktop and mobile
            function setupNavFilter(inputId, navContainerId) {
                const searchInput = document.getElementById(inputId);
                const navContainer = document.getElementById(navContainerId);
                if (!searchInput || !navContainer) return;

                searchInput.addEventListener('input', function () {
                    const query = this.value.trim().toLowerCase();
                    const navItems = navContainer.querySelectorAll('.nav-sub-item, .nav-group');

                    if (!query) {
                        // Reset visibility
                        navItems.forEach(el => el.classList.remove('nav-item-hidden'));
                        return;
                    }

                    // For groups with collapsible sections
                    const groups = navContainer.querySelectorAll('.nav-group');
                    groups.forEach(group => {
                        const links = group.querySelectorAll('.nav-link-item');
                        let groupHasMatch = false;

                        links.forEach(link => {
                            const text = link.innerText.toLowerCase();
                            const subItem = link.closest('.nav-sub-item') || link;
                            if (text.includes(query)) {
                                subItem.classList.remove('nav-item-hidden');
                                groupHasMatch = true;
                            } else {
                                subItem.classList.add('nav-item-hidden');
                            }
                        });

                        const collapse = group.querySelector('.collapse');
                        if (groupHasMatch) {
                            group.classList.remove('nav-item-hidden');
                            if (collapse && !collapse.classList.contains('show')) {
                                const bsCollapse = bootstrap.Collapse.getOrCreateInstance(collapse);
                                bsCollapse.show();
                            }
                        } else {
                            group.classList.add('nav-item-hidden');
                        }
                    });
                });
            }

            setupNavFilter('sidebarSearchDesktop', 'sidebarNavigationDesktop');
            setupNavFilter('sidebarSearchMobile', 'sidebarNavigationMobile');

            // Keyboard shortcut Ctrl+K to focus search
            document.addEventListener('keydown', function (e) {
                if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                    e.preventDefault();
                    const desktopSearch = document.getElementById('sidebarSearchDesktop');
                    if (desktopSearch && window.innerWidth >= 992) {
                        desktopSearch.focus();
                    } else {
                        const mobileToggle = document.getElementById('mobileMenuToggle');
                        if (mobileToggle) mobileToggle.click();
                        setTimeout(() => {
                            const mobileSearch = document.getElementById('sidebarSearchMobile');
                            if (mobileSearch) mobileSearch.focus();
                        }, 300);
                    }
                }
            });
        });
    </script>
    @stack('scripts')
</body>
</html>
