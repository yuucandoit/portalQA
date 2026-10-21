<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'QA Portal'))</title>

    <!-- PWA Head Elements -->
    @include('layouts.partials.pwa-head')

    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <!-- Compiled Local Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f1f5f9;
            display: flex;
            min-height: 100vh;
            color: #1e293b;
        }

        /* Helper Classes */
        .mr-1 { margin-right: 0.25rem; }
        .mr-2 { margin-right: 0.5rem; }
        .mb-2 { margin-bottom: 0.5rem; }
        .mb-4 { margin-bottom: 1rem; }
        .text-blue-500 { color: #3b82f6; }
        .text-green-500 { color: #22c55e; }
        .text-blue-600 { color: #2563eb; }
        .text-green-600 { color: #16a34a; }

        /* ===== SIDEBAR ===== */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            color: white;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 1000;
            transition: transform 0.3s ease;
            overflow-y: auto;
        }

        .sidebar::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: #475569;
            border-radius: 10px;
        }

        .sidebar-brand {
            padding: 24px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }

        .sidebar-brand .brand-icon {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .sidebar-brand .brand-text h1 {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: -0.5px;
            margin: 0;
            line-height: 1.2;
            color: white;
        }

        .sidebar-brand .brand-text span {
            font-size: 11px;
            color: #94a3b8;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .sidebar-menu {
            padding: 20px 16px;
            flex: 1;
        }

        .sidebar-menu .menu-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #64748b;
            padding: 8px 12px;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .sidebar-menu .menu-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 16px;
            border-radius: 10px;
            color: #cbd5e1;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
            position: relative;
            margin-bottom: 2px;
            font-size: 14px;
            font-weight: 500;
        }

        .sidebar-menu .menu-item:hover {
            background: rgba(255,255,255,0.06);
            color: white;
        }

        .sidebar-menu .menu-item.active {
            background: rgba(102, 126, 234, 0.2);
            color: white;
        }

        .sidebar-menu .menu-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 8px;
            bottom: 8px;
            width: 3px;
            background: linear-gradient(180deg, #667eea, #764ba2);
            border-radius: 0 4px 4px 0;
        }

        .sidebar-menu .menu-item i {
            width: 20px;
            text-align: center;
            font-size: 16px;
            flex-shrink: 0;
        }

        .sidebar-menu .menu-item .badge {
            margin-left: auto;
            background: rgba(102, 126, 234, 0.3);
            color: #a5b4fc;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .sidebar-menu .menu-item .badge-danger {
            background: rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        .sidebar-menu .menu-item .badge-success {
            background: rgba(34, 197, 94, 0.3);
            color: #86efac;
        }

        .sidebar-footer {
            padding: 16px 20px;
            border-top: 1px solid rgba(255,255,255,0.05);
            flex-shrink: 0;
        }

        .sidebar-footer .user-card {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 12px;
            border-radius: 10px;
            transition: background 0.2s ease;
        }

        .sidebar-footer .user-card:hover {
            background: rgba(255,255,255,0.05);
        }

        .sidebar-footer .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 15px;
            flex-shrink: 0;
            color: white;
        }

        .sidebar-footer .user-info {
            flex: 1;
            min-width: 0;
        }

        .sidebar-footer .user-info .name {
            font-size: 14px;
            font-weight: 600;
            color: white;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar-footer .user-info .role {
            font-size: 12px;
            color: #94a3b8;
        }

        .sidebar-footer .user-card .logout-btn {
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
            font-size: 16px;
            transition: color 0.2s ease;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sidebar-footer .user-card .logout-btn:hover {
            color: #ef4444;
        }

        /* ===== MAIN CONTENT ===== */
        .main-content {
            margin-left: 280px;
            flex: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            width: calc(100% - 280px);
        }

        /* Top Bar */
        .topbar {
            background: white;
            padding: 16px 32px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .topbar .page-title h2 {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }

        .topbar .page-title p {
            font-size: 13px;
            color: #64748b;
            margin: 0;
        }

        .topbar .topbar-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .topbar .topbar-actions .search-box {
            position: relative;
        }

        .topbar .topbar-actions .search-box input {
            padding: 8px 16px 8px 40px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 13px;
            width: 240px;
            background: #f8fafc;
            transition: all 0.3s ease;
        }

        .topbar .topbar-actions .search-box input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            width: 280px;
        }

        .topbar .topbar-actions .search-box i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        .topbar .topbar-actions .notification-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 1px solid #e2e8f0;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            color: #64748b;
        }

        .topbar .topbar-actions .notification-btn:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }

        .topbar .topbar-actions .notification-btn .dot {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 8px;
            height: 8px;
            background: #ef4444;
            border-radius: 50%;
            border: 2px solid white;
        }

        .topbar .topbar-actions .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 20px;
            color: #1e293b;
            cursor: pointer;
            padding: 4px;
        }

        /* Page Content */
        .page-content {
            padding: 32px;
            flex: 1;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: white;
            border-radius: 14px;
            padding: 20px 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
            transition: all 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .stat-card .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin-bottom: 12px;
        }

        .stat-card .stat-icon.blue { background: #eff6ff; color: #3b82f6; }
        .stat-card .stat-icon.green { background: #f0fdf4; color: #22c55e; }
        .stat-card .stat-icon.red { background: #fef2f2; color: #ef4444; }
        .stat-card .stat-icon.yellow { background: #fffbeb; color: #f59e0b; }

        .stat-card .stat-number {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
        }

        .stat-card .stat-label {
            font-size: 13px;
            color: #64748b;
            margin-top: 2px;
        }

        .stat-card .stat-change {
            font-size: 12px;
            font-weight: 600;
            margin-top: 8px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .stat-card .stat-change.up { color: #22c55e; }
        .stat-card .stat-change.down { color: #ef4444; }

        /* Content Cards */
        .content-card {
            background: white;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            margin-bottom: 24px;
        }

        .content-card .card-header {
            padding: 16px 24px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .content-card .card-header h3 {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
            display: flex;
            align-items: center;
        }

        .content-card .card-header .card-action {
            color: #667eea;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
        }

        .content-card .card-header .card-action:hover {
            text-decoration: underline;
        }

        .content-card .card-body {
            padding: 20px 24px;
        }

        /* Table */
        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        table th {
            text-align: left;
            padding: 10px 12px;
            font-weight: 600;
            color: #64748b;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #f1f5f9;
        }

        table td {
            padding: 12px;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
        }

        table tr:hover td {
            background: #f8fafc;
        }

        .badge-status {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge-success { background: #dcfce7; color: #166534; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .badge-secondary { background: #f1f5f9; color: #475569; }

        .priority-high { color: #ef4444; font-weight: 600; }
        .priority-critical { color: #dc2626; font-weight: 700; }
        .priority-medium { color: #f59e0b; font-weight: 600; }
        .priority-low { color: #22c55e; font-weight: 600; }

        /* Mobile Menu Overlay */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }

        .sidebar-overlay.active {
            display: block;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1024px) {
            .topbar .topbar-actions .search-box input {
                width: 160px;
            }
            .topbar .topbar-actions .search-box input:focus {
                width: 200px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .sidebar-overlay.active {
                display: block;
            }

            .main-content {
                margin-left: 0;
                width: 100%;
            }

            .topbar {
                padding: 12px 16px;
            }

            .topbar .topbar-actions .menu-toggle {
                display: block;
            }

            .topbar .topbar-actions .search-box {
                display: none;
            }

            .topbar .page-title h2 {
                font-size: 17px;
            }

            .topbar .page-title p {
                font-size: 12px;
            }

            .page-content {
                padding: 16px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }

            .stat-card {
                padding: 16px;
            }

            .stat-card .stat-number {
                font-size: 22px;
            }

            .content-card .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .content-card .card-body {
                padding: 16px;
            }

            table {
                font-size: 13px;
            }

            table th, table td {
                padding: 8px 10px;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }

            .stat-card .stat-number {
                font-size: 18px;
            }

            .stat-card .stat-icon {
                width: 36px;
                height: 36px;
                font-size: 14px;
            }

            .topbar .page-title h2 {
                font-size: 15px;
            }

            .page-content {
                padding: 12px;
            }
        }

        /* ==========================================================================
           PWA & MOBILE OPTIMIZATIONS
           ========================================================================== */
        
        /* Topbar Install Button */
        .pwa-install-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            background: linear-gradient(135deg, #0284c7, #2563eb);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
        }
        .pwa-install-btn:hover {
            background: linear-gradient(135deg, #0369a1, #1d4ed8);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35);
        }
        .pwa-install-btn i {
            font-size: 13px;
        }

        /* Sidebar Install Box */
        .btn-sidebar-pwa-install {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(56, 189, 248, 0.3);
            border-radius: 12px;
            color: #f8fafc;
            text-align: left;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .btn-sidebar-pwa-install:hover {
            background: rgba(56, 189, 248, 0.15);
            border-color: #38bdf8;
            transform: translateY(-1px);
        }
        .btn-sidebar-pwa-install i {
            font-size: 20px;
            color: #38bdf8;
        }
        .btn-sidebar-pwa-install .pwa-text-col {
            display: flex;
            flex-direction: column;
            line-height: 1.3;
        }
        .btn-sidebar-pwa-install .pwa-text-col strong {
            font-size: 13px;
            color: #ffffff;
        }
        .btn-sidebar-pwa-install .pwa-text-col small {
            font-size: 11px;
            color: #94a3b8;
        }

        /* Bottom Navigation Bar (< 768px) */
        .pwa-bottom-nav {
            display: none;
        }

        @media (max-width: 768px) {
            .page-content {
                padding-bottom: calc(72px + env(safe-area-inset-bottom, 0px)) !important;
            }

            .pwa-bottom-nav {
                display: flex;
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                z-index: 1050;
                background: rgba(15, 23, 42, 0.94);
                backdrop-filter: blur(16px);
                -webkit-backdrop-filter: blur(16px);
                border-top: 1px solid rgba(255, 255, 255, 0.08);
                padding: 6px 8px max(6px, env(safe-area-inset-bottom, 6px));
                justify-content: space-around;
                align-items: center;
                box-shadow: 0 -10px 25px rgba(0, 0, 0, 0.3);
            }

            .bottom-nav-item {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                text-decoration: none;
                color: #94a3b8;
                font-size: 10px;
                font-weight: 500;
                padding: 4px 8px;
                border-radius: 8px;
                border: none;
                background: transparent;
                cursor: pointer;
                transition: all 0.2s ease;
                min-width: 58px;
                -webkit-tap-highlight-color: transparent;
            }

            .bottom-nav-item:hover,
            .bottom-nav-item:focus {
                color: #38bdf8;
            }

            .bottom-nav-item.active {
                color: #38bdf8;
                font-weight: 700;
            }

            .bottom-nav-item .nav-icon-wrap {
                position: relative;
                font-size: 18px;
                margin-bottom: 2px;
                height: 24px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .bottom-nav-item.active .nav-icon-wrap {
                transform: translateY(-2px);
                transition: transform 0.2s ease;
            }

            .bottom-nav-item.active .nav-icon-wrap::after {
                content: '';
                position: absolute;
                bottom: -4px;
                width: 16px;
                height: 3px;
                background: #38bdf8;
                border-radius: 9999px;
            }

            .bottom-nav-badge {
                position: absolute;
                top: -3px;
                right: -8px;
                background: #ef4444;
                color: #ffffff;
                font-size: 9px;
                font-weight: 800;
                padding: 1px 5px;
                border-radius: 10px;
                line-height: 1.2;
                border: 1px solid #0f172a;
            }

            .chat-live-pulse {
                position: absolute;
                top: 2px;
                right: -3px;
                width: 7px;
                height: 7px;
                background: #22c55e;
                border-radius: 50%;
                box-shadow: 0 0 0 2px #0f172a;
                animation: chatPulse 2s infinite;
            }

            @keyframes chatPulse {
                0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
                70% { transform: scale(1); box-shadow: 0 0 0 5px rgba(34, 197, 94, 0); }
                100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
            }

            /* Responsive Adjustments for Mobile Topbar */
            .pwa-install-btn .pwa-btn-label {
                display: none;
            }
            .pwa-install-btn {
                padding: 8px 10px;
            }
        }

        /* Safe area support for notch and home indicator */
        @supports (padding-top: env(safe-area-inset-top)) {
            .topbar {
                padding-top: max(16px, env(safe-area-inset-top));
            }
            .sidebar {
                padding-top: env(safe-area-inset-top);
                padding-bottom: env(safe-area-inset-bottom);
            }
        }

        /* iOS Installation Instruction Modal */
        .pwa-ios-modal-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            z-index: 9999;
            align-items: flex-end;
            justify-content: center;
            padding: 16px;
            animation: fadeInModal 0.25s ease-out;
        }

        @keyframes fadeInModal {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .pwa-ios-modal-card {
            background: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 20px;
            width: 100%;
            max-width: 440px;
            padding: 24px;
            color: #ffffff;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .pwa-ios-modal-header {
            display: flex;
            align-items: center;
            gap: 14px;
            position: relative;
            margin-bottom: 20px;
        }

        .pwa-ios-modal-header h3 {
            font-size: 16px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 2px;
        }

        .pwa-ios-modal-header p {
            font-size: 12px;
            color: #94a3b8;
        }

        .btn-close-modal {
            position: absolute;
            top: -4px;
            right: 0;
            background: transparent;
            border: none;
            color: #94a3b8;
            font-size: 22px;
            cursor: pointer;
        }

        .pwa-ios-steps {
            background: rgba(15, 23, 42, 0.6);
            border-radius: 14px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            margin-bottom: 20px;
        }

        .pwa-step {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: 13px;
            color: #cbd5e1;
            line-height: 1.4;
        }

        .step-num {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            background: #2563eb;
            color: white;
            border-radius: 50%;
            font-size: 11px;
            font-weight: 700;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .btn-understand {
            width: 100%;
            padding: 12px;
            background: #2563eb;
            color: #ffffff;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }

        /* PWA Toast Notification */
        .pwa-toast-container {
            position: fixed;
            bottom: 80px;
            right: 20px;
            z-index: 99999;
            display: flex;
            flex-direction: column;
            gap: 8px;
            pointer-events: none;
        }

        .pwa-toast {
            pointer-events: auto;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 18px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 500;
            color: #ffffff;
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
            animation: toastIn 0.3s ease-out;
            transition: all 0.3s ease;
        }

        .pwa-toast-success i { color: #22c55e; }
        .pwa-toast-info i { color: #38bdf8; }

        @keyframes toastIn {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Update Banner */
        .pwa-update-banner {
            position: fixed;
            top: 16px;
            left: 50%;
            transform: translateX(-50%);
            background: #1e293b;
            border: 1px solid #38bdf8;
            color: #ffffff;
            padding: 10px 18px;
            border-radius: 9999px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            z-index: 99999;
            display: flex;
            align-items: center;
            gap: 14px;
            font-size: 13px;
            animation: bannerDown 0.3s ease-out;
        }

        .btn-update-now {
            background: #0284c7;
            color: white;
            border: none;
            padding: 5px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }
        @keyframes bannerDown {
            from { opacity: 0; transform: translate(-50%, -20px); }
            to { opacity: 1; transform: translate(-50%, 0); }
        }
    </style>
    @stack('styles')
</head>
<body>

    <!-- Sidebar Overlay (Mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- ===== SIDEBAR COMPONENT ===== -->
    @include('layouts.sidebar')

    <!-- ===== MAIN CONTENT ===== -->
    <main class="main-content">

        <!-- ===== TOPBAR COMPONENT ===== -->
        @include('layouts.topbar')

        <!-- ===== PAGE CONTENT SLOT ===== -->
        <div class="page-content">
            @yield('content')
        </div>
    </main>

    <!-- ===== MOBILE PWA BOTTOM NAVIGATION ===== -->
    @include('layouts.partials.bottom-nav')

    <!-- PWA Client Engine -->
    <script src="{{ asset('js/pwa.js') }}"></script>

    @auth
    <!-- Global Real-Time Chat Notifier & Unread Badge Engine -->
    <script>
        window.CURRENT_USER_ID = {{ Auth::id() }};
        window.CURRENT_USER_NAME = "{{ addslashes(Auth::user()->name) }}";
        @php
            $isHttps = request()->isSecure() || request()->header('X-Forwarded-Proto') === 'https' || str_starts_with(config('app.url', ''), 'https://');
            $defaultWsUrl = $isHttps ? 'wss://' . request()->getHost() . '/ws' : (env('CHAT_WS_URL') ?: '');
        @endphp
        window.WS_URL = @json($defaultWsUrl);
        window.WS_PORT = {{ config('services.chat.ws_port', env('CHAT_WS_PORT', 6001)) }};
    </script>
    <script src="{{ asset('js/chat-notifier.js') }}"></script>
    @endauth

    <!-- Global Layout Scripts -->
    <script>
        (function() {
            'use strict';

            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const menuToggle = document.getElementById('menuToggle');
            const bottomNavMenuToggle = document.getElementById('bottomNavMenuToggle');

            function toggleSidebar() {
                if (!sidebar || !overlay) return;
                sidebar.classList.toggle('open');
                overlay.classList.toggle('active');
            }

            function closeSidebar() {
                if (!sidebar || !overlay) return;
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
            }

            if (menuToggle) {
                menuToggle.addEventListener('click', toggleSidebar);
            }
            if (bottomNavMenuToggle) {
                bottomNavMenuToggle.addEventListener('click', toggleSidebar);
            }
            if (overlay) {
                overlay.addEventListener('click', closeSidebar);
            }

            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    closeSidebar();
                }
            });
        })();
    </script>
    @stack('scripts')
</body>
</html>
