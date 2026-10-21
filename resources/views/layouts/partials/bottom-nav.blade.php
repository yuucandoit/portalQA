<!-- Mobile PWA Bottom Navigation Bar (< 768px) -->
<nav class="pwa-bottom-nav" id="pwaBottomNav" aria-label="Navigasi Bawah">
    <a href="{{ route('home') }}" class="bottom-nav-item {{ request()->routeIs('home') ? 'active' : '' }}">
        <div class="nav-icon-wrap">
            <i class="fas fa-th-large"></i>
        </div>
        <span class="nav-label">Dashboard</span>
    </a>

    <a href="{{ route('reports.index') }}" class="bottom-nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}">
        <div class="nav-icon-wrap">
            <i class="fas fa-file-alt"></i>
        </div>
        <span class="nav-label">Reports</span>
    </a>

    @php
        $unreadChatTotal = Auth::check() ? Auth::user()->unreadChatCount() : 0;
    @endphp
    <a href="{{ route('chat.index') }}" class="bottom-nav-item nav-item-chat {{ request()->routeIs('chat.*') ? 'active' : '' }}">
        <div class="nav-icon-wrap">
            <i class="fas fa-comments"></i>
            <span class="chat-live-pulse" id="bottomNavChatPulse" style="{{ $unreadChatTotal > 0 ? 'display: none;' : '' }}"></span>
            <span class="bottom-nav-badge" id="bottomNavChatUnreadBadge" style="{{ $unreadChatTotal > 0 ? '' : 'display: none;' }}">
                {{ $unreadChatTotal > 99 ? '99+' : $unreadChatTotal }}
            </span>
        </div>
        <span class="nav-label">Team Chat</span>
    </a>

    <a href="{{ route('bugs.index') }}" class="bottom-nav-item {{ request()->routeIs('bugs.*') ? 'active' : '' }}">
        <div class="nav-icon-wrap">
            <i class="fas fa-bug"></i>
            @php $activeBugCount = \App\Models\Bug::whereIn('status', ['Open', 'In Progress', 'Reopen', 'Ready to Retest'])->count(); @endphp
            @if($activeBugCount > 0)
                <span class="bottom-nav-badge">{{ $activeBugCount }}</span>
            @endif
        </div>
        <span class="nav-label">Bugs</span>
    </a>

    <button type="button" class="bottom-nav-item btn-bottom-menu-toggle" id="bottomNavMenuToggle" aria-label="Buka Menu">
        <div class="nav-icon-wrap">
            <i class="fas fa-bars"></i>
        </div>
        <span class="nav-label">Menu</span>
    </button>
</nav>

