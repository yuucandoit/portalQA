/**
 * QA Portal - Global Real-Time Chat Notifier
 * Connects in the background on all portal pages (Dashboard, Reports, Bugs, etc.)
 * to update unread chat badges, play audio chime, and trigger desktop push notifications.
 */

(function () {
    'use strict';

    // Only run if user is authenticated and not on the chat studio page (which has its own full client)
    if (typeof CURRENT_USER_ID === 'undefined' || !CURRENT_USER_ID) return;
    if (window.location.pathname.startsWith('/chat')) return;

    const currentUserId = parseInt(CURRENT_USER_ID, 10);
    const wsPort = typeof WS_PORT !== 'undefined' ? WS_PORT : 6001;
    let socket = null;
    let reconnectTimeout = null;
    let reconnectAttempts = 0;
    const maxReconnectAttempts = 20;

    function initGlobalSocket() {
        if (socket && (socket.readyState === WebSocket.OPEN || socket.readyState === WebSocket.CONNECTING)) {
            return;
        }

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

        try {
            socket = new WebSocket(wsUrl);
        } catch (e) {
            scheduleReconnect();
            return;
        }

        socket.onopen = () => {
            reconnectAttempts = 0;
            // Authenticate with user ID but no active channel
            socket.send(JSON.stringify({
                type: 'auth',
                userId: currentUserId,
                userName: typeof CURRENT_USER_NAME !== 'undefined' ? CURRENT_USER_NAME : 'User',
                channelId: null
            }));
        };

        socket.onmessage = (event) => {
            try {
                const packet = JSON.parse(event.data);
                if (packet.event === 'chat_notification' || packet.event === 'new_message') {
                    handleIncomingChatNotification(packet.data || packet);
                }
            } catch (err) {
                // Silently ignore non-JSON messages
            }
        };

        socket.onclose = () => {
            scheduleReconnect();
        };

        socket.onerror = () => {
            try { socket.close(); } catch (e) {}
        };
    }

    function scheduleReconnect() {
        if (reconnectAttempts >= maxReconnectAttempts) return;
        clearTimeout(reconnectTimeout);
        const delay = Math.min(1000 * Math.pow(1.5, reconnectAttempts), 30000);
        reconnectAttempts++;
        reconnectTimeout = setTimeout(initGlobalSocket, delay);
    }

    function handleIncomingChatNotification(data) {
        if (!data || !data.message) return;
        const msg = data.message;

        // Skip our own messages
        if (parseInt(msg.user_id, 10) === currentUserId) return;

        // 1. Play chime audio
        playNotificationChime();

        // 2. Increment sidebar and mobile bottom nav unread badges
        incrementGlobalChatBadge();

        // 3. Show Web Push / Desktop Notification
        const senderName = msg.sender_name || 'Rekan Tim';
        const channelName = data.channelName || 'Team Chat';
        const channelSlug = data.channelSlug || data.channelId || '';
        const preview = msg.message || (msg.message_type === 'image' ? 'Mengirim foto' : (msg.message_type === 'audio' ? 'Voice note' : 'Mengirim lampiran berkas'));

        showPushNotification(senderName, channelName, preview, channelSlug);
    }

    function incrementGlobalChatBadge() {
        const sidebarBadge = document.getElementById('sidebarChatUnreadBadge');
        const liveTag = document.getElementById('sidebarChatLiveTag');
        const bottomNavBadge = document.getElementById('bottomNavChatUnreadBadge');
        const bottomNavPulse = document.getElementById('bottomNavChatPulse');

        let currentCount = 0;
        if (sidebarBadge && sidebarBadge.textContent) {
            currentCount = parseInt(sidebarBadge.textContent.replace('+', ''), 10) || 0;
        }

        const newCount = currentCount + 1;
        const displayCount = newCount > 99 ? '99+' : newCount;

        if (sidebarBadge) {
            sidebarBadge.textContent = displayCount;
            sidebarBadge.style.display = 'inline-flex';
        }
        if (liveTag) {
            liveTag.style.display = 'none';
        }

        if (bottomNavBadge) {
            bottomNavBadge.textContent = displayCount;
            bottomNavBadge.style.display = 'inline-flex';
        }
        if (bottomNavPulse) {
            bottomNavPulse.style.display = 'none';
        }

        // Update PWA App Badge if supported
        if ('setAppBadge' in navigator) {
            navigator.setAppBadge(newCount).catch(() => {});
        }

        // Update page document title
        const originalTitle = document.title.replace(/^\(\d+\+\?\)\s*/, '');
        document.title = `(${displayCount}) ${originalTitle}`;
    }

    function playNotificationChime() {
        try {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return;
            const ctx = new AudioCtx();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(587.33, ctx.currentTime);
            osc.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.12);
            gain.gain.setValueAtTime(0.09, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.2);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + 0.2);
        } catch (e) {}
    }

    function showPushNotification(sender, channel, body, channelSlug) {
        if (!('Notification' in window)) return;

        if (Notification.permission === 'granted') {
            displayNotification(sender, channel, body, channelSlug);
        } else if (Notification.permission === 'default') {
            Notification.requestPermission().then((perm) => {
                if (perm === 'granted') {
                    displayNotification(sender, channel, body, channelSlug);
                }
            });
        }
    }

    function displayNotification(sender, channel, body, channelSlug) {
        const title = `${sender} (#${channel})`;
        const options = {
            body: body,
            icon: '/icons/icon-192x192.png',
            badge: '/icons/icon-96x96.png',
            tag: `chat-${channelSlug}`,
            renotify: true,
            data: { url: `/chat?channel=${channelSlug}` }
        };

        // Try Service Worker notification first for PWA consistency
        if ('serviceWorker' in navigator && navigator.serviceWorker.ready) {
            navigator.serviceWorker.ready.then((reg) => {
                reg.showNotification(title, options);
            }).catch(() => {
                new Notification(title, options);
            });
        } else {
            const n = new Notification(title, options);
            n.onclick = function () {
                window.focus();
                window.location.href = `/chat?channel=${channelSlug}`;
            };
        }
    }

    // Expose prompt permission trigger globally
    window.requestChatNotificationPermission = function () {
        if ('Notification' in window && Notification.permission !== 'granted') {
            Notification.requestPermission();
        }
    };

    // Auto connect on page load
    document.addEventListener('DOMContentLoaded', () => {
        initGlobalSocket();
    });

})();

