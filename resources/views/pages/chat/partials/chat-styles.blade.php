<style>
    /* ===== 0. SEAMLESS SHELL INTEGRATION FOR TEAM CHAT ===== */
    .main-content {
        height: 100vh;
        overflow: hidden;
    }

    .topbar {
        padding: 12px 24px !important;
        flex-shrink: 0;
    }

    /* Hide redundant global search box in topbar on chat page to avoid multiple search bars */
    .topbar .topbar-actions .search-box {
        display: none !important;
    }

    .page-content {
        padding: 0 !important;
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        background: #ffffff;
    }

    /* Utility colors */
    .text-amber-500 { color: #f59e0b; }
    .text-purple-500 { color: #a855f7; }
    .text-blue-400 { color: #60a5fa; }
    .text-blue-500 { color: #3b82f6; }
    .text-blue-600 { color: #2563eb; }
    .text-rose-500 { color: #f43f5e; }
    .text-emerald-600 { color: #059669; }
    .text-slate-300 { color: #cbd5e1; }
    .text-slate-400 { color: #94a3b8; }
    .text-slate-500 { color: #64748b; }

    /* ===== 1. CHAT STUDIO MAIN CONTAINER (FLUSH & UNIFIED) ===== */
    .chat-studio-container {
        display: flex;
        flex: 1;
        width: 100%;
        height: 100%;
        min-height: 0;
        background: #ffffff;
        overflow: hidden;
        position: relative;
    }

    /* ===== 2. LEFT PANEL: UNIFIED CHANNEL & DM SUB-SIDEBAR ===== */
    .chat-sidebar {
        width: 276px;
        background: #f8fafc;
        color: #1e293b;
        display: flex;
        flex-direction: column;
        border-right: 1px solid #e2e8f0;
        flex-shrink: 0;
        height: 100%;
    }

    .chat-sidebar-top {
        padding: 14px 14px 12px;
        border-bottom: 1px solid #e2e8f0;
        background: #ffffff;
        display: flex;
        flex-direction: column;
        gap: 10px;
        flex-shrink: 0;
    }

    .chat-sidebar-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }

    .ws-info {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    .ws-name {
        font-size: 14px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        line-height: 1.25;
    }

    .ws-status {
        font-size: 11px;
        color: #64748b;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        margin-top: 2px;
    }

    .ws-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .ws-dot.online { background: #16a34a; }
    .ws-dot.offline { background: #f59e0b; }

    .btn-create-channel {
        height: 30px;
        padding: 0 10px;
        border-radius: 7px;
        background: #2563eb;
        border: 1px solid #1d4ed8;
        color: #ffffff;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        font-size: 11.5px;
        font-weight: 600;
        transition: background 0.15s;
        flex-shrink: 0;
    }
    .btn-create-channel:hover {
        background: #1d4ed8;
    }

    .chat-sidebar-search {
        position: relative;
    }

    .chat-sidebar-search .search-icon {
        position: absolute;
        left: 11px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 12px;
        pointer-events: none;
    }

    .chat-sidebar-search input {
        width: 100%;
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 7px 10px 7px 32px;
        font-size: 12.5px;
        color: #0f172a;
        outline: none;
        transition: border-color 0.15s, background 0.15s;
    }
    .chat-sidebar-search input:focus {
        background: #ffffff;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.08);
    }

    /* Channel Navigation List */
    .chat-channel-list {
        flex: 1;
        overflow-y: auto;
        padding: 12px 10px;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .nav-section {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .nav-section-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 10.5px;
        font-weight: 700;
        color: #64748b;
        letter-spacing: 0.5px;
        padding: 2px 8px 4px;
    }

    .btn-section-add {
        width: 20px;
        height: 20px;
        border-radius: 5px;
        background: transparent;
        border: none;
        color: #64748b;
        font-size: 11px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: background 0.15s, color 0.15s;
    }
    .btn-section-add:hover {
        background: #e2e8f0;
        color: #0f172a;
    }

    .channel-group {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .channel-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 7px 10px;
        border-radius: 8px;
        color: #475569;
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
        border: 1px solid transparent;
        transition: all 0.15s;
    }
    .channel-item:hover {
        background: #f1f5f9;
        color: #0f172a;
    }
    .channel-item.active {
        background: #eff6ff;
        color: #1d4ed8;
        border-color: #bfdbfe;
        font-weight: 700;
    }

    .ch-left {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
        flex: 1;
    }

    .ch-name {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .ch-right {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-shrink: 0;
    }

    .ch-icon {
        font-size: 12.5px;
        color: #94a3b8;
        width: 16px;
        text-align: center;
        flex-shrink: 0;
    }
    .channel-item.active .ch-icon { color: #2563eb; }
    .ch-icon.broadcast { color: #d97706; }
    .ch-icon.private { color: #9333ea; }

    .badge-broadcast {
        font-size: 10px;
        background: #fef3c7;
        color: #b45309;
        padding: 1px 6px;
        border-radius: 4px;
        font-weight: 700;
    }

    .unread-badge {
        font-size: 10.5px;
        font-weight: 700;
        background: #ef4444;
        color: #ffffff;
        padding: 1px 6px;
        border-radius: 999px;
        min-width: 18px;
        text-align: center;
        justify-content: center;
    }

    .dm-avatar-wrap {
        position: relative;
        flex-shrink: 0;
    }

    .dm-avatar {
        width: 24px;
        height: 24px;
        border-radius: 6px;
        background: #e2e8f0;
        color: #334155;
        font-size: 10px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .channel-item.active .dm-avatar {
        background: #2563eb;
        color: #ffffff;
    }

    .user-presence-dot {
        position: absolute;
        bottom: -1px;
        right: -1px;
        width: 7px;
        height: 7px;
        border-radius: 50%;
        border: 1.5px solid #f8fafc;
    }
    .user-presence-dot.online { background: #16a34a; }
    .user-presence-dot.offline { background: #94a3b8; }

    .nav-empty {
        padding: 6px 10px;
        font-size: 11.5px;
        color: #94a3b8;
        font-style: italic;
    }

    /* ===== 3. MAIN CHAT AREA ===== */
    .chat-main {
        flex: 1;
        min-width: 0;
        height: 100%;
        display: flex;
        flex-direction: column;
        background: #f8fafc;
        position: relative;
        overflow: hidden;
    }

    /* Unified Top Header Stack */
    .chat-top-stack {
        background: #ffffff;
        border-bottom: 1px solid #e2e8f0;
        flex-shrink: 0;
        z-index: 20;
    }

    .chat-header {
        padding: 11px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
    }

    .chat-header-info {
        display: flex;
        align-items: center;
        gap: 11px;
        min-width: 0;
        flex: 1;
    }

    .ch-header-icon {
        width: 36px;
        height: 36px;
        border-radius: 9px;
        background: #f1f5f9;
        color: #475569;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        flex-shrink: 0;
    }
    .ch-header-icon.broadcast { background: #fef3c7; color: #d97706; }
    .ch-header-icon.private_group { background: #f3e8ff; color: #9333ea; }
    .ch-header-icon.direct { background: #eff6ff; color: #2563eb; }

    .ch-header-text {
        min-width: 0;
        flex: 1;
    }

    .ch-title-row {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .ch-title {
        font-size: 15px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .ch-type-badge {
        font-size: 10.5px;
        font-weight: 600;
        padding: 2px 7px;
        border-radius: 5px;
        background: #f1f5f9;
        color: #475569;
        flex-shrink: 0;
    }
    .ch-type-badge.broadcast { background: #fef3c7; color: #b45309; }
    .ch-type-badge.private_group { background: #f3e8ff; color: #7e22ce; }
    .ch-type-badge.direct { background: #eff6ff; color: #1d4ed8; }

    .ch-topic {
        font-size: 12px;
        color: #64748b;
        margin-top: 1px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Unified Header Action Buttons */
    .chat-header-actions {
        display: inline-flex;
        align-items: center;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 9px;
        padding: 3px;
        gap: 2px;
        flex-shrink: 0;
    }

    .header-action-btn {
        background: transparent;
        border: none;
        color: #475569;
        padding: 5px 10px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.15s;
    }
    .header-action-btn:hover {
        background: #ffffff;
        color: #0f172a;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
    }
    .header-action-btn.has-pinned {
        color: #b45309;
    }

    .action-count-pill {
        background: #e2e8f0;
        color: #334155;
        font-size: 10.5px;
        font-weight: 700;
        padding: 1px 6px;
        border-radius: 999px;
    }
    .header-action-btn.has-pinned .action-count-pill {
        background: #fef3c7;
        color: #b45309;
    }

    /* Pinned Banner Strip (Integrated inside chat-top-stack) */
    .pinned-banner {
        background: #fffbeb;
        border-top: 1px solid #fde68a;
        padding: 6px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .pinned-banner-left {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        font-size: 12px;
        min-width: 0;
        flex: 1;
    }

    .pinned-count-text {
        font-weight: 700;
        color: #92400e;
        white-space: nowrap;
    }

    .pinned-snippet {
        color: #78350f;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .btn-pinned-view {
        background: #ffffff;
        border: 1px solid #fcd34d;
        color: #92400e;
        font-size: 11px;
        font-weight: 600;
        padding: 3px 9px;
        border-radius: 5px;
        cursor: pointer;
        white-space: nowrap;
        flex-shrink: 0;
    }
    .btn-pinned-view:hover {
        background: #fef3c7;
    }

    /* In-Chat Search Bar (Integrated inside chat-top-stack) */
    .in-chat-search-bar {
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        padding: 8px 20px;
        position: relative;
    }

    .search-bar-top-row {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .search-input-group {
        flex: 1;
        min-width: 220px;
        display: flex;
        align-items: center;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 7px;
        padding: 5px 10px;
        gap: 8px;
    }
    .search-input-group:focus-within {
        border-color: #2563eb;
    }
    .search-input-group i { color: #64748b; font-size: 12px; }
    .search-input-group input {
        flex: 1;
        border: none;
        outline: none;
        font-size: 12.5px;
        color: #0f172a;
        background: transparent;
    }
    .btn-close-search {
        background: none;
        border: none;
        color: #64748b;
        font-size: 16px;
        line-height: 1;
        cursor: pointer;
    }

    .search-filter-chips {
        display: flex;
        align-items: center;
        gap: 5px;
        flex-wrap: wrap;
    }

    .filter-chip {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        color: #475569;
        font-size: 11px;
        font-weight: 600;
        padding: 4px 9px;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.15s;
    }
    .filter-chip:hover {
        border-color: #94a3b8;
        color: #0f172a;
    }
    .filter-chip.active {
        background: #2563eb;
        color: #ffffff;
        border-color: #2563eb;
    }

    .search-results-box {
        position: absolute;
        top: calc(100% + 4px);
        left: 20px;
        right: 20px;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.12);
        max-height: 260px;
        overflow-y: auto;
        z-index: 100;
        padding: 6px;
    }

    .search-res-item {
        padding: 8px 10px;
        border-radius: 7px;
        cursor: pointer;
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.15s;
    }
    .search-res-item:last-child { border-bottom: none; }
    .search-res-item:hover { background: #f8fafc; }
    .res-meta {
        display: flex;
        justify-content: space-between;
        font-size: 11.5px;
        color: #64748b;
        margin-bottom: 2px;
    }
    .res-meta strong { color: #0f172a; }
    .res-snippet {
        font-size: 12.5px;
        color: #334155;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .search-empty, .search-loading {
        padding: 14px;
        text-align: center;
        font-size: 12.5px;
        color: #64748b;
    }

    /* ===== 4. CHAT MESSAGES VIEWPORT & UNIFIED MESSAGE BUBBLES ===== */
    .chat-messages-viewport {
        flex: 1;
        overflow-y: auto;
        padding: 18px 24px;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    /* Compact Welcome Banner */
    .channel-welcome-banner {
        display: flex;
        align-items: center;
        gap: 12px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 12px 16px;
        margin-bottom: 4px;
    }

    .welcome-icon {
        width: 38px;
        height: 38px;
        border-radius: 9px;
        background: #eff6ff;
        color: #2563eb;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        flex-shrink: 0;
    }

    .welcome-text-wrap {
        min-width: 0;
        flex: 1;
    }

    .welcome-title {
        font-size: 13.5px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 2px;
    }

    .welcome-desc {
        font-size: 12px;
        color: #64748b;
        margin: 0;
        line-height: 1.4;
    }

    /* Messages Stream */
    .messages-stream {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .date-divider {
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 8px 0;
        position: relative;
    }
    .date-divider::before {
        content: '';
        position: absolute;
        left: 0;
        right: 0;
        top: 50%;
        height: 1px;
        background: #e2e8f0;
        z-index: 1;
    }
    .date-divider span {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        color: #64748b;
        font-size: 11px;
        font-weight: 600;
        padding: 2px 12px;
        border-radius: 999px;
        position: relative;
        z-index: 2;
    }

    /* Message Row Layout (Clear Left vs Right Alignment) */
    .message-row {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        position: relative;
        max-width: 100%;
    }

    .message-row.is-me {
        flex-direction: row-reverse;
    }

    .msg-avatar-col {
        flex-shrink: 0;
        margin-top: 2px;
    }

    .msg-avatar {
        width: 34px;
        height: 34px;
        border-radius: 9px;
        background: #e2e8f0;
        color: #334155;
        font-size: 12px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .message-row.is-me .msg-avatar {
        background: #2563eb;
        color: #ffffff;
    }

    .msg-content-col {
        display: flex;
        flex-direction: column;
        max-width: 72%;
        min-width: 160px;
    }

    .message-row.is-me .msg-content-col {
        align-items: flex-end;
    }

    /* Unified Card Bubble */
    .msg-card-bubble {
        position: relative;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 4px 14px 14px 14px;
        padding: 9px 13px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
        width: 100%;
        transition: border-color 0.15s, box-shadow 0.15s;
    }

    .message-row.is-me .msg-card-bubble {
        background: #eff6ff;
        border-color: #bfdbfe;
        border-radius: 14px 4px 14px 14px;
    }

    .message-row.is-pinned .msg-card-bubble {
        background: #fffbeb;
        border-color: #fcd34d;
    }

    .message-row:hover .msg-card-bubble {
        border-color: #cbd5e1;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.05);
    }

    .message-row.is-me:hover .msg-card-bubble {
        border-color: #93c5fd;
    }

    /* Header inside bubble */
    .msg-meta-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 4px;
    }

    .msg-sender {
        font-size: 12.5px;
        font-weight: 700;
        color: #0f172a;
    }

    .message-row.is-me .msg-sender {
        color: #1e40af;
    }

    .msg-time {
        font-size: 10.5px;
        color: #64748b;
        white-space: nowrap;
    }

    .msg-pinned-tag {
        font-size: 10px;
        font-weight: 700;
        background: #fef3c7;
        color: #b45309;
        padding: 1px 6px;
        border-radius: 4px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    /* Quoted Reply inside bubble */
    .msg-quoted-box {
        background: rgba(15, 23, 42, 0.04);
        border-left: 3px solid #2563eb;
        padding: 5px 9px;
        border-radius: 5px;
        margin-bottom: 6px;
        font-size: 11.5px;
        cursor: pointer;
        display: flex;
        flex-direction: column;
        gap: 1px;
    }
    .quote-sender {
        font-weight: 700;
        color: #2563eb;
    }
    .quote-text {
        color: #475569;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Message Text & Media Body */
    .msg-bubble {
        font-size: 13.5px;
        color: #1e293b;
        line-height: 1.5;
        word-break: break-word;
    }

    .recalled-text {
        font-style: italic;
        color: #64748b;
        font-size: 12.5px;
    }

    /* Formatting Elements */
    .chat-code-block {
        background: #0f172a;
        color: #e2e8f0;
        padding: 9px 12px;
        border-radius: 7px;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 12px;
        margin: 6px 0;
        overflow-x: auto;
    }

    .chat-inline-code {
        background: rgba(15, 23, 42, 0.06);
        color: #0f172a;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 12px;
        padding: 1px 5px;
        border-radius: 4px;
    }

    .chat-link {
        color: #2563eb;
        text-decoration: underline;
        word-break: break-all;
    }

    /* Mentions / Tags styling */
    .chat-mention {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
        padding: 0 6px;
        border-radius: 5px;
        font-weight: 700;
        font-size: 12px;
        line-height: 1.5;
        vertical-align: baseline;
    }
    .chat-mention i {
        font-size: 10px;
    }
    .chat-mention.is-me-tagged {
        background: #fef3c7;
        color: #92400e;
        border-color: #fde68a;
    }

    /* Attachments */
    .chat-attached-img {
        max-width: 300px;
        max-height: 220px;
        border-radius: 8px;
        margin-top: 6px;
        border: 1px solid #e2e8f0;
        cursor: pointer;
        display: block;
    }

    .chat-attached-video {
        max-width: 340px;
        border-radius: 8px;
        margin-top: 6px;
        border: 1px solid #cbd5e1;
        display: block;
    }

    .voice-note-card {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 9px;
        padding: 5px 10px;
        margin-top: 5px;
    }

    .vn-icon {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: #eff6ff;
        color: #2563eb;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
    }

    .chat-audio-element {
        height: 30px;
    }

    .doc-file-card {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 9px;
        padding: 7px 12px;
        margin-top: 5px;
        text-decoration: none;
        color: #1e293b;
        transition: all 0.15s;
    }
    .doc-file-card:hover {
        background: #f8fafc;
        border-color: #94a3b8;
    }
    .doc-icon { font-size: 20px; }
    .doc-info { display: flex; flex-direction: column; }
    .doc-name { font-weight: 600; font-size: 12.5px; color: #0f172a; }
    .doc-size { font-size: 11px; color: #64748b; }
    .doc-download-btn { color: #2563eb; font-size: 13px; margin-left: 4px; }

    .chat-gif-img {
        max-width: 240px;
        border-radius: 8px;
        margin-top: 5px;
        display: block;
    }

    /* Unified Bottom Bar inside Bubble */
    .msg-bottom-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 4px;
    }
    .msg-bottom-bar:empty {
        display: none;
    }

    .msg-reactions-row {
        display: flex;
        align-items: center;
        gap: 4px;
        flex-wrap: wrap;
    }

    .reaction-pill {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        padding: 1px 7px;
        border-radius: 999px;
        font-size: 11px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: all 0.15s;
    }
    .reaction-pill:hover {
        border-color: #94a3b8;
    }
    .reaction-pill.reacted-by-me {
        background: #dbeafe;
        border-color: #60a5fa;
        color: #1d4ed8;
        font-weight: 600;
    }

    .msg-footer-row {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-left: auto;
    }

    .edited-tag {
        font-size: 10.5px;
        color: #64748b;
    }

    .receipt-status {
        font-size: 11px;
        display: inline-flex;
        align-items: center;
    }

    .btn-thread-badge {
        background: #ffffff;
        border: 1px solid #bfdbfe;
        color: #1d4ed8;
        font-size: 11px;
        font-weight: 600;
        padding: 2px 8px;
        border-radius: 6px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .btn-thread-badge:hover {
        background: #dbeafe;
    }

    /* Hover Toolbar (Attached cleanly to top of bubble) */
    .msg-hover-toolbar {
        position: absolute;
        right: 8px;
        top: -14px;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
        display: none;
        align-items: center;
        padding: 2px 4px;
        gap: 1px;
        z-index: 15;
    }

    .message-row.is-me .msg-hover-toolbar {
        right: auto;
        left: 8px;
    }

    .msg-card-bubble:hover .msg-hover-toolbar {
        display: inline-flex;
    }

    .toolbar-btn {
        background: none;
        border: none;
        color: #475569;
        font-size: 12px;
        padding: 3px 6px;
        border-radius: 5px;
        cursor: pointer;
        line-height: 1.2;
    }
    .toolbar-btn:hover {
        background: #f1f5f9;
        color: #0f172a;
    }
    .toolbar-btn.active-pinned {
        color: #f59e0b;
    }

    /* System Messages */
    .system-message-row {
        text-align: center;
        margin: 4px 0;
    }
    .system-message-content {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        color: #64748b;
        font-size: 11.5px;
        padding: 3px 12px;
        border-radius: 999px;
    }
    .system-time {
        margin-left: 6px;
        color: #94a3b8;
        font-size: 10.5px;
    }

    /* Live Typing Bar */
    .typing-indicator-bar {
        padding: 4px 24px;
        font-size: 11.5px;
        color: #64748b;
        background: transparent;
        display: flex;
        align-items: center;
        gap: 6px;
        flex-shrink: 0;
    }
    .typing-dots span {
        display: inline-block;
        width: 4px;
        height: 4px;
        border-radius: 50%;
        background: #2563eb;
        animation: typingDot 1.4s infinite;
    }
    .typing-dots span:nth-child(2) { animation-delay: 0.2s; }
    .typing-dots span:nth-child(3) { animation-delay: 0.4s; }

    @keyframes typingDot {
        0%, 60%, 100% { transform: translateY(0); }
        30% { transform: translateY(-4px); }
    }

    /* ===== 5. UNIFIED MESSAGE COMPOSER CARD ===== */
    .chat-input-wrapper {
        padding: 10px 20px 14px;
        background: #f8fafc;
        flex-shrink: 0;
        position: relative;
        overflow: visible !important;
        z-index: 50;
    }

    .chat-composer-card {
        background: #ffffff;
        border: 1.5px solid #cbd5e1;
        border-radius: 12px;
        position: relative;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04);
        transition: border-color 0.15s, box-shadow 0.15s;
    }

    .chat-composer-card:focus-within {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    /* Floating Mention Autocomplete Dropdown */
    .mention-autocomplete-dropdown {
        position: absolute;
        bottom: calc(100% - 6px);
        left: 20px;
        width: 320px;
        max-width: calc(100% - 40px);
        background: #ffffff;
        border: 1.5px solid #cbd5e1;
        border-radius: 12px;
        box-shadow: 0 16px 36px rgba(15, 23, 42, 0.18), 0 2px 8px rgba(15, 23, 42, 0.06);
        z-index: 99999;
        display: flex;
        flex-direction: column;
    }

    .mention-dropdown-header {
        padding: 8px 12px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        border-radius: 11px 11px 0 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .mention-header-title {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 11px;
        font-weight: 700;
        color: #64748b;
    }

    .btn-close-mention {
        background: none;
        border: none;
        color: #94a3b8;
        font-size: 16px;
        line-height: 1;
        cursor: pointer;
        padding: 0 2px;
    }
    .btn-close-mention:hover {
        color: #0f172a;
    }

    .mention-list {
        max-height: 220px;
        overflow-y: auto;
        padding: 4px;
    }

    .mention-item {
        display: flex;
        align-items: center;
        gap: 9px;
        padding: 6px 10px;
        border-radius: 7px;
        cursor: pointer;
        transition: background 0.1s;
    }
    .mention-item:hover, .mention-item.active {
        background: #eff6ff;
    }
    .mention-item-avatar {
        width: 26px;
        height: 26px;
        border-radius: 7px;
        background: #e2e8f0;
        color: #334155;
        font-size: 10.5px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .mention-item-avatar.broadcast {
        background: #fef3c7;
        color: #b45309;
    }
    .mention-item-info {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
    }
    .mention-item-name {
        font-size: 12.5px;
        font-weight: 700;
        color: #0f172a;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .mention-item-desc {
        font-size: 11px;
        color: #64748b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .mention-role-pill {
        font-size: 10px;
        padding: 1px 5px;
        border-radius: 4px;
        background: #f1f5f9;
        color: #475569;
        font-weight: 600;
        margin-left: auto;
        flex-shrink: 0;
    }
    .mention-role-pill.owner, .mention-role-pill.admin {
        background: #dbeafe;
        color: #1d4ed8;
    }

    /* Reply strip inside composer */
    .reply-preview-bar {
        background: #f0f9ff;
        border-bottom: 1px solid #bae6fd;
        padding: 6px 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }
    .reply-info {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        min-width: 0;
        flex: 1;
    }
    .reply-text {
        display: flex;
        align-items: center;
        gap: 6px;
        min-width: 0;
        overflow: hidden;
    }
    .reply-snippet-inline {
        color: #475569;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .btn-cancel-reply {
        background: none;
        border: none;
        font-size: 16px;
        color: #64748b;
        cursor: pointer;
        line-height: 1;
    }

    /* File preview strip inside composer */
    .file-preview-strip {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 6px 14px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
    }
    .file-name-text { font-weight: 600; color: #0f172a; }
    .file-size-text { color: #64748b; font-size: 11px; }
    .btn-remove-attachment {
        background: none;
        border: none;
        font-size: 16px;
        color: #dc2626;
        cursor: pointer;
        margin-left: auto;
        line-height: 1;
    }

    /* Voice Recording Bar inside composer */
    .voice-recording-bar {
        background: #fef2f2;
        border-bottom: 1px solid #fecaca;
        padding: 8px 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    .record-status-left {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .record-pulse {
        width: 9px;
        height: 9px;
        border-radius: 50%;
        background: #dc2626;
        animation: pulseRecord 1s infinite;
    }
    @keyframes pulseRecord {
        0% { transform: scale(0.9); opacity: 0.8; }
        50% { transform: scale(1.25); opacity: 1; }
        100% { transform: scale(0.9); opacity: 0.8; }
    }
    .record-timer {
        font-family: ui-monospace, monospace;
        font-weight: 700;
        color: #dc2626;
        font-size: 12.5px;
    }
    .record-label { font-size: 12px; color: #991b1b; font-weight: 500; }
    .record-actions { display: flex; gap: 6px; }
    .btn-record-cancel {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        color: #475569;
        font-size: 11.5px;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 6px;
        cursor: pointer;
    }
    .btn-record-send {
        background: #dc2626;
        border: none;
        color: #ffffff;
        font-size: 11.5px;
        font-weight: 600;
        padding: 4px 12px;
        border-radius: 6px;
        cursor: pointer;
    }

    /* Main Textarea Editor inside Composer */
    .composer-editor-row {
        padding: 10px 14px 6px;
    }

    .chat-textarea {
        width: 100%;
        border: none;
        outline: none;
        resize: none;
        max-height: 130px;
        font-family: inherit;
        font-size: 13.5px;
        line-height: 1.45;
        color: #0f172a;
        background: transparent;
        display: block;
    }

    /* Unified Bottom Toolbar inside Composer */
    .composer-toolbar-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 6px 10px 8px 10px;
        border-top: 1px solid #f1f5f9;
        background: #fcfcfd;
        gap: 8px;
    }

    .composer-tools-left {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }

    .tool-cluster {
        display: inline-flex;
        align-items: center;
        gap: 2px;
    }

    .tool-divider {
        width: 1px;
        height: 18px;
        background: #e2e8f0;
        margin: 0 4px;
    }

    .format-btn, .btn-input-tool {
        background: transparent;
        border: none;
        color: #64748b;
        font-size: 13px;
        width: 28px;
        height: 28px;
        border-radius: 6px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: background 0.15s, color 0.15s;
    }
    .format-btn:hover, .btn-input-tool:hover {
        background: #f1f5f9;
        color: #0f172a;
    }

    .btn-input-tool.text-btn {
        width: auto;
        height: 24px;
        font-size: 10.5px;
        font-weight: 700;
        border: 1px solid #cbd5e1;
        border-radius: 5px;
        padding: 0 6px;
        margin: 0 2px;
    }

    .composer-tools-right {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-shrink: 0;
    }

    .shortcut-tip {
        font-size: 11px;
        color: #94a3b8;
    }
    .shortcut-tip kbd {
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        padding: 1px 4px;
        font-size: 10px;
        color: #475569;
    }

    .btn-send-message {
        background: #2563eb;
        border: none;
        color: #ffffff;
        height: 30px;
        padding: 0 12px;
        border-radius: 7px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        font-size: 12px;
        font-weight: 600;
        transition: background 0.15s;
    }
    .btn-send-message:hover { background: #1d4ed8; }
    .btn-send-message:disabled { opacity: 0.6; cursor: not-allowed; }

    .broadcast-readonly-notice {
        background: #fffbeb;
        border: 1px solid #fde68a;
        color: #92400e;
        padding: 10px 16px;
        border-radius: 10px;
        font-size: 12.5px;
        font-weight: 600;
        text-align: center;
    }

    /* ===== 6. RIGHT SLIDE PANES: THREAD & PINNED DRAWER ===== */
    .chat-thread-pane, .chat-pinned-pane {
        width: 340px;
        background: #ffffff;
        border-left: 1px solid #e2e8f0;
        display: flex;
        flex-direction: column;
        flex-shrink: 0;
        height: 100%;
        z-index: 30;
    }

    .thread-header, .pinned-pane-header {
        padding: 12px 16px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-shrink: 0;
    }

    .thread-title-wrap, .pinned-title-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .thread-title, .pinned-pane-title {
        font-size: 13.5px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
    }

    .pinned-count-pill {
        background: #fef3c7;
        color: #b45309;
        font-size: 11px;
        font-weight: 700;
        padding: 1px 7px;
        border-radius: 999px;
    }

    .btn-close-thread {
        background: none;
        border: none;
        color: #64748b;
        font-size: 18px;
        line-height: 1;
        cursor: pointer;
    }

    .thread-viewport {
        flex: 1;
        overflow-y: auto;
        padding: 14px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        background: #f8fafc;
    }

    .thread-parent-box {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px 12px;
    }

    .thread-parent-item, .thread-reply-row {
        display: flex;
        align-items: flex-start;
        gap: 9px;
    }

    .thread-parent-body, .thread-reply-body {
        flex: 1;
        min-width: 0;
    }

    .thread-reply-row {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 8px 11px;
    }
    .thread-reply-row.is-me {
        background: #eff6ff;
        border-color: #bfdbfe;
    }

    .thread-divider {
        text-align: center;
        border-bottom: 1px solid #e2e8f0;
        line-height: 0.1em;
        margin: 6px 0;
    }
    .thread-divider span {
        background: #f8fafc;
        padding: 0 8px;
        font-size: 10.5px;
        font-weight: 700;
        color: #64748b;
    }

    .thread-replies-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .thread-empty, .thread-loading, .thread-error {
        text-align: center;
        font-size: 12px;
        color: #64748b;
        padding: 16px 8px;
    }

    .thread-input-box {
        padding: 10px 12px;
        border-top: 1px solid #e2e8f0;
        background: #ffffff;
        display: flex;
        gap: 8px;
        align-items: flex-end;
        flex-shrink: 0;
    }

    .thread-textarea {
        flex: 1;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 7px 10px;
        font-size: 12.5px;
        outline: none;
        resize: none;
        font-family: inherit;
    }
    .thread-textarea:focus { border-color: #2563eb; }

    .btn-send-thread {
        background: #2563eb;
        border: none;
        color: #ffffff;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    /* Pinned Pane */
    .pinned-pane-body {
        flex: 1;
        overflow-y: auto;
        padding: 14px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        background: #f8fafc;
    }

    .pinned-card {
        border: 1px solid #fde68a;
        background: #fffbeb;
        border-radius: 10px;
        padding: 10px 12px;
    }

    .pinned-card-header {
        display: flex;
        justify-content: space-between;
        font-size: 11px;
        color: #92400e;
        margin-bottom: 4px;
    }
    .pinned-sender { font-weight: 700; }

    .pinned-content {
        font-size: 12.5px;
        color: #78350f;
        margin-bottom: 8px;
        line-height: 1.4;
    }

    .pinned-card-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }
    .btn-jump-msg {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        color: #2563eb;
        font-size: 11px;
        font-weight: 600;
        padding: 3px 8px;
        border-radius: 5px;
        cursor: pointer;
    }
    .btn-unpin-msg {
        background: none;
        border: none;
        color: #dc2626;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
    }

    .pinned-empty {
        text-align: center;
        padding: 40px 20px;
        color: #94a3b8;
        font-size: 12.5px;
    }
    .pinned-empty i { font-size: 24px; margin-bottom: 8px; display: block; }

    /* Empty channel state */
    .no-channel-state {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 40px;
        text-align: center;
        color: #64748b;
    }
    .no-channel-state .empty-icon {
        width: 56px;
        height: 56px;
        border-radius: 14px;
        background: #eff6ff;
        color: #2563eb;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin-bottom: 12px;
    }
    .no-channel-state h3 {
        font-size: 16px;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 4px;
    }
    .no-channel-state p {
        font-size: 13px;
        max-width: 360px;
        margin-bottom: 16px;
    }

    /* ===== 7. POPOVERS & MODALS ===== */
    .qa-popover {
        position: fixed;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 12px;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.15);
        z-index: 1000;
        padding: 12px;
        width: 260px;
    }
    .popover-tabs {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
        padding-bottom: 6px;
        border-bottom: 1px solid #f1f5f9;
    }
    .popover-title { font-size: 12px; font-weight: 700; color: #0f172a; }
    .btn-close-popover { background: none; border: none; font-size: 16px; color: #94a3b8; cursor: pointer; }

    .emoji-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 5px;
    }
    .emoji-btn {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        font-size: 17px;
        padding: 5px 0;
        cursor: pointer;
    }
    .emoji-btn:hover { background: #eff6ff; border-color: #bfdbfe; }

    .gif-popover { width: 300px; }
    .gif-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        max-height: 220px;
        overflow-y: auto;
    }
    .gif-item {
        border: 1px solid #e2e8f0;
        border-radius: 7px;
        overflow: hidden;
        cursor: pointer;
        text-align: center;
        background: #f8fafc;
    }
    .gif-item img {
        width: 100%;
        height: 76px;
        object-fit: cover;
        display: block;
    }
    .gif-item span {
        display: block;
        font-size: 10.5px;
        font-weight: 600;
        color: #475569;
        padding: 3px;
    }

    /* Modals & Form Controls */
    .qa-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.55);
        backdrop-filter: blur(2px);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
    }
    .qa-modal-dialog {
        background: #ffffff;
        border-radius: 14px;
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.2);
        width: 100%;
        overflow: hidden;
    }
    .qa-modal-md { max-width: 520px; }
    .qa-modal-sm { max-width: 400px; }

    .qa-modal-header {
        padding: 14px 20px;
        background: #0f172a;
        color: #ffffff;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .modal-title-group { display: flex; align-items: center; gap: 8px; }
    .qa-modal-title { font-size: 14.5px; font-weight: 700; color: #ffffff; margin: 0; }
    .qa-modal-close { background: none; border: none; color: #94a3b8; font-size: 20px; cursor: pointer; line-height: 1; }

    .qa-modal-body { padding: 18px 20px; }
    .qa-modal-footer {
        padding: 12px 20px;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        display: flex;
        justify-content: flex-end;
        gap: 8px;
    }

    .form-group {
        margin-bottom: 14px;
    }
    .form-group:last-child { margin-bottom: 0; }

    .form-label {
        display: block;
        font-size: 12.5px;
        font-weight: 600;
        color: #334155;
        margin-bottom: 6px;
    }

    .qa-input {
        width: 100%;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 8px 12px;
        font-size: 13px;
        color: #0f172a;
        background: #ffffff;
        outline: none;
        font-family: inherit;
    }
    .qa-input:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }
    .qa-input.with-prefix {
        padding-left: 28px;
    }

    .form-hint {
        display: block;
        font-size: 11px;
        color: #64748b;
        margin-top: 4px;
    }

    .qa-btn-primary {
        background: #2563eb;
        border: 1px solid #1d4ed8;
        color: #ffffff;
        font-size: 12.5px;
        font-weight: 600;
        padding: 7px 14px;
        border-radius: 8px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .qa-btn-primary:hover { background: #1d4ed8; }

    .qa-btn-secondary {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        color: #475569;
        font-size: 12.5px;
        font-weight: 600;
        padding: 7px 14px;
        border-radius: 8px;
        cursor: pointer;
    }
    .qa-btn-secondary:hover { background: #f1f5f9; }

    .type-choice-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
    }
    .type-choice-card {
        border: 1.5px solid #cbd5e1;
        border-radius: 8px;
        padding: 10px 8px;
        cursor: pointer;
        text-align: center;
        position: relative;
    }
    .type-choice-card input { position: absolute; top: 6px; left: 6px; }
    .type-choice-card:has(input:checked) {
        border-color: #2563eb;
        background: #eff6ff;
    }
    .choice-content i { font-size: 16px; margin-bottom: 4px; display: block; }
    .choice-content strong { display: block; font-size: 11.5px; color: #0f172a; }
    .choice-content span { font-size: 10px; color: #64748b; line-height: 1.25; display: block; margin-top: 2px; }

    .input-prefix-wrap {
        position: relative;
        display: flex;
        align-items: center;
    }
    .input-prefix {
        position: absolute;
        left: 10px;
        color: #64748b;
        font-weight: 700;
        font-size: 13px;
    }

    .members-checkbox-list {
        max-height: 140px;
        overflow-y: auto;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 6px;
    }
    .member-check-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12.5px;
        padding: 4px 6px;
        cursor: pointer;
    }

    .modal-subtitle-text {
        font-size: 12.5px;
        color: #64748b;
        margin-bottom: 12px;
    }

    .team-dm-select-list {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .team-dm-choice {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        text-decoration: none;
        color: #0f172a;
        transition: all 0.15s;
    }
    .team-dm-choice:hover { background: #f8fafc; border-color: #93c5fd; }
    .dm-avatar-mini {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: #eff6ff;
        color: #2563eb;
        font-weight: 700;
        font-size: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .dm-choice-info { flex: 1; }
    .dm-choice-info strong { display: block; font-size: 13px; }
    .dm-choice-info span { font-size: 11px; color: #64748b; }

    /* Channel info card */
    .channel-info-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 12px 14px;
    }
    .channel-info-card h4 { font-size: 15px; font-weight: 700; color: #0f172a; margin: 0 0 4px; }
    .channel-info-card .topic-text { font-size: 12.5px; font-weight: 600; color: #2563eb; margin: 0 0 4px; }
    .channel-info-card .desc-text { font-size: 12px; color: #475569; margin: 0; }

    .member-section-wrap { margin-top: 14px; }
    .member-section-heading { font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 8px; }
    .channel-member-list { max-height: 220px; overflow-y: auto; }
    .ch-member-row {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 6px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    .member-avatar {
        width: 28px;
        height: 28px;
        border-radius: 6px;
        background: #e2e8f0;
        color: #334155;
        font-size: 11px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .member-detail { flex: 1; font-size: 12px; }
    .member-detail strong { display: block; color: #0f172a; }
    .member-detail span { font-size: 11px; color: #64748b; }
    .member-role-badge {
        font-size: 10.5px;
        font-weight: 600;
        padding: 2px 7px;
        border-radius: 4px;
        background: #f1f5f9;
        color: #475569;
    }
    .member-role-badge.owner { background: #dbeafe; color: #1d4ed8; }

    @keyframes highlightPulse {
        0% { box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.35); }
        100% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0); }
    }
    .highlight-pulse .msg-card-bubble {
        animation: highlightPulse 2s ease-out;
    }

    /* Responsive */
    @media (max-width: 1024px) {
        .chat-sidebar { width: 220px; }
        .shortcut-tip, .header-action-btn .btn-label { display: none; }
        .msg-content-col { max-width: 82%; }
    }

    @media (max-width: 768px) {
        .page-content {
            padding-bottom: calc(58px + env(safe-area-inset-bottom, 0px)) !important;
        }
        .chat-sidebar { width: 64px; }
        .ws-info, .btn-create-channel span, .chat-sidebar-search, .nav-section-title, .ch-name, .ch-right { display: none; }
        .chat-sidebar-header { justify-content: center; }
        .btn-create-channel { width: 34px; padding: 0; }
        .channel-item { justify-content: center; padding: 9px 0; }
        .ch-left { justify-content: center; }
        .chat-thread-pane, .chat-pinned-pane {
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            width: 100%;
            z-index: 200;
        }
    }
</style>
