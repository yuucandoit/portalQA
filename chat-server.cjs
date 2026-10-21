/**
 * QA Portal Real-Time WebSocket & Broadcast Server
 * Powered by Node.js & 'ws'
 */
const http = require('http');
const { WebSocketServer, WebSocket } = require('ws');

const PORT = parseInt(process.env.CHAT_WS_PORT || '6001', 10);
const HOST = process.env.CHAT_WS_HOST || '0.0.0.0';

// Store connected clients and room channels
// socket -> { userId, userName, channelId }
const clients = new Map();
// channelId -> Set of sockets
const channels = new Map();
// userId -> count of active sockets
const onlineUsers = new Map();

const server = http.createServer((req, res) => {
    // CORS headers for all HTTP requests
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');

    if (req.method === 'OPTIONS') {
        res.writeHead(204);
        res.end();
        return;
    }

    // Health check endpoint
    if (req.method === 'GET' && req.url === '/status') {
        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({
            status: 'online',
            port: PORT,
            connections: clients.size,
            onlineUserIds: Array.from(onlineUsers.keys()),
            channelsActive: channels.size,
            timestamp: new Date().toISOString()
        }));
        return;
    }

    // Internal Broadcast Webhook from Laravel: POST /broadcast
    if (req.method === 'POST' && req.url === '/broadcast') {
        let body = '';
        req.on('data', chunk => { body += chunk; });
        req.on('end', () => {
            try {
                const payload = JSON.parse(body || '{}');
                const { event, channelId, data } = payload;

                if (!event) {
                    res.writeHead(400, { 'Content-Type': 'application/json' });
                    res.end(JSON.stringify({ error: 'Missing event name' }));
                    return;
                }

                const broadcastMsg = JSON.stringify({ event, channelId, data, timestamp: new Date().toISOString() });

                let sentCount = 0;
                if (channelId) {
                    // Send to all clients active in this specific channel
                    const channelSet = channels.get(String(channelId));
                    const notifiedSockets = new Set();
                    if (channelSet) {
                        channelSet.forEach(ws => {
                            if (ws.readyState === WebSocket.OPEN) {
                                ws.send(broadcastMsg);
                                notifiedSockets.add(ws);
                                sentCount++;
                            }
                        });
                    }

                    // For new messages, also notify all other connected clients
                    // so their unread badge and push notifications trigger globally!
                    if (event === 'new_message') {
                        const notifyMsg = JSON.stringify({
                            event: 'chat_notification',
                            channelId,
                            data,
                            timestamp: new Date().toISOString()
                        });
                        clients.forEach((info, ws) => {
                            if (!notifiedSockets.has(ws) && ws.readyState === WebSocket.OPEN) {
                                ws.send(notifyMsg);
                                sentCount++;
                            }
                        });
                    }
                } else {
                    // Global broadcast to all connected clients
                    clients.forEach((info, ws) => {
                        if (ws.readyState === WebSocket.OPEN) {
                            ws.send(broadcastMsg);
                            sentCount++;
                        }
                    });
                }

                res.writeHead(200, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({ success: true, deliveredTo: sentCount }));
            } catch (err) {
                res.writeHead(500, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({ error: err.message }));
            }
        });
        return;
    }

    res.writeHead(404, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify({ error: 'Not Found' }));
});

const wss = new WebSocketServer({ server });

function broadcastPresence() {
    const list = Array.from(onlineUsers.keys());
    const presenceMsg = JSON.stringify({
        event: 'presence_update',
        data: { onlineUsers: list }
    });
    clients.forEach((info, ws) => {
        if (ws.readyState === WebSocket.OPEN) {
            ws.send(presenceMsg);
        }
    });
}

function leaveChannel(ws) {
    const info = clients.get(ws);
    if (!info || !info.channelId) return;

    const channelSet = channels.get(String(info.channelId));
    if (channelSet) {
        channelSet.delete(ws);
        if (channelSet.size === 0) {
            channels.delete(String(info.channelId));
        }
    }
    info.channelId = null;
}

function joinChannel(ws, channelId) {
    leaveChannel(ws);
    const info = clients.get(ws);
    if (!info) return;

    info.channelId = String(channelId);
    if (!channels.has(String(channelId))) {
        channels.set(String(channelId), new Set());
    }
    channels.get(String(channelId)).add(ws);
}

wss.on('connection', (ws, req) => {
    const clientInfo = {
        userId: null,
        userName: 'Anonymous',
        channelId: null,
        connectedAt: Date.now()
    };
    clients.set(ws, clientInfo);

    ws.isAlive = true;
    ws.on('pong', () => { ws.isAlive = true; });

    ws.on('message', (raw) => {
        try {
            const msg = JSON.parse(raw.toString());
            const { type, channelId, userId, userName, data } = msg;

            switch (type) {
                case 'auth': {
                    if (userId) {
                        clientInfo.userId = parseInt(userId, 10);
                        clientInfo.userName = userName || ('User #' + userId);

                        const currentCount = onlineUsers.get(clientInfo.userId) || 0;
                        onlineUsers.set(clientInfo.userId, currentCount + 1);

                        if (channelId) {
                            joinChannel(ws, channelId);
                        }

                        // Send back auth ACK and current online user list
                        ws.send(JSON.stringify({
                            event: 'authenticated',
                            data: {
                                userId: clientInfo.userId,
                                channelId: clientInfo.channelId,
                                onlineUsers: Array.from(onlineUsers.keys())
                            }
                        }));

                        broadcastPresence();
                    }
                    break;
                }

                case 'join_channel': {
                    if (channelId) {
                        joinChannel(ws, channelId);
                        ws.send(JSON.stringify({
                            event: 'channel_joined',
                            data: { channelId }
                        }));
                    }
                    break;
                }

                case 'leave_channel': {
                    leaveChannel(ws);
                    break;
                }

                case 'typing': {
                    const targetCh = channelId || clientInfo.channelId;
                    if (targetCh && channels.has(String(targetCh))) {
                        const typingPayload = JSON.stringify({
                            event: 'user_typing',
                            channelId: targetCh,
                            data: {
                                userId: clientInfo.userId,
                                userName: clientInfo.userName
                            }
                        });
                        channels.get(String(targetCh)).forEach(otherWs => {
                            if (otherWs !== ws && otherWs.readyState === WebSocket.OPEN) {
                                otherWs.send(typingPayload);
                            }
                        });
                    }
                    break;
                }

                case 'stop_typing': {
                    const targetCh = channelId || clientInfo.channelId;
                    if (targetCh && channels.has(String(targetCh))) {
                        const stopPayload = JSON.stringify({
                            event: 'user_stop_typing',
                            channelId: targetCh,
                            data: {
                                userId: clientInfo.userId
                            }
                        });
                        channels.get(String(targetCh)).forEach(otherWs => {
                            if (otherWs !== ws && otherWs.readyState === WebSocket.OPEN) {
                                otherWs.send(stopPayload);
                            }
                        });
                    }
                    break;
                }

                case 'ping': {
                    ws.send(JSON.stringify({ event: 'pong', timestamp: Date.now() }));
                    break;
                }

                default:
                    // Generic relay within channel if needed
                    break;
            }
        } catch (e) {
            console.error('[WS Message Error]', e.message);
        }
    });

    ws.on('close', () => {
        const info = clients.get(ws);
        if (info) {
            leaveChannel(ws);
            if (info.userId) {
                const count = (onlineUsers.get(info.userId) || 1) - 1;
                if (count <= 0) {
                    onlineUsers.delete(info.userId);
                } else {
                    onlineUsers.set(info.userId, count);
                }
                broadcastPresence();
            }
            clients.delete(ws);
        }
    });

    ws.on('error', (err) => {
        console.error('[WS Client Error]', err.message);
    });
});

// Heartbeat interval to clean up stale dead sockets
const heartbeatInterval = setInterval(() => {
    wss.clients.forEach(ws => {
        if (!ws.isAlive) {
            ws.terminate();
            return;
        }
        ws.isAlive = false;
        ws.ping();
    });
}, 30000);

wss.on('close', () => {
    clearInterval(heartbeatInterval);
});

server.listen(PORT, HOST, () => {
    console.log(`[WebSocket Server] Running at ws://${HOST}:${PORT} and http://${HOST}:${PORT}`);
});

