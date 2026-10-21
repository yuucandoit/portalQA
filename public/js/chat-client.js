/**
 * QA Portal - Real-Time Team Chat Client
 * WebSocket-powered instant messaging with attachments, voice notes,
 * Slack-style threading, reactions, receipts, pinned messages, and search.
 */

(function () {
    'use strict';

    // Global state
    let socket = null;
    let reconnectAttempts = 0;
    const maxReconnectAttempts = 30;
    let reconnectTimeout = null;
    let pingInterval = null;

    // Chat context variables initialized from Blade view
    const currentUserId = typeof CURRENT_USER_ID !== 'undefined' ? parseInt(CURRENT_USER_ID, 10) : null;
    const currentUserName = typeof CURRENT_USER_NAME !== 'undefined' ? CURRENT_USER_NAME : 'Anonymous';
    const activeChannelId = typeof ACTIVE_CHANNEL_ID !== 'undefined' && ACTIVE_CHANNEL_ID !== null ? parseInt(ACTIVE_CHANNEL_ID, 10) : null;
    const activeChannelSlug = typeof ACTIVE_CHANNEL_SLUG !== 'undefined' ? ACTIVE_CHANNEL_SLUG : '';
    const csrfToken = typeof CSRF_TOKEN !== 'undefined' ? CSRF_TOKEN : '';
    const wsPort = typeof WS_PORT !== 'undefined' ? WS_PORT : 6001;

    // Interactive states
    let pendingFile = null;
    let replyingTo = null; // { id, sender, text }
    let activeThreadId = null;
    let popoverTargetMessageId = null;
    let typingTimer = null;
    let isTyping = false;
    let activeSearchFilter = '';
    let searchTimer = null;

    // Mention & Autocomplete state
    const channelMembers = (typeof ALL_TEAM_USERS !== 'undefined' && ALL_TEAM_USERS.length > 0)
        ? ALL_TEAM_USERS
        : (typeof CHANNEL_MEMBERS !== 'undefined' ? CHANNEL_MEMBERS : []);
    let mentionDropdownActive = false;
    let mentionSelectedIndex = 0;
    let mentionFilterQuery = '';
    let currentMentionCandidates = [];
    let mentionTargetInput = null;

    // Voice recorder states
    let mediaRecorder = null;
    let audioChunks = [];
    let recordStartTime = null;
    let recordTimerInterval = null;

    /* ==========================================================================
       1. WEBSOCKET CONNECTION & LIFECYCLE
       ========================================================================== */

    function initWebSocket() {
        if (!currentUserId) return;

        const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        const host = window.location.hostname || '127.0.0.1';
        let wsUrl;
        if (typeof window.WS_URL !== 'undefined' && window.WS_URL) {
            wsUrl = window.WS_URL;
        } else if (protocol === 'wss:' || (host !== 'localhost' && host !== '127.0.0.1')) {
            // Over HTTPS or on remote domain, always connect via Nginx reverse proxy /ws endpoint
            wsUrl = `${protocol}//${host}/ws`;
        } else if (!wsPort || wsPort === 443 || wsPort === '443' || wsPort === '0' || wsPort === 0) {
            wsUrl = `${protocol}//${host}/ws`;
        } else {
            wsUrl = `${protocol}//${host}:${wsPort}`;
        }

        updateWsStatus('connecting', 'Menghubungkan...');

        try {
            socket = new WebSocket(wsUrl);
        } catch (e) {
            console.warn('[WS Connect Failed]', e);
            scheduleReconnect();
            return;
        }

        socket.onopen = function () {
            reconnectAttempts = 0;
            updateWsStatus('online', 'Terhubung (Real-time)');

            // Authenticate with server & join active channel
            const authPayload = {
                type: 'auth',
                userId: currentUserId,
                userName: currentUserName,
                channelId: activeChannelId
            };
            socket.send(JSON.stringify(authPayload));

            // Start heartbeat ping
            clearInterval(pingInterval);
            pingInterval = setInterval(() => {
                if (socket && socket.readyState === WebSocket.OPEN) {
                    socket.send(JSON.stringify({ type: 'ping' }));
                }
            }, 25000);
        };

        socket.onmessage = function (event) {
            try {
                const packet = JSON.parse(event.data);
                handleSocketEvent(packet);
            } catch (err) {
                console.error('[WS Parse Error]', err, event.data);
            }
        };

        socket.onclose = function () {
            updateWsStatus('offline', 'Terputus. Mencoba menghubungkan kembali...');
            clearInterval(pingInterval);
            scheduleReconnect();
        };

        socket.onerror = function (err) {
            console.warn('[WS Error]', err);
            socket.close();
        };
    }

    function scheduleReconnect() {
        if (reconnectAttempts >= maxReconnectAttempts) {
            updateWsStatus('offline', 'Koneksi gagal. Silakan muat ulang halaman.');
            return;
        }
        clearTimeout(reconnectTimeout);
        const delay = Math.min(1000 * Math.pow(1.5, reconnectAttempts), 15000);
        reconnectAttempts++;
        reconnectTimeout = setTimeout(initWebSocket, delay);
    }

    function updateWsStatus(state, text) {
        const dot = document.getElementById('wsStatusDot');
        const label = document.getElementById('wsStatusText');
        if (dot) {
            dot.className = 'ws-dot ' + (state === 'online' ? 'online' : 'offline');
        }
        if (label) {
            label.textContent = text;
        }
    }

    /* ==========================================================================
       2. WEBSOCKET EVENT DISPATCHER
       ========================================================================== */

    function handleSocketEvent(packet) {
        const { event, channelId, data } = packet;

        switch (event) {
            case 'authenticated':
                if (data && data.onlineUsers) {
                    updatePresenceIndicators(data.onlineUsers);
                }
                break;

            case 'presence_update':
                if (data && data.onlineUsers) {
                    updatePresenceIndicators(data.onlineUsers);
                }
                break;

            case 'new_message':
                if (data && data.message) {
                    handleIncomingMessage(data.message, channelId, data.isThread);
                }
                break;

            case 'message_edited':
                if (data && data.messageId) {
                    handleMessageEdited(data.messageId, data.message, data.is_edited);
                }
                break;

            case 'message_deleted':
                if (data && data.messageId) {
                    handleMessageDeleted(data.messageId);
                }
                break;

            case 'reaction_updated':
                if (data && data.messageId && data.reactions) {
                    handleReactionUpdated(data.messageId, data.reactions);
                }
                break;

            case 'pin_updated':
                if (data && data.messageId) {
                    handlePinUpdated(data);
                }
                break;

            case 'receipt_updated':
                if (data && data.messageId) {
                    handleReceiptUpdated(data.messageId, data.status);
                }
                break;

            case 'user_typing':
                if (data && data.userId !== currentUserId && channelId == activeChannelId) {
                    showTypingIndicator(data.userName);
                }
                break;

            case 'user_stop_typing':
                if (channelId == activeChannelId) {
                    hideTypingIndicator();
                }
                break;

            case 'channel_created':
                if (data && data.channel) {
                    handleChannelCreatedNotice(data.channel, data.creator);
                }
                break;

            case 'pong':
                // Heartbeat response
                break;

            default:
                break;
        }
    }

    function updatePresenceIndicators(onlineUserIds) {
        const userSet = new Set(onlineUserIds.map(id => parseInt(id, 10)));
        document.querySelectorAll('[id^="presence-"]').forEach(el => {
            const userId = parseInt(el.id.replace('presence-', ''), 10);
            if (userSet.has(userId)) {
                el.classList.remove('offline');
                el.classList.add('online');
            } else {
                el.classList.remove('online');
                el.classList.add('offline');
            }
        });
    }

    /* ==========================================================================
       3. MESSAGE RENDERING & INCOMING DISPATCH
       ========================================================================== */

    function playMessageNotificationSound() {
        try {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return;
            const ctx = new AudioCtx();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(587.33, ctx.currentTime);
            osc.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.12);
            gain.gain.setValueAtTime(0.08, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.18);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + 0.18);
        } catch (e) {
            // AudioContext might be muted until first user interaction
        }
    }

    function handleIncomingMessage(msg, targetChannelId, isThread) {
        const isCurrentChannel = parseInt(targetChannelId, 10) === activeChannelId;
        const isMe = parseInt(msg.user_id, 10) === currentUserId;

        if (!isMe) {
            playMessageNotificationSound();
            // Show push notification if in another channel OR browser tab is in background/minimized
            if (!isCurrentChannel || document.hidden) {
                showChatPushNotification(msg, data.channelName, data.channelSlug, targetChannelId);
            }
        }

        if (isCurrentChannel) {
            if (isThread) {
                // If thread pane is open for this parent, append it
                if (activeThreadId && parseInt(activeThreadId, 10) === parseInt(msg.parent_id, 10)) {
                    appendThreadReply(msg);
                }
                // Update thread badge in main stream
                updateParentThreadBadge(msg.parent_id);
            } else {
                // Main chat timeline
                appendMessageToStream(msg);
                scrollToBottomSmooth();
                // Send read receipt if sender is someone else
                if (msg.user_id !== currentUserId) {
                    markChannelAsRead(activeChannelId);
                }
            }
        } else {
            // Message in another channel: update unread counter badge
            incrementUnreadBadge(targetChannelId);
        }
    }

    function appendMessageToStream(msg) {
        const stream = document.getElementById('messagesStream');
        if (!stream) return;

        // Check if message already exists in DOM (deduplication)
        if (document.getElementById(`msg-${msg.id}`)) return;

        const isMe = parseInt(msg.user_id, 10) === currentUserId;
        const html = renderMessageItemHtml(msg, isMe);
        stream.insertAdjacentHTML('beforeend', html);
    }

    function renderMessageItemHtml(m, isMe) {
        if (m.message_type === 'system') {
            return `
                <div class="system-message-row" id="msg-${m.id}" data-id="${m.id}">
                    <div class="system-message-content">
                        <i class="fas fa-info-circle mr-1"></i>
                        ${escapeAndFormatText(m.message)}
                        <span class="system-time">${m.created_at || ''}</span>
                    </div>
                </div>
            `;
        }

        const initial = m.sender_initial || (m.sender_name ? m.sender_name.substring(0, 2).toUpperCase() : 'U');
        const isPinnedClass = m.is_pinned ? 'is-pinned' : '';
        const isDeletedClass = m.is_deleted ? 'is-deleted' : '';

        // Formatted content
        let bubbleContent = '';
        if (m.is_deleted) {
            bubbleContent = `
                <div class="recalled-text">
                    <i class="fas fa-ban mr-1"></i> ${escapeHtml(m.message || 'Pesan ini telah ditarik kembali')}
                </div>
            `;
        } else {
            // Text
            if (m.message) {
                bubbleContent += `<div class="msg-text-formatted" id="msgText-${m.id}">${escapeAndFormatText(m.message)}</div>`;
            }
            // Media: Image
            if (m.message_type === 'image' && m.file_path) {
                bubbleContent += `
                    <div class="msg-attachment-media">
                        <a href="${m.file_path}" target="_blank" class="media-preview-link">
                            <img src="${m.file_path}" alt="${escapeHtml(m.file_name || 'Gambar')}" loading="lazy" class="chat-attached-img" />
                        </a>
                    </div>
                `;
            }
            // Media: Video
            if (m.message_type === 'video' && m.file_path) {
                bubbleContent += `
                    <div class="msg-attachment-media">
                        <video controls class="chat-attached-video" preload="metadata">
                            <source src="${m.file_path}" type="${m.file_mime || 'video/mp4'}">
                            Browser Anda tidak mendukung pemutar video.
                        </video>
                    </div>
                `;
            }
            // Audio Voice Note
            if (m.message_type === 'audio' && m.file_path) {
                bubbleContent += `
                    <div class="msg-attachment-audio">
                        <div class="voice-note-card">
                            <div class="vn-icon"><i class="fas fa-microphone"></i></div>
                            <div class="vn-player">
                                <audio controls class="chat-audio-element" preload="metadata">
                                    <source src="${m.file_path}" type="${m.file_mime || 'audio/webm'}">
                                </audio>
                            </div>
                        </div>
                    </div>
                `;
            }
            // Document
            if (m.message_type === 'document' && m.file_path) {
                const docIcon = getDocIconClass(m.file_name);
                bubbleContent += `
                    <div class="msg-attachment-doc">
                        <a href="${m.file_path}" target="_blank" download="${escapeHtml(m.file_name || 'berkas')}" class="doc-file-card">
                            <div class="doc-icon"><i class="fas ${docIcon}"></i></div>
                            <div class="doc-info">
                                <span class="doc-name">${escapeHtml(m.file_name || 'Berkas')}</span>
                                <span class="doc-size">${escapeHtml(m.file_size || 'Berkas')}</span>
                            </div>
                            <div class="doc-download-btn"><i class="fas fa-download"></i></div>
                        </a>
                    </div>
                `;
            }
            // GIF
            if ((m.message_type === 'gif' || m.message_type === 'sticker') && m.file_path) {
                bubbleContent += `
                    <div class="msg-attachment-gif">
                        <img src="${m.file_path}" alt="GIF" class="chat-gif-img" />
                    </div>
                `;
            }
        }

        // Quoted reply
        let quotedHtml = '';
        if (m.reply_to) {
            quotedHtml = `
                <div class="msg-quoted-box" onclick="jumpToMessage(${m.reply_to.id})" title="Klik untuk lompat ke pesan asli">
                    <span class="quote-sender">${escapeHtml(m.reply_to.sender || 'User')}</span>
                    <span class="quote-text">${escapeHtml(m.reply_to.message || '')}</span>
                </div>
            `;
        }

        // Receipt status checkmarks (for my messages)
        let receiptHtml = '';
        if (isMe && !m.is_deleted) {
            let icon = '<i class="fas fa-check text-slate-400"></i>';
            if (m.receipt_status === 'read') {
                icon = '<i class="fas fa-check-double text-blue-500"></i>';
            } else if (m.receipt_status === 'delivered') {
                icon = '<i class="fas fa-check-double text-slate-400"></i>';
            }
            receiptHtml = `<span class="receipt-status ${m.receipt_status || 'sent'}" id="receipt-${m.id}" title="Status pengiriman: ${m.receipt_status || 'sent'}">${icon}</span>`;
        }

        // Thread reply badge
        let threadBadgeHtml = '';
        if (m.thread_count && m.thread_count > 0) {
            threadBadgeHtml = `
                <button type="button" class="btn-thread-badge" onclick="openThreadPane(${m.id})">
                    <i class="fas fa-reply-all"></i>
                    <span>${m.thread_count} balasan</span>
                </button>
            `;
        }

        // Reactions
        const reactionsHtml = renderReactionsHtml(m.id, m.reactions || []);

        // Actions toolbar
        const toolbarHtml = `
            <div class="msg-hover-toolbar">
                <button type="button" class="toolbar-btn" onclick="toggleReactionDirect(${m.id}, '👍')" title="Suka (👍)">👍</button>
                <button type="button" class="toolbar-btn" onclick="toggleReactionDirect(${m.id}, '❤️')" title="Hati (❤️)">❤️</button>
                <button type="button" class="toolbar-btn" onclick="toggleReactionDirect(${m.id}, '🔥')" title="Semangat (🔥)">🔥</button>
                <button type="button" class="toolbar-btn" onclick="openReactionPickerForMsg(${m.id}, event)" title="Reaksi Emoji Lainnya">
                    <i class="far fa-smile"></i>
                </button>
                <button type="button" class="toolbar-btn" onclick="openThreadPane(${m.id})" title="Balas dalam Thread">
                    <i class="fas fa-comments"></i>
                </button>
                <button type="button" class="toolbar-btn" onclick="setQuotedReply(${m.id}, '${escapeJs(m.sender_name)}', '${escapeJs((m.message || '').substring(0, 60))}')" title="Kutip Pesan">
                    <i class="fas fa-reply"></i>
                </button>
                <button type="button" class="toolbar-btn ${m.is_pinned ? 'active-pinned' : ''}" onclick="togglePinDirect(${m.id})" title="${m.is_pinned ? 'Lepas Sematan' : 'Sematkan Pesan'}">
                    <i class="fas fa-thumbtack"></i>
                </button>
                ${isMe && !m.is_deleted ? `
                    <button type="button" class="toolbar-btn" onclick="startInlineEdit(${m.id})" title="Edit Pesan">
                        <i class="fas fa-pen"></i>
                    </button>
                    <button type="button" class="toolbar-btn text-rose-500" onclick="deleteMessageDirect(${m.id})" title="Tarik / Hapus Pesan">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                ` : ''}
            </div>
        `;

        return `
            <div class="message-row ${isMe ? 'is-me' : 'is-other'} ${isPinnedClass} ${isDeletedClass}"
                 id="msg-${m.id}"
                 data-id="${m.id}"
                 data-user-id="${m.user_id}"
                 data-sender="${escapeHtml(m.sender_name || 'User')}">

                <div class="msg-avatar-col">
                    <div class="msg-avatar" title="${escapeHtml(m.sender_name || 'User')}">${initial}</div>
                </div>

                <div class="msg-content-col">
                    <div class="msg-card-bubble">
                        ${toolbarHtml}

                        <div class="msg-meta-row">
                            <span class="msg-sender">${escapeHtml(isMe ? 'Anda (' + (m.sender_name || 'User') + ')' : (m.sender_name || 'User'))}</span>
                            <span class="msg-time" title="${m.time_full || ''}">${m.created_at || ''}</span>
                            ${m.is_pinned ? `<span class="msg-pinned-tag"><i class="fas fa-thumbtack"></i> Disematkan</span>` : ''}
                        </div>

                        ${quotedHtml}

                        <div class="msg-bubble">
                            ${bubbleContent}
                        </div>

                        <div class="msg-bottom-bar">
                            <div class="msg-reactions-row" id="reactions-${m.id}">
                                ${reactionsHtml}
                            </div>

                            <div class="msg-footer-row">
                                ${threadBadgeHtml}
                                ${m.is_edited && !m.is_deleted ? `<span class="edited-tag">(diedit)</span>` : ''}
                                ${receiptHtml}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function renderReactionsHtml(messageId, reactions) {
        if (!reactions || reactions.length === 0) return '';
        return reactions.map(r => {
            const reactedByMe = r.user_ids && r.user_ids.includes(currentUserId);
            const userNames = r.users ? r.users.join(', ') : '';
            return `
                <button type="button"
                        class="reaction-pill ${reactedByMe ? 'reacted-by-me' : ''}"
                        onclick="toggleReactionDirect(${messageId}, '${escapeJs(r.emoji)}')"
                        title="${escapeHtml(userNames)}">
                    <span>${r.emoji}</span>
                    <span class="reaction-count">${r.count}</span>
                </button>
            `;
        }).join('');
    }

    function handleMessageEdited(messageId, newContent, isEdited) {
        const textEl = document.getElementById(`msgText-${messageId}`);
        if (textEl) {
            textEl.innerHTML = escapeAndFormatText(newContent);
        }
        // Add (diedit) tag if not already there
        const row = document.getElementById(`msg-${messageId}`);
        if (row && !row.querySelector('.edited-tag')) {
            const footer = row.querySelector('.msg-footer-row');
            if (footer) {
                footer.insertAdjacentHTML('afterbegin', '<span class="edited-tag">(diedit)</span>');
            }
        }
        // Update thread preview if open
        const threadParentText = document.getElementById(`threadParentText-${messageId}`);
        if (threadParentText) {
            threadParentText.innerHTML = escapeAndFormatText(newContent);
        }
    }

    function handleMessageDeleted(messageId) {
        const row = document.getElementById(`msg-${messageId}`);
        if (row) {
            row.classList.add('is-deleted');
            const bubble = row.querySelector('.msg-bubble');
            if (bubble) {
                bubble.innerHTML = `
                    <div class="recalled-text">
                        <i class="fas fa-ban mr-1"></i> Pesan ini telah ditarik kembali
                    </div>
                `;
            }
            const editBtn = row.querySelector('.fa-pen')?.closest('.toolbar-btn');
            const delBtn = row.querySelector('.fa-trash-alt')?.closest('.toolbar-btn');
            if (editBtn) editBtn.remove();
            if (delBtn) delBtn.remove();
        }
    }

    function handleReactionUpdated(messageId, reactions) {
        const container = document.getElementById(`reactions-${messageId}`);
        if (container) {
            container.innerHTML = renderReactionsHtml(messageId, reactions);
        }
        // Also update in thread if present
        const threadReactions = document.getElementById(`threadReactions-${messageId}`);
        if (threadReactions) {
            threadReactions.innerHTML = renderReactionsHtml(messageId, reactions);
        }
    }

    function handlePinUpdated(data) {
        const { messageId, isPinned, pinnedMessagesCount, pinnedMessage } = data;
        const row = document.getElementById(`msg-${messageId}`);
        if (row) {
            if (isPinned) {
                row.classList.add('is-pinned');
                if (!row.querySelector('.msg-pinned-tag')) {
                    const meta = row.querySelector('.msg-meta-row');
                    if (meta) {
                        meta.insertAdjacentHTML('beforeend', '<span class="msg-pinned-tag"><i class="fas fa-thumbtack text-amber-500"></i> Disematkan</span>');
                    }
                }
            } else {
                row.classList.remove('is-pinned');
                const tag = row.querySelector('.msg-pinned-tag');
                if (tag) tag.remove();
            }
        }

        // Update header count and banner
        const headerBadge = document.getElementById('headerPinnedCountBadge');
        if (headerBadge) headerBadge.textContent = pinnedMessagesCount || 0;

        const banner = document.getElementById('pinnedMessageBanner');
        const bannerCount = document.getElementById('pinnedBannerCount');
        const bannerSnippet = document.getElementById('pinnedBannerSnippet');

        if (banner && bannerCount) {
            bannerCount.textContent = pinnedMessagesCount || 0;
            if (pinnedMessagesCount > 0) {
                banner.style.display = 'flex';
                if (pinnedMessage && bannerSnippet) {
                    bannerSnippet.textContent = pinnedMessage.message || 'Pesan disematkan';
                }
            } else {
                banner.style.display = 'none';
            }
        }

        // Update drawer badge
        const drawerBadge = document.getElementById('pinnedPaneCountBadge');
        if (drawerBadge) drawerBadge.textContent = pinnedMessagesCount || 0;

        // If drawer is open, refresh pinned card
        if (isPinned && pinnedMessage) {
            addPinnedCardToDrawer(pinnedMessage);
        } else {
            removePinnedCardFromDrawer(messageId);
        }
    }

    function handleReceiptUpdated(messageId, status) {
        const receipt = document.getElementById(`receipt-${messageId}`);
        if (!receipt) return;

        receipt.className = `receipt-status ${status}`;
        receipt.title = `Status pengiriman: ${status}`;
        if (status === 'read') {
            receipt.innerHTML = '<i class="fas fa-check-double text-blue-500"></i>';
        } else if (status === 'delivered') {
            receipt.innerHTML = '<i class="fas fa-check-double text-slate-400"></i>';
        } else {
            receipt.innerHTML = '<i class="fas fa-check text-slate-400"></i>';
        }
    }

    function updateParentThreadBadge(parentId) {
        if (!parentId) return;
        const row = document.getElementById(`msg-${parentId}`);
        if (!row) return;

        const footer = row.querySelector('.msg-footer-row');
        if (!footer) return;

        let badge = footer.querySelector('.btn-thread-badge');
        if (!badge) {
            badge = document.createElement('button');
            badge.type = 'button';
            badge.className = 'btn-thread-badge';
            badge.onclick = () => window.openThreadPane(parentId);
            badge.innerHTML = `<i class="fas fa-reply-all"></i> <span>1 balasan</span>`;
            footer.appendChild(badge);
        } else {
            const span = badge.querySelector('span');
            const match = span ? span.textContent.match(/\d+/) : null;
            const current = match ? parseInt(match[0], 10) : 0;
            if (span) span.textContent = `${current + 1} balasan`;
        }
    }

    function incrementUnreadBadge(channelId) {
        const badge = document.getElementById(`unread-${channelId}`);
        if (badge) {
            const count = parseInt(badge.textContent || '0', 10) + 1;
            badge.textContent = count;
            badge.style.display = 'inline-flex';
        }
        updateGlobalChatUnreadBadges();
    }

    function updateGlobalChatUnreadBadges() {
        const badges = document.querySelectorAll('[id^="unread-"]');
        let total = 0;
        badges.forEach(b => {
            if (b.style.display !== 'none') {
                total += parseInt(b.textContent || '0', 10) || 0;
            }
        });

        const sidebarBadge = document.getElementById('sidebarChatUnreadBadge');
        const liveTag = document.getElementById('sidebarChatLiveTag');
        const bottomNavBadge = document.getElementById('bottomNavChatUnreadBadge');
        const bottomNavPulse = document.getElementById('bottomNavChatPulse');

        if (sidebarBadge) {
            sidebarBadge.textContent = total > 99 ? '99+' : total;
            sidebarBadge.style.display = total > 0 ? 'inline-flex' : 'none';
        }
        if (liveTag) {
            liveTag.style.display = total > 0 ? 'none' : 'inline-flex';
        }

        if (bottomNavBadge) {
            bottomNavBadge.textContent = total > 99 ? '99+' : total;
            bottomNavBadge.style.display = total > 0 ? 'inline-flex' : 'none';
        }
        if (bottomNavPulse) {
            bottomNavPulse.style.display = total > 0 ? 'none' : 'block';
        }

        if ('setAppBadge' in navigator) {
            if (total > 0) navigator.setAppBadge(total).catch(() => {});
            else navigator.clearAppBadge().catch(() => {});
        }

        const originalTitle = document.title.replace(/^\(\d+\+\?\)\s*/, '');
        if (total > 0) {
            document.title = `(${total}) ${originalTitle}`;
        } else {
            document.title = originalTitle;
        }
    }

    function markChannelAsRead(channelId) {
        if (!channelId) return;
        const badge = document.getElementById(`unread-${channelId}`);
        if (badge) {
            badge.textContent = '0';
            badge.style.display = 'none';
        }
        updateGlobalChatUnreadBadges();

        fetch(`/chat/channels/${channelId}/read`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).catch(err => console.debug('[Mark Read Error]', err));
    }

    function showChatPushNotification(msg, channelName, channelSlug, channelId) {
        if (!('Notification' in window)) return;
        if (Notification.permission !== 'granted') return;

        const sender = msg.sender_name || 'Rekan Tim';
        const ch = channelName || 'Team Chat';
        const text = msg.message || (msg.message_type === 'image' ? 'Mengirim foto' : (msg.message_type === 'audio' ? 'Voice note' : 'Mengirim berkas'));
        const title = `${sender} (#${ch})`;

        const options = {
            body: text,
            icon: '/icons/icon-192x192.png',
            badge: '/icons/icon-96x96.png',
            tag: `chat-ch-${channelId}`,
            renotify: true,
            data: { url: `/chat?channel=${channelSlug || channelId}` }
        };

        if ('serviceWorker' in navigator && navigator.serviceWorker.ready) {
            navigator.serviceWorker.ready.then(reg => {
                reg.showNotification(title, options);
            }).catch(() => {
                new Notification(title, options);
            });
        } else {
            const n = new Notification(title, options);
            n.onclick = function() {
                window.focus();
                if (parseInt(channelId, 10) !== activeChannelId) {
                    window.location.href = `/chat?channel=${channelSlug || channelId}`;
                }
            };
        }
    }

    function handleChannelCreatedNotice(channel, creator) {
        // Show subtle notification toast or console log
        console.log(`[New Channel Created] #${channel.name} oleh ${creator}`);
    }

    /* ==========================================================================
       4. TYPING INDICATOR (CLIENT-SIDE BROADCAST & UI)
       ========================================================================== */

    function broadcastTyping() {
        if (!socket || socket.readyState !== WebSocket.OPEN || !activeChannelId) return;

        if (!isTyping) {
            isTyping = true;
            socket.send(JSON.stringify({
                type: 'typing',
                channelId: activeChannelId,
                userId: currentUserId,
                userName: currentUserName
            }));
        }

        clearTimeout(typingTimer);
        typingTimer = setTimeout(stopTypingBroadcast, 2000);
    }

    function stopTypingBroadcast() {
        if (!socket || socket.readyState !== WebSocket.OPEN || !activeChannelId || !isTyping) return;
        isTyping = false;
        socket.send(JSON.stringify({
            type: 'stop_typing',
            channelId: activeChannelId,
            userId: currentUserId
        }));
    }

    function showTypingIndicator(userName) {
        const bar = document.getElementById('typingIndicatorBar');
        const text = document.getElementById('typingIndicatorText');
        if (bar && text) {
            text.textContent = `${userName} sedang mengetik...`;
            bar.style.display = 'flex';
        }
    }

    function hideTypingIndicator() {
        const bar = document.getElementById('typingIndicatorBar');
        if (bar) {
            bar.style.display = 'none';
        }
    }

    /* ==========================================================================
       5. SENDING MESSAGES (TEXT, ATTACHMENTS, VOICE NOTES)
       ========================================================================== */

    window.submitMessage = function () {
        if (!activeChannelId) return;

        const input = document.getElementById('chatMessageInput');
        const text = input ? input.value.trim() : '';

        if (!text && !pendingFile) {
            return;
        }

        stopTypingBroadcast();

        const formData = new FormData();
        if (text) formData.append('message', text);
        if (pendingFile) formData.append('file', pendingFile);
        if (replyingTo && replyingTo.id) formData.append('reply_to_id', replyingTo.id);

        const sendBtn = document.getElementById('btnSendMessage');
        if (sendBtn) sendBtn.disabled = true;

        fetch(`/chat/channels/${activeChannelId}/messages`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    if (input) {
                        input.value = '';
                        autoResizeTextarea(input);
                    }
                    window.removePendingAttachment();
                    window.cancelReply();
                } else {
                    alert(res.message || 'Gagal mengirim pesan.');
                }
            })
            .catch(err => {
                console.error('[Send Message Error]', err);
                alert('Terjadi kesalahan jaringan saat mengirim pesan.');
            })
            .finally(() => {
                if (sendBtn) sendBtn.disabled = false;
                if (input) input.focus();
            });
    };

    window.handleMessageKeydown = function (event) {
        if (mentionDropdownActive) {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                if (currentMentionCandidates.length > 0) {
                    mentionSelectedIndex = (mentionSelectedIndex + 1) % currentMentionCandidates.length;
                    updateMentionSelectedClass();
                }
                return;
            }
            if (event.key === 'ArrowUp') {
                event.preventDefault();
                if (currentMentionCandidates.length > 0) {
                    mentionSelectedIndex = (mentionSelectedIndex - 1 + currentMentionCandidates.length) % currentMentionCandidates.length;
                    updateMentionSelectedClass();
                }
                return;
            }
            if (event.key === 'Enter' || event.key === 'Tab') {
                event.preventDefault();
                if (currentMentionCandidates[mentionSelectedIndex]) {
                    window.insertMention(currentMentionCandidates[mentionSelectedIndex].name);
                }
                return;
            }
            if (event.key === 'Escape') {
                event.preventDefault();
                hideMentionDropdown();
                return;
            }
        }

        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            window.submitMessage();
        }
    };

    /* ==========================================================================
       MENTION / TAG SYSTEM (@MEMBER, @ALL, @CHANNEL, @HERE)
       ========================================================================== */

    window.checkMentionTrigger = function (input) {
        if (!input) return;
        mentionTargetInput = input;
        const cursorPos = input.selectionStart || 0;
        const textBefore = input.value.substring(0, cursorPos);

        const lastAtIndex = textBefore.lastIndexOf('@');
        if (lastAtIndex !== -1) {
            const charBeforeAt = lastAtIndex > 0 ? textBefore.charAt(lastAtIndex - 1) : ' ';
            if (/[\s(\[\{]/.test(charBeforeAt) || lastAtIndex === 0) {
                const queryText = textBefore.substring(lastAtIndex + 1);
                if (!queryText.includes('\n') && queryText.length <= 25) {
                    mentionFilterQuery = queryText.toLowerCase().trim();
                    showMentionDropdown(mentionFilterQuery, input);
                    return;
                }
            }
        }

        hideMentionDropdown();
    };

    window.triggerMentionPrompt = function () {
        const input = document.getElementById('chatMessageInput');
        if (!input) return;
        input.focus();
        const curVal = input.value;
        const needsSpace = curVal.length > 0 && !curVal.endsWith(' ') && !curVal.endsWith('\n');
        input.value = curVal + (needsSpace ? ' @' : '@');
        input.selectionStart = input.selectionEnd = input.value.length;
        window.checkMentionTrigger(input);
        window.autoResizeTextarea(input);
    };

    function showMentionDropdown(query, input) {
        const dropdown = document.getElementById('mentionDropdown');
        const list = document.getElementById('mentionList');
        if (!dropdown || !list) return;

        const broadcastItems = [
            { id: 'all', name: 'all', label: '@all', desc: 'Beritahu semua anggota di channel ini', isBroadcast: true },
            { id: 'channel', name: 'channel', label: '@channel', desc: 'Seluruh anggota dalam channel ini', isBroadcast: true },
            { id: 'here', name: 'here', label: '@here', desc: 'Anggota yang sedang aktif/online', isBroadcast: true }
        ];

        const userSource = (typeof ALL_TEAM_USERS !== 'undefined' && ALL_TEAM_USERS.length > 0)
            ? ALL_TEAM_USERS
            : ((typeof CHANNEL_MEMBERS !== 'undefined' && CHANNEL_MEMBERS.length > 0) ? CHANNEL_MEMBERS : (channelMembers || []));

        const userItems = userSource.map(m => ({
            id: m.id,
            name: m.name,
            label: `@${m.name}`,
            desc: m.email || 'Anggota Tim QA',
            role: m.role || 'member',
            isBroadcast: false
        }));

        const allCandidates = [...broadcastItems, ...userItems];
        currentMentionCandidates = allCandidates.filter(item => {
            if (!query) return true;
            return item.name.toLowerCase().includes(query) || item.desc.toLowerCase().includes(query);
        });

        if (currentMentionCandidates.length === 0) {
            hideMentionDropdown();
            return;
        }

        mentionSelectedIndex = Math.min(mentionSelectedIndex, currentMentionCandidates.length - 1);
        if (mentionSelectedIndex < 0) mentionSelectedIndex = 0;

        list.innerHTML = currentMentionCandidates.map((c, idx) => {
            const isSelected = idx === mentionSelectedIndex;
            const initial = c.isBroadcast ? '<i class="fas fa-bullhorn"></i>' : (c.name ? c.name.substring(0, 2).toUpperCase() : 'U');
            const roleBadge = c.role && (c.role === 'owner' || c.role === 'admin') ? `<span class="mention-role-pill ${c.role}">${c.role}</span>` : '';
            return `
                <div class="mention-item ${isSelected ? 'active' : ''}"
                     onclick="window.insertMention('${escapeJs(c.name)}')"
                     data-index="${idx}">
                    <div class="mention-item-avatar ${c.isBroadcast ? 'broadcast' : ''}">${initial}</div>
                    <div class="mention-item-info">
                        <span class="mention-item-name">${escapeHtml(c.label)}</span>
                        <span class="mention-item-desc">${escapeHtml(c.desc)}</span>
                    </div>
                    ${roleBadge}
                </div>
            `;
        }).join('');

        dropdown.style.display = 'flex';
        mentionDropdownActive = true;
    }

    function hideMentionDropdown() {
        const dropdown = document.getElementById('mentionDropdown');
        if (dropdown) dropdown.style.display = 'none';
        mentionDropdownActive = false;
        mentionSelectedIndex = 0;
        currentMentionCandidates = [];
    }
    window.hideMentionDropdown = hideMentionDropdown;

    window.insertMention = function (mentionName) {
        const input = mentionTargetInput || document.getElementById('chatMessageInput');
        if (!input) return;

        const cursorPos = input.selectionStart || 0;
        const textBefore = input.value.substring(0, cursorPos);
        const textAfter = input.value.substring(cursorPos);

        const lastAtIndex = textBefore.lastIndexOf('@');
        if (lastAtIndex !== -1) {
            const newBefore = textBefore.substring(0, lastAtIndex) + '@' + mentionName + ' ';
            input.value = newBefore + textAfter;
            const newCursor = newBefore.length;
            input.selectionStart = input.selectionEnd = newCursor;
        }

        hideMentionDropdown();
        input.focus();
        window.autoResizeTextarea(input);
    };

    function updateMentionSelectedClass() {
        const items = document.querySelectorAll('.mention-item');
        items.forEach((item, idx) => {
            if (idx === mentionSelectedIndex) {
                item.classList.add('active');
                item.scrollIntoView({ block: 'nearest' });
            } else {
                item.classList.remove('active');
            }
        });
    }

    window.handleTypingEvent = function () {
        broadcastTyping();
    };

    window.autoResizeTextarea = function (el) {
        if (!el) return;
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight, 160) + 'px';
    };

    /* ==========================================================================
       6. FORMATTING TOOLBAR
       ========================================================================== */

    window.applyFormat = function (type) {
        const textarea = document.getElementById('chatMessageInput');
        if (!textarea) return;

        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = textarea.value;
        const selected = text.substring(start, end);

        let formatted = '';
        let cursorOffset = 0;

        switch (type) {
            case 'bold':
                formatted = `*${selected || 'teks'}*`;
                cursorOffset = selected ? formatted.length : 1;
                break;
            case 'italic':
                formatted = `_${selected || 'teks'}_`;
                cursorOffset = selected ? formatted.length : 1;
                break;
            case 'strike':
                formatted = `~${selected || 'teks'}~`;
                cursorOffset = selected ? formatted.length : 1;
                break;
            case 'code':
                formatted = `\`${selected || 'code'}\``;
                cursorOffset = selected ? formatted.length : 1;
                break;
            case 'codeblock':
                formatted = `\`\`\`\n${selected || '// kode di sini'}\n\`\`\``;
                cursorOffset = selected ? formatted.length : 4;
                break;
            case 'link':
                formatted = `[${selected || 'Judul Tautan'}](https://)`;
                cursorOffset = formatted.length - 1;
                break;
            default:
                formatted = selected;
        }

        textarea.value = text.substring(0, start) + formatted + text.substring(end);
        textarea.focus();
        textarea.setSelectionRange(start + cursorOffset, start + cursorOffset);
        autoResizeTextarea(textarea);
    };

    /* ==========================================================================
       7. FILE ATTACHMENTS
       ========================================================================== */

    window.handleFileSelected = function (fileList) {
        if (!fileList || !fileList[0]) return;
        const file = fileList[0];

        // 50MB maximum check
        if (file.size > 50 * 1024 * 1024) {
            alert('Ukuran berkas melebihi batas maksimum 50MB.');
            return;
        }

        pendingFile = file;

        const strip = document.getElementById('filePreviewStrip');
        const nameEl = document.getElementById('filePreviewName');
        const sizeEl = document.getElementById('filePreviewSize');

        if (strip && nameEl && sizeEl) {
            nameEl.textContent = file.name;
            sizeEl.textContent = formatBytes(file.size);
            strip.style.display = 'flex';
        }
    };

    window.removePendingAttachment = function () {
        pendingFile = null;
        const input = document.getElementById('fileUploadInput');
        if (input) input.value = '';

        const strip = document.getElementById('filePreviewStrip');
        if (strip) strip.style.display = 'none';
    };

    // Drag and drop attachment support
    document.addEventListener('DOMContentLoaded', () => {
        const chatMain = document.getElementById('chatMainArea');
        if (!chatMain) return;

        chatMain.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.stopPropagation();
            chatMain.classList.add('drag-over');
        });

        chatMain.addEventListener('dragleave', (e) => {
            e.preventDefault();
            e.stopPropagation();
            chatMain.classList.remove('drag-over');
        });

        chatMain.addEventListener('drop', (e) => {
            e.preventDefault();
            e.stopPropagation();
            chatMain.classList.remove('drag-over');
            if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                window.handleFileSelected(e.dataTransfer.files);
            }
        });
    });

    /* ==========================================================================
       8. VOICE NOTES RECORDING (WEB AUDIO / MEDIARECORDER)
       ========================================================================== */

    window.startVoiceRecording = function () {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            alert('Browser Anda tidak mendukung perekaman suara audio.');
            return;
        }

        navigator.mediaDevices.getUserMedia({ audio: true })
            .then(stream => {
                audioChunks = [];
                mediaRecorder = new MediaRecorder(stream);

                mediaRecorder.ondataavailable = e => {
                    if (e.data && e.data.size > 0) {
                        audioChunks.push(e.data);
                    }
                };

                mediaRecorder.start(200);
                recordStartTime = Date.now();

                // Show recording UI
                const recordBar = document.getElementById('voiceRecordingBar');
                const chatInputRow = document.getElementById('chatInputRow');
                if (recordBar) recordBar.style.display = 'flex';
                if (chatInputRow) chatInputRow.style.opacity = '0.3';

                // Timer tick
                const timerLabel = document.getElementById('voiceRecordTimer');
                clearInterval(recordTimerInterval);
                recordTimerInterval = setInterval(() => {
                    const elapsed = Math.floor((Date.now() - recordStartTime) / 1000);
                    const mins = String(Math.floor(elapsed / 60)).padStart(2, '0');
                    const secs = String(elapsed % 60).padStart(2, '0');
                    if (timerLabel) timerLabel.textContent = `${mins}:${secs}`;
                }, 500);
            })
            .catch(err => {
                console.error('[Mic Permission Error]', err);
                alert('Akses mikrofon ditolak atau tidak tersedia. Pastikan izin audio diizinkan di browser Anda.');
            });
    };

    window.cancelVoiceRecording = function () {
        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            mediaRecorder.stop();
            mediaRecorder.stream.getTracks().forEach(track => track.stop());
        }
        audioChunks = [];
        cleanupVoiceRecordingUi();
    };

    window.stopAndSendVoiceRecording = function () {
        if (!mediaRecorder || mediaRecorder.state === 'inactive') return;

        const duration = Math.max(1, Math.floor((Date.now() - recordStartTime) / 1000));

        mediaRecorder.onstop = function () {
            const blob = new Blob(audioChunks, { type: 'audio/webm' });
            cleanupVoiceRecordingUi();

            // Convert to Base64
            const reader = new FileReader();
            reader.readAsDataURL(blob);
            reader.onloadend = function () {
                const base64Data = reader.result;

                fetch(`/chat/channels/${activeChannelId}/messages`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        audio_base64: base64Data,
                        message_type: 'audio',
                        file_duration: duration,
                        reply_to_id: replyingTo ? replyingTo.id : null
                    })
                })
                    .then(r => r.json())
                    .then(res => {
                        if (!res.success) {
                            alert(res.message || 'Gagal mengirim voice note.');
                        }
                        window.cancelReply();
                    })
                    .catch(err => console.error('[Voice Note Upload Error]', err));
            };
        };

        mediaRecorder.stop();
        mediaRecorder.stream.getTracks().forEach(track => track.stop());
    };

    function cleanupVoiceRecordingUi() {
        clearInterval(recordTimerInterval);
        const recordBar = document.getElementById('voiceRecordingBar');
        const chatInputRow = document.getElementById('chatInputRow');
        const timerLabel = document.getElementById('voiceRecordTimer');

        if (recordBar) recordBar.style.display = 'none';
        if (chatInputRow) chatInputRow.style.opacity = '1';
        if (timerLabel) timerLabel.textContent = '00:00';
    }

    /* ==========================================================================
       9. QUOTED REPLY & SLACK-STYLE THREADING
       ========================================================================== */

    window.setQuotedReply = function (messageId, senderName, snippet) {
        replyingTo = { id: messageId, sender: senderName, text: snippet };
        const bar = document.getElementById('replyPreviewBar');
        const senderEl = document.getElementById('replySenderName');
        const textEl = document.getElementById('replySnippetText');

        if (bar && senderEl && textEl) {
            senderEl.textContent = senderName;
            textEl.textContent = snippet;
            bar.style.display = 'flex';
        }

        const input = document.getElementById('chatMessageInput');
        if (input) input.focus();
    };

    window.cancelReply = function () {
        replyingTo = null;
        const bar = document.getElementById('replyPreviewBar');
        if (bar) bar.style.display = 'none';
    };

    window.openThreadPane = function (messageId) {
        activeThreadId = messageId;
        const pane = document.getElementById('chatThreadPane');
        const pinnedPane = document.getElementById('chatPinnedPane');

        if (pinnedPane) pinnedPane.style.display = 'none';
        if (pane) pane.style.display = 'flex';

        const parentBox = document.getElementById('threadParentBox');
        const repliesList = document.getElementById('threadRepliesList');

        if (parentBox) parentBox.innerHTML = '<div class="thread-loading"><i class="fas fa-spinner fa-spin"></i> Memuat thread...</div>';
        if (repliesList) repliesList.innerHTML = '';

        fetch(`/chat/channels/${activeChannelId}/thread/${messageId}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(res => res.json())
            .then(res => {
                if (!res.success) {
                    alert('Gagal memuat thread percakapan.');
                    return;
                }
                renderThreadParent(res.parent);
                renderThreadReplies(res.replies);
                const input = document.getElementById('threadMessageInput');
                if (input) input.focus();
            })
            .catch(err => {
                console.error('[Thread Fetch Error]', err);
                if (parentBox) parentBox.innerHTML = '<div class="thread-error">Gagal memuat thread.</div>';
            });
    };

    window.closeThreadPane = function () {
        activeThreadId = null;
        const pane = document.getElementById('chatThreadPane');
        if (pane) pane.style.display = 'none';
    };

    function renderThreadParent(parent) {
        const box = document.getElementById('threadParentBox');
        if (!box) return;

        const initial = parent.sender_initial || (parent.sender_name ? parent.sender_name.substring(0, 2).toUpperCase() : 'U');
        box.innerHTML = `
            <div class="thread-parent-item">
                <div class="msg-avatar">${initial}</div>
                <div class="thread-parent-body">
                    <div class="msg-meta-row">
                        <strong class="msg-sender">${escapeHtml(parent.sender_name)}</strong>
                        <span class="msg-time">${parent.created_at || ''}</span>
                    </div>
                    <div class="msg-text-formatted" id="threadParentText-${parent.id}">
                        ${escapeAndFormatText(parent.message || '')}
                    </div>
                </div>
            </div>
        `;
    }

    function renderThreadReplies(replies) {
        const list = document.getElementById('threadRepliesList');
        const label = document.getElementById('threadReplyCountLabel');
        if (label) label.textContent = `${replies.length} Balasan`;
        if (!list) return;

        if (replies.length === 0) {
            list.innerHTML = `<div class="thread-empty">Belum ada balasan. Jadilah yang pertama membalas!</div>`;
            return;
        }

        list.innerHTML = replies.map(r => {
            const isMe = r.user_id === currentUserId;
            const initial = r.sender_initial || (r.sender_name ? r.sender_name.substring(0, 2).toUpperCase() : 'U');
            return `
                <div class="thread-reply-row ${isMe ? 'is-me' : ''}" id="threadReply-${r.id}">
                    <div class="msg-avatar">${initial}</div>
                    <div class="thread-reply-body">
                        <div class="msg-meta-row">
                            <strong>${escapeHtml(r.sender_name)}</strong>
                            <span class="msg-time">${r.created_at || ''}</span>
                        </div>
                        <div class="msg-text-formatted">${escapeAndFormatText(r.message || '')}</div>
                    </div>
                </div>
            `;
        }).join('');

        scrollThreadToBottom();
    }

    function appendThreadReply(msg) {
        const list = document.getElementById('threadRepliesList');
        if (!list) return;

        // Remove empty state placeholder if present
        const empty = list.querySelector('.thread-empty');
        if (empty) empty.remove();

        const isMe = msg.user_id === currentUserId;
        const initial = msg.sender_initial || (msg.sender_name ? msg.sender_name.substring(0, 2).toUpperCase() : 'U');
        const html = `
            <div class="thread-reply-row ${isMe ? 'is-me' : ''}" id="threadReply-${msg.id}">
                <div class="msg-avatar">${initial}</div>
                <div class="thread-reply-body">
                    <div class="msg-meta-row">
                        <strong>${escapeHtml(msg.sender_name)}</strong>
                        <span class="msg-time">${msg.created_at || ''}</span>
                    </div>
                    <div class="msg-text-formatted">${escapeAndFormatText(msg.message || '')}</div>
                </div>
            </div>
        `;
        list.insertAdjacentHTML('beforeend', html);
        scrollThreadToBottom();
    }

    window.submitThreadMessage = function () {
        if (!activeThreadId || !activeChannelId) return;

        const input = document.getElementById('threadMessageInput');
        const text = input ? input.value.trim() : '';
        if (!text) return;

        fetch(`/chat/channels/${activeChannelId}/messages`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                message: text,
                parent_id: activeThreadId
            })
        })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    if (input) input.value = '';
                } else {
                    alert(res.message || 'Gagal mengirim balasan thread.');
                }
            })
            .catch(err => {
                console.error('[Thread Send Error]', err);
                alert('Terjadi kesalahan jaringan.');
            });
    };

    function scrollThreadToBottom() {
        const vp = document.getElementById('threadViewport');
        if (vp) vp.scrollTop = vp.scrollHeight;
    }

    /* ==========================================================================
       10. PINNED MESSAGES DRAWER & ACTIONS
       ========================================================================== */

    window.togglePinnedDrawer = function () {
        const pane = document.getElementById('chatPinnedPane');
        const threadPane = document.getElementById('chatThreadPane');
        if (!pane) return;

        if (pane.style.display === 'none' || pane.style.display === '') {
            if (threadPane) threadPane.style.display = 'none';
            pane.style.display = 'flex';
        } else {
            pane.style.display = 'none';
        }
    };

    window.togglePinDirect = function (messageId) {
        fetch(`/chat/messages/${messageId}/pin`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(r => r.json())
            .catch(err => console.error('[Pin Error]', err));
    };

    window.unpinMessageDirect = function (messageId) {
        window.togglePinDirect(messageId);
    };

    window.jumpToMessage = function (messageId) {
        const row = document.getElementById(`msg-${messageId}`);
        if (row) {
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
            row.classList.remove('highlight-pulse');
            void row.offsetWidth; // trigger reflow
            row.classList.add('highlight-pulse');
        } else {
            alert('Pesan berada di riwayat sebelumnya atau channel lain.');
        }
    };

    function addPinnedCardToDrawer(pm) {
        const body = document.getElementById('pinnedPaneBody');
        if (!body) return;

        const empty = body.querySelector('.pinned-empty');
        if (empty) empty.remove();

        if (document.getElementById(`pinnedCard-${pm.id}`)) return;

        const html = `
            <div class="pinned-card" id="pinnedCard-${pm.id}">
                <div class="pinned-card-header">
                    <span class="pinned-sender">${escapeHtml(pm.sender_name || 'User')}</span>
                    <span class="pinned-time">${pm.pinned_at || pm.created_at || ''}</span>
                </div>
                <div class="pinned-content">
                    ${escapeHtml(pm.message || '')}
                </div>
                <div class="pinned-card-actions">
                    <button type="button" class="btn-jump-msg" onclick="jumpToMessage(${pm.id})">
                        <i class="fas fa-external-link-alt"></i> Lompat ke Pesan
                    </button>
                    <button type="button" class="btn-unpin-msg" onclick="unpinMessageDirect(${pm.id})">
                        Lepas Sematan
                    </button>
                </div>
            </div>
        `;
        body.insertAdjacentHTML('afterbegin', html);
    }

    function removePinnedCardFromDrawer(messageId) {
        const card = document.getElementById(`pinnedCard-${messageId}`);
        if (card) card.remove();

        const body = document.getElementById('pinnedPaneBody');
        if (body && body.children.length === 0) {
            body.innerHTML = `
                <div class="pinned-empty">
                    <i class="fas fa-thumbtack"></i>
                    <p>Belum ada pesan yang disematkan di channel ini.</p>
                </div>
            `;
        }
    }

    /* ==========================================================================
       11. EMOJI & REACTION POPOVERS
       ========================================================================== */

    window.toggleEmojiPicker = function (event) {
        event.stopPropagation();
        popoverTargetMessageId = null;
        const popover = document.getElementById('emojiPickerPopover');
        const gifPopover = document.getElementById('gifPickerPopover');
        if (gifPopover) gifPopover.style.display = 'none';

        if (!popover) return;
        if (popover.style.display === 'none' || popover.style.display === '') {
            const btnRect = event.currentTarget.getBoundingClientRect();
            popover.style.left = `${Math.max(10, btnRect.left - 120)}px`;
            popover.style.bottom = `${window.innerHeight - btnRect.top + 10}px`;
            popover.style.top = 'auto';
            popover.style.display = 'block';
        } else {
            popover.style.display = 'none';
        }
    };

    window.openReactionPickerForMsg = function (messageId, event) {
        event.stopPropagation();
        popoverTargetMessageId = messageId;
        const popover = document.getElementById('emojiPickerPopover');
        if (!popover) return;

        const rect = event.currentTarget.getBoundingClientRect();
        popover.style.left = `${Math.min(window.innerWidth - 260, rect.left)}px`;
        popover.style.top = `${rect.bottom + 8}px`;
        popover.style.bottom = 'auto';
        popover.style.display = 'block';
    };

    window.closeEmojiPicker = function () {
        popoverTargetMessageId = null;
        const popover = document.getElementById('emojiPickerPopover');
        if (popover) popover.style.display = 'none';
    };

    window.insertEmoji = function (emoji) {
        if (popoverTargetMessageId) {
            window.toggleReactionDirect(popoverTargetMessageId, emoji);
            window.closeEmojiPicker();
            return;
        }

        const input = document.getElementById('chatMessageInput');
        if (input) {
            const start = input.selectionStart;
            const end = input.selectionEnd;
            const text = input.value;
            input.value = text.substring(0, start) + emoji + text.substring(end);
            input.focus();
            input.setSelectionRange(start + emoji.length, start + emoji.length);
            autoResizeTextarea(input);
        }
        window.closeEmojiPicker();
    };

    window.toggleReactionDirect = function (messageId, emoji) {
        fetch(`/chat/messages/${messageId}/reaction`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ emoji: emoji })
        })
            .then(r => r.json())
            .catch(err => console.error('[Reaction Error]', err));
    };

    /* ==========================================================================
       12. GIF PICKER
       ========================================================================== */

    window.toggleGifPicker = function (event) {
        event.stopPropagation();
        const emojiPopover = document.getElementById('emojiPickerPopover');
        if (emojiPopover) emojiPopover.style.display = 'none';

        const popover = document.getElementById('gifPickerPopover');
        if (!popover) return;

        if (popover.style.display === 'none' || popover.style.display === '') {
            const btnRect = event.currentTarget.getBoundingClientRect();
            popover.style.left = `${Math.max(10, btnRect.left - 150)}px`;
            popover.style.bottom = `${window.innerHeight - btnRect.top + 10}px`;
            popover.style.top = 'auto';
            popover.style.display = 'block';
        } else {
            popover.style.display = 'none';
        }
    };

    window.closeGifPicker = function () {
        const popover = document.getElementById('gifPickerPopover');
        if (popover) popover.style.display = 'none';
    };

    window.sendPreloadedGif = function (url, title) {
        if (!activeChannelId) return;

        fetch(`/chat/channels/${activeChannelId}/messages`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                message: title || 'GIF',
                message_type: 'gif',
                metadata: { file_url: url }
            })
        })
            .then(r => r.json())
            .then(res => {
                if (!res.success) {
                    alert(res.message || 'Gagal mengirim GIF.');
                }
                window.closeGifPicker();
            })
            .catch(err => console.error('[Send GIF Error]', err));
    };

    // Close floating popovers on outside click
    document.addEventListener('click', (e) => {
        const emojiPopover = document.getElementById('emojiPickerPopover');
        const gifPopover = document.getElementById('gifPickerPopover');
        const mentionDropdown = document.getElementById('mentionDropdown');

        if (emojiPopover && !emojiPopover.contains(e.target)) {
            emojiPopover.style.display = 'none';
        }
        if (gifPopover && !gifPopover.contains(e.target)) {
            gifPopover.style.display = 'none';
        }
        if (mentionDropdown && !mentionDropdown.contains(e.target) && e.target.id !== 'chatMessageInput') {
            hideMentionDropdown();
        }
    });

    /* ==========================================================================
       13. EDIT & DELETE / RECALL MESSAGES
       ========================================================================== */

    window.startInlineEdit = function (messageId) {
        const textEl = document.getElementById(`msgText-${messageId}`);
        if (!textEl) return;

        const currentText = textEl.innerText.trim();
        const newText = prompt('Ubah pesan Anda:', currentText);

        if (newText === null || newText.trim() === '' || newText === currentText) {
            return;
        }

        fetch(`/chat/messages/${messageId}/edit`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ message: newText })
        })
            .then(r => r.json())
            .then(res => {
                if (!res.success) {
                    alert(res.message || 'Gagal mengubah pesan.');
                }
            })
            .catch(err => console.error('[Edit Message Error]', err));
    };

    window.deleteMessageDirect = function (messageId) {
        if (!confirm('Tarik / hapus pesan ini untuk semua orang?')) {
            return;
        }

        fetch(`/chat/messages/${messageId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(r => r.json())
            .then(res => {
                if (!res.success) {
                    alert(res.message || 'Gagal menghapus pesan.');
                }
            })
            .catch(err => console.error('[Delete Message Error]', err));
    };

    /* ==========================================================================
       14. IN-CHAT SEARCH & FILTER CHIPS
       ========================================================================== */

    window.toggleSearchBox = function () {
        const bar = document.getElementById('inChatSearchBar');
        if (!bar) return;

        if (bar.style.display === 'none' || bar.style.display === '') {
            bar.style.display = 'block';
            const input = document.getElementById('inChatSearchInput');
            if (input) {
                input.focus();
                window.handleInChatSearch(input.value);
            }
        } else {
            bar.style.display = 'none';
            const resBox = document.getElementById('searchResultsBox');
            if (resBox) resBox.style.display = 'none';
        }
    };

    window.setSearchFilter = function (filterType, element) {
        activeSearchFilter = filterType;
        document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
        if (element) element.classList.add('active');

        const input = document.getElementById('inChatSearchInput');
        window.handleInChatSearch(input ? input.value : '');
    };

    window.handleInChatSearch = function (query) {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            const resBox = document.getElementById('searchResultsBox');
            if (!resBox) return;

            const trimmed = (query || '').trim();
            if (!trimmed && !activeSearchFilter) {
                resBox.style.display = 'none';
                return;
            }

            resBox.style.display = 'block';
            resBox.innerHTML = '<div class="search-loading"><i class="fas fa-spinner fa-spin"></i> Mencari...</div>';

            const params = new URLSearchParams({
                q: trimmed,
                filter: activeSearchFilter
            });

            fetch(`/chat/channels/${activeChannelId}/search?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(r => r.json())
                .then(res => {
                    if (!res.success || !res.data) {
                        resBox.innerHTML = '<div class="search-empty">Gagal memuat hasil pencarian.</div>';
                        return;
                    }
                    if (res.data.length === 0) {
                        resBox.innerHTML = '<div class="search-empty">Tidak ada pesan yang cocok dengan kriteria.</div>';
                        return;
                    }

                    resBox.innerHTML = res.data.map(item => `
                        <div class="search-res-item" onclick="jumpToMessage(${item.id})">
                            <div class="res-meta">
                                <strong>${escapeHtml(item.sender_name || 'User')}</strong>
                                <span>${item.created_at || ''}</span>
                            </div>
                            <div class="res-snippet">${escapeHtml(item.message || item.file_name || 'Lampiran')}</div>
                        </div>
                    `).join('');
                })
                .catch(err => {
                    console.error('[Search Error]', err);
                    resBox.innerHTML = '<div class="search-empty">Terjadi kesalahan pencarian.</div>';
                });
        }, 300);
    };

    /* ==========================================================================
       15. MODALS & CHANNEL MANAGEMENT
       ========================================================================== */

    window.openCreateChannelModal = function (defaultType) {
        const modal = document.getElementById('createChannelModal');
        if (!modal) return;

        if (defaultType) {
            const radio = modal.querySelector(`input[name="channel_type"][value="${defaultType}"]`);
            if (radio) {
                radio.checked = true;
                window.onChannelTypeChange(defaultType);
            }
        }
        modal.style.display = 'flex';
        const nameInput = document.getElementById('newChannelName');
        if (nameInput) nameInput.focus();
    };

    window.closeCreateChannelModal = function () {
        const modal = document.getElementById('createChannelModal');
        if (modal) modal.style.display = 'none';
    };

    window.onChannelTypeChange = function (val) {
        const prefix = document.getElementById('channelNamePrefix');
        const memberGroup = document.getElementById('memberSelectGroup');

        if (prefix) {
            if (val === 'broadcast') prefix.textContent = '📢';
            else if (val === 'private_group') prefix.textContent = '🔒';
            else prefix.textContent = '#';
        }

        if (memberGroup) {
            memberGroup.style.display = (val === 'private_group') ? 'block' : 'none';
        }
    };

    window.handleCreateChannelSubmit = function (event) {
        event.preventDefault();

        const form = event.target;
        const type = form.querySelector('input[name="channel_type"]:checked')?.value || 'public_channel';
        const name = document.getElementById('newChannelName')?.value?.trim();
        const topic = document.getElementById('newChannelTopic')?.value?.trim();
        const desc = document.getElementById('newChannelDesc')?.value?.trim();

        const memberCheckboxes = form.querySelectorAll('input[name="invited_members[]"]:checked');
        const memberIds = Array.from(memberCheckboxes).map(cb => parseInt(cb.value, 10));

        const btn = document.getElementById('btnSubmitChannel');
        if (btn) btn.disabled = true;

        fetch('/chat/channels', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                name: name,
                type: type,
                topic: topic,
                description: desc,
                member_ids: memberIds
            })
        })
            .then(async r => {
                const text = await r.text();
                let res;
                try {
                    res = JSON.parse(text);
                } catch (e) {
                    throw new Error('Respon server tidak valid (' + r.status + ')');
                }
                if (!r.ok || !res.success) {
                    const errorMsg = res.message || (res.errors ? Object.values(res.errors).flat().join('\n') : 'Gagal membuat ruang obrolan.');
                    throw new Error(errorMsg);
                }
                return res;
            })
            .then(res => {
                if (res.redirect) {
                    window.location.href = res.redirect;
                }
            })
            .catch(err => {
                console.error('[Create Channel Error]', err);
                alert(err.message || 'Terjadi kesalahan saat membuat channel.');
            })
            .finally(() => {
                if (btn) btn.disabled = false;
            });
    };

    window.openNewDmModal = function () {
        const modal = document.getElementById('newDmModal');
        if (modal) modal.style.display = 'flex';
    };

    window.closeNewDmModal = function () {
        const modal = document.getElementById('newDmModal');
        if (modal) modal.style.display = 'none';
    };

    window.openChannelInfoModal = function () {
        const modal = document.getElementById('channelInfoModal');
        if (modal) modal.style.display = 'flex';
    };

    window.closeChannelInfoModal = function () {
        const modal = document.getElementById('channelInfoModal');
        if (modal) modal.style.display = 'none';
    };

    window.filterChannelList = function (keyword) {
        const lower = (keyword || '').toLowerCase().trim();
        document.querySelectorAll('.channel-item').forEach(item => {
            const name = item.getAttribute('data-name') || '';
            item.style.display = name.includes(lower) ? 'flex' : 'none';
        });
    };

    window.handleViewportScroll = function () {
        // Can be used for pagination if loading past messages
    };

    /* ==========================================================================
       16. UTILITY HELPERS
       ========================================================================== */

    function scrollToBottomSmooth() {
        const vp = document.getElementById('chatMessagesViewport');
        if (!vp) return;
        vp.scrollTo({ top: vp.scrollHeight, behavior: 'smooth' });
    }

    function scrollToBottomInstant() {
        const vp = document.getElementById('chatMessagesViewport');
        if (!vp) return;
        vp.scrollTop = vp.scrollHeight;
    }

    function formatBytes(bytes) {
        if (!bytes || bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    }

    function getDocIconClass(fileName) {
        if (!fileName) return 'fa-file-alt text-slate-500';
        const ext = fileName.split('.').pop().toLowerCase();
        switch (ext) {
            case 'pdf': return 'fa-file-pdf text-rose-500';
            case 'zip':
            case 'rar':
            case 'tar':
            case 'gz': return 'fa-file-archive text-amber-500';
            case 'xls':
            case 'xlsx':
            case 'csv': return 'fa-file-excel text-emerald-600';
            case 'doc':
            case 'docx': return 'fa-file-word text-blue-600';
            default: return 'fa-file-alt text-slate-500';
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function escapeJs(str) {
        if (!str) return '';
        return str.replace(/'/g, "\\'").replace(/"/g, '\\"');
    }

    function escapeAndFormatText(raw) {
        if (!raw) return '';
        let escaped = escapeHtml(raw);

        // Code blocks ```...```
        escaped = escaped.replace(/```([\s\S]*?)```/g, '<pre class="chat-code-block"><code>$1</code></pre>');
        // Monospace `...`
        escaped = escaped.replace(/`([^`]+)`/g, '<code class="chat-inline-code">$1</code>');
        // Bold *text*
        escaped = escaped.replace(/\*([^*]+)\*/g, '<strong>$1</strong>');
        // Italic _text_
        escaped = escaped.replace(/_([^_]+)_/g, '<em>$1</em>');
        // Strike ~text~
        escaped = escaped.replace(/~([^~]+)~/g, '<del>$1</del>');
        // Hyperlinks
        escaped = escaped.replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener noreferrer" class="chat-link">$1</a>');

        // Mentions: @all, @here, @channel, and @Name
        escaped = escaped.replace(/@([a-zA-Z0-9_\-\.]+(?:\s[a-zA-Z0-9_\-\.]+)?)/g, function(match, name) {
            const isBroadcast = ['all', 'channel', 'here'].includes(name.toLowerCase());
            const isMeTagged = isBroadcast || (currentUserName && (currentUserName.toLowerCase().includes(name.toLowerCase()) || name.toLowerCase().includes(currentUserName.toLowerCase())));
            const icon = isBroadcast ? 'fa-bullhorn' : 'fa-at';
            const cls = isMeTagged ? 'chat-mention is-me-tagged' : 'chat-mention';
            return `<span class="${cls}"><i class="fas ${icon}"></i>@${name}</span>`;
        });
        // Linebreaks
        escaped = escaped.replace(/\n/g, '<br>');

        return escaped;
    }

    /* ==========================================================================
       17. INITIALIZATION ON PAGE LOAD
       ========================================================================== */

    window.requestChatNotificationPermission = function () {
        if (!('Notification' in window)) {
            alert('Browser ini tidak mendukung notifikasi Web.');
            return;
        }

        if (Notification.permission === 'granted') {
            alert('Notifikasi desktop & push untuk chat sudah aktif!');
            return;
        }

        Notification.requestPermission().then((permission) => {
            if (permission === 'granted') {
                const btn = document.getElementById('btnChatNotifyPerm');
                if (btn) btn.classList.add('active');
                if ('serviceWorker' in navigator && navigator.serviceWorker.ready) {
                    navigator.serviceWorker.ready.then(reg => {
                        reg.showNotification('QA Portal', {
                            body: 'Notifikasi chat telah aktif! Anda akan mendapatkan pemberitahuan saat ada pesan baru.',
                            icon: '/icons/icon-192x192.png',
                            badge: '/icons/icon-96x96.png'
                        });
                    });
                }
            }
        });
    };

    document.addEventListener('DOMContentLoaded', () => {
        scrollToBottomInstant();
        initWebSocket();
        updateGlobalChatUnreadBadges();
    });

})();
