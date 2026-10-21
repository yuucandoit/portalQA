<!-- ===== SIDEBAR COMPONENT ===== -->
<aside class="sidebar" id="sidebar">
    <!-- Brand -->
    <div class="sidebar-brand">
        <div class="brand-icon">
            <i class="fas fa-shield-alt"></i>
        </div>
        <div class="brand-text">
            <h1>{{ config('app.name', 'QA Portal') }}</h1>
            <span>Quality Assurance System</span>
        </div>
    </div>

    <!-- Menu -->
    <nav class="sidebar-menu">
        <div class="menu-label">Main Menu</div>

        <a href="{{ Route::has('home') ? route('home') : '#' }}" class="menu-item {{ request()->routeIs('home') ? 'active' : '' }}">
            <i class="fas fa-th-large"></i>
            <span>Dashboard</span>
        </a>

        <a href="{{ route('projects.index') }}" class="menu-item {{ request()->routeIs('projects.*') ? 'active' : '' }}">
            <i class="fas fa-folder-open"></i>
            <span>Projects</span>
            <span class="badge">{{ \App\Models\Projects::count() }}</span>
        </a>

        <a href="{{ route('reports.index') }}" class="menu-item {{ request()->routeIs('reports.*') ? 'active' : '' }}">
            <i class="fas fa-file-alt"></i>
            <span>Reports Test</span>
            <span class="badge badge-danger">{{ \App\Models\TestReport::count() }}</span>
        </a>

        <a href="{{ route('bugs.index') }}" class="menu-item {{ request()->routeIs('bugs.*') ? 'active' : '' }}">
            <i class="fas fa-bug text-rose-500"></i>
            <span>Bug List</span>
            @php $activeBugCount = \App\Models\Bug::whereIn('status', ['Open', 'In Progress', 'Reopen', 'Ready to Retest'])->count(); @endphp
            @if($activeBugCount > 0)
                <span class="badge" style="background: #f43f5e; color: white; font-weight: 700;">{{ $activeBugCount }}</span>
            @endif
        </a>

        <div class="menu-label" style="margin-top: 24px;">COMMUNICATION & AI</div>

        @php
            $unreadChatTotal = Auth::check() ? Auth::user()->unreadChatCount() : 0;
        @endphp
        <a href="{{ route('chat.index') }}" class="menu-item {{ request()->routeIs('chat.*') ? 'active' : '' }}">
            <i class="fas fa-comments" style="color: #38bdf8;"></i>
            <span>Team Chat</span>
            <span class="badge badge-chat-unread" id="sidebarChatUnreadBadge" style="{{ $unreadChatTotal > 0 ? '' : 'display: none;' }} background: #ef4444; color: white; font-weight: 700; border-radius: 9999px; padding: 2px 7px; font-size: 11px;">
                {{ $unreadChatTotal > 99 ? '99+' : $unreadChatTotal }}
            </span>
            <span class="badge badge-chat-live" id="sidebarChatLiveTag" style="{{ $unreadChatTotal > 0 ? 'display: none;' : '' }} background: linear-gradient(135deg, #0ea5e9, #2563eb); color: white; font-weight: 800; letter-spacing: 0.5px;">
                LIVE
            </span>
        </a>

        <a href="{{ route('daily-standup.index') }}" class="menu-item {{ request()->routeIs('daily-standup.*') ? 'active' : '' }}">
            <i class="fas fa-robot text-purple-400"></i>
            <span>AI Daily Standup</span>
            <span class="badge" style="background: linear-gradient(135deg, #6366f1, #ec4899); color: white; font-weight: 700;">Standup</span>
        </a>

        <div class="menu-label" style="margin-top: 24px;">Settings</div>

        <a href="{{ route('settings.index') }}" class="menu-item {{ request()->routeIs('settings.*') ? 'active' : '' }}">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </a>

        <a href="#" class="menu-item {{ request()->is('help*') ? 'active' : '' }}">
            <i class="fas fa-question-circle"></i>
            <span>Help & Support</span>
        </a>

        @if(Auth::check() && Auth::user()->isAdmin())
            <div class="menu-label" style="margin-top: 24px;">ADMINISTRATION</div>
            <a href="{{ route('admin.users.index') }}" class="menu-item {{ request()->routeIs('admin.users.*') || request()->routeIs('users.*') ? 'active' : '' }}">
                <i class="fas fa-users-cog" style="color: #818cf8;"></i>
                <span>User Management</span>
                <span class="badge" style="background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; font-weight: 700; font-size: 11px;">Admin</span>
            </a>
        @endif

        <!-- PWA Install Sidebar Button -->
        <div class="sidebar-pwa-wrap pwa-install-trigger" style="display: none; padding: 10px 14px; margin-top: 14px;">
            <button type="button" class="btn-sidebar-pwa-install" onclick="window.triggerPwaInstall()">
                <i class="fas fa-mobile-screen-button"></i>
                <div class="pwa-text-col">
                    <strong>Pasang Aplikasi</strong>
                    <small>Akses cepat di HP & Desktop</small>
                </div>
            </button>
        </div>
    </nav>

    <!-- Footer / User Card -->
    <div class="sidebar-footer">
        <div class="user-card">
            @auth
                @php
                    $name = Auth::user()->name ?? 'QA User';
                    $words = explode(' ', trim($name));
                    $initials = count($words) >= 2 
                        ? strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1))
                        : strtoupper(substr($name, 0, 2));
                @endphp
                <div class="user-avatar">{{ $initials }}</div>
                <div class="user-info">
                    <div class="name" title="{{ Auth::user()->name }}">{{ Str::limit(Auth::user()->name, 16) }}</div>
                    <div class="role" style="{{ Auth::user()->isAdmin() ? 'color: #a78bfa; font-weight: 600;' : '' }}">
                        <i class="{{ Auth::user()->isAdmin() ? 'fas fa-shield-alt text-purple-400' : 'fas fa-user' }}" style="font-size: 10px; margin-right: 3px;"></i>
                        {{ Auth::user()->isAdmin() ? 'Administrator' : 'QA Member' }}
                    </div>
                </div>
                <button class="logout-btn" title="Logout" onclick="event.preventDefault(); document.getElementById('sidebar-logout-form').submit();">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
                <form id="sidebar-logout-form" action="{{ route('logout') }}" method="POST" class="d-none" style="display: none;">
                    @csrf
                </form>
            @else
                <div class="user-avatar">G</div>
                <div class="user-info">
                    <div class="name">Guest User</div>
                    <div class="role">Visitor</div>
                </div>
                <a href="{{ route('login') }}" class="logout-btn" title="Login" style="color: #667eea;">
                    <i class="fas fa-sign-in-alt"></i>
                </a>
            @endauth
        </div>
    </div>
</aside>
