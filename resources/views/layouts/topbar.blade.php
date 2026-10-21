<!-- ===== TOPBAR COMPONENT ===== -->
<header class="topbar">
    <div class="page-title">
        <h2 id="pageTitle">@yield('page_title', 'Dashboard')</h2>
        <p id="pageSubtitle">@yield('page_subtitle', 'Overview of your QA activities')</p>
    </div>
    <div class="topbar-actions">
        <!-- PWA Install Button (Auto-detected on Desktop & Mobile) -->
        <button type="button" class="pwa-install-btn pwa-install-trigger" style="display: none;" onclick="window.triggerPwaInstall()" title="Install QA Portal di Perangkat">
            <i class="fas fa-download"></i>
            <span class="pwa-btn-label">Install App</span>
        </button>

        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Search..." />
        </div>
        <button class="notification-btn" title="Aktifkan Notifikasi Chat" onclick="if (window.requestChatNotificationPermission) window.requestChatNotificationPermission();">
            <i class="fas fa-bell"></i>
            <span class="dot"></span>
        </button>
        <button class="menu-toggle" id="menuToggle" title="Toggle Sidebar">
            <i class="fas fa-bars"></i>
        </button>
    </div>
</header>
