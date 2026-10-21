@php
    $isMe = $m['user_id'] === $user->id;
    $isSystem = $m['message_type'] === 'system';
@endphp

@if($isSystem)
    <div class="system-message-row" id="msg-{{ $m['id'] }}" data-id="{{ $m['id'] }}">
        <div class="system-message-content">
            <i class="fas fa-info-circle mr-1"></i>
            {!! preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', e($m['message'])) !!}
            <span class="system-time">{{ $m['created_at'] }}</span>
        </div>
    </div>
@else
    <div class="message-row {{ $isMe ? 'is-me' : 'is-other' }} {{ $m['is_pinned'] ? 'is-pinned' : '' }} {{ $m['is_deleted'] ? 'is-deleted' : '' }}"
         id="msg-{{ $m['id'] }}"
         data-id="{{ $m['id'] }}"
         data-user-id="{{ $m['user_id'] }}"
         data-sender="{{ $m['sender_name'] }}">

        <!-- Sender Avatar -->
        <div class="msg-avatar-col">
            <div class="msg-avatar" title="{{ $m['sender_name'] }}">
                {{ $m['sender_initial'] }}
            </div>
        </div>

        <!-- Unified Message Bubble Container -->
        <div class="msg-content-col">
            <div class="msg-card-bubble">
                <!-- Floating Action Hover Bar (Attached to Bubble) -->
                <div class="msg-hover-toolbar">
                    <button type="button" class="toolbar-btn" onclick="toggleReactionDirect({{ $m['id'] }}, '👍')" title="Suka (👍)">👍</button>
                    <button type="button" class="toolbar-btn" onclick="toggleReactionDirect({{ $m['id'] }}, '❤️')" title="Hati (❤️)">❤️</button>
                    <button type="button" class="toolbar-btn" onclick="toggleReactionDirect({{ $m['id'] }}, '🔥')" title="Semangat (🔥)">🔥</button>
                    <button type="button" class="toolbar-btn" onclick="openReactionPickerForMsg({{ $m['id'] }}, event)" title="Reaksi Emoji Lainnya">
                        <i class="far fa-smile"></i>
                    </button>
                    <button type="button" class="toolbar-btn" onclick="openThreadPane({{ $m['id'] }})" title="Balas dalam Thread">
                        <i class="fas fa-comments"></i>
                    </button>
                    <button type="button" class="toolbar-btn" onclick="setQuotedReply({{ $m['id'] }}, '{{ addslashes($m['sender_name']) }}', '{{ addslashes(Str::limit($m['message'], 60)) }}')" title="Kutip Pesan">
                        <i class="fas fa-reply"></i>
                    </button>
                    <button type="button" class="toolbar-btn {{ $m['is_pinned'] ? 'active-pinned' : '' }}" onclick="togglePinDirect({{ $m['id'] }})" title="{{ $m['is_pinned'] ? 'Lepas Sematan' : 'Sematkan Pesan' }}">
                        <i class="fas fa-thumbtack"></i>
                    </button>

                    @if($isMe && !$m['is_deleted'] && in_array($m['message_type'], ['text', 'image', 'video', 'document']))
                        <button type="button" class="toolbar-btn" onclick="startInlineEdit({{ $m['id'] }})" title="Edit Pesan">
                            <i class="fas fa-pen"></i>
                        </button>
                    @endif

                    @if(($isMe || in_array($activeChannel->members()->where('user_id', $user->id)->first()?->role, ['owner', 'admin'])) && !$m['is_deleted'])
                        <button type="button" class="toolbar-btn text-rose-500" onclick="deleteMessageDirect({{ $m['id'] }})" title="Tarik / Hapus Pesan">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    @endif
                </div>

                <!-- Header (Sender & Time & Pinned Tag) -->
                <div class="msg-meta-row">
                    <span class="msg-sender">{{ $isMe ? 'Anda (' . $m['sender_name'] . ')' : $m['sender_name'] }}</span>
                    <span class="msg-time" title="{{ $m['time_full'] }}">{{ $m['created_at'] }}</span>

                    @if($m['is_pinned'])
                        <span class="msg-pinned-tag" title="Disematkan oleh {{ $m['pinned_by'] ?? 'User' }}">
                            <i class="fas fa-thumbtack"></i> Disematkan
                        </span>
                    @endif
                </div>

                <!-- Quoted Reply (if replying to another message) -->
                @if(!empty($m['reply_to']))
                    <div class="msg-quoted-box" onclick="jumpToMessage({{ $m['reply_to']['id'] }})" title="Klik untuk lompat ke pesan asli">
                        <span class="quote-sender">{{ $m['reply_to']['sender'] }}</span>
                        <span class="quote-text">{{ $m['reply_to']['message'] }}</span>
                    </div>
                @endif

                <!-- Body Content -->
                <div class="msg-bubble">
                    @if($m['is_deleted'])
                        <div class="recalled-text">
                            <i class="fas fa-ban mr-1"></i> {{ $m['message'] ?: 'Pesan ini telah ditarik kembali' }}
                        </div>
                    @else
                        <!-- Text Content (with Markdown Formatting) -->
                        @if(!empty($m['message']))
                            <div class="msg-text-formatted" id="msgText-{{ $m['id'] }}">
                                @php
                                    $parsed = e($m['message']);
                                    $parsed = preg_replace('/```(.*?)```/s', '<pre class="chat-code-block"><code>$1</code></pre>', $parsed);
                                    $parsed = preg_replace('/`([^`]+)`/', '<code class="chat-inline-code">$1</code>', $parsed);
                                    $parsed = preg_replace('/\*(.*?)\*/', '<strong>$1</strong>', $parsed);
                                    $parsed = preg_replace('/_(.*?)_/', '<em>$1</em>', $parsed);
                                    $parsed = preg_replace('/~(.*?)~/', '<del>$1</del>', $parsed);
                                    $parsed = preg_replace('/(https?:\/\/[^\s]+)/', '<a href="$1" target="_blank" rel="noopener noreferrer" class="chat-link">$1</a>', $parsed);
                                    // Mentions: @all, @here, @channel, and @Name
                                    $parsed = preg_replace_callback('/@([a-zA-Z0-9_\-\.]+(?:\s[a-zA-Z0-9_\-\.]+)?)/', function($matches) use ($user) {
                                        $tag = $matches[1];
                                        $isBroadcast = in_array(strtolower($tag), ['all', 'channel', 'here']);
                                        $isMeTagged = $isBroadcast || str_contains(strtolower($user->name), strtolower($tag)) || str_contains(strtolower($tag), strtolower($user->name));
                                        $icon = $isBroadcast ? 'fa-bullhorn' : 'fa-at';
                                        $class = $isMeTagged ? 'chat-mention is-me-tagged' : 'chat-mention';
                                        return '<span class="' . $class . '"><i class="fas ' . $icon . '"></i>@' . $tag . '</span>';
                                    }, $parsed);
                                @endphp
                                {!! nl2br($parsed) !!}
                            </div>
                        @endif

                        <!-- Image Attachment -->
                        @if($m['message_type'] === 'image' && !empty($m['file_path']))
                            <div class="msg-attachment-media">
                                <a href="{{ $m['file_path'] }}" target="_blank" class="media-preview-link">
                                    <img src="{{ $m['file_path'] }}" alt="{{ $m['file_name'] ?: 'Image' }}" loading="lazy" class="chat-attached-img" />
                                </a>
                            </div>
                        @endif

                        <!-- Video Attachment -->
                        @if($m['message_type'] === 'video' && !empty($m['file_path']))
                            <div class="msg-attachment-media">
                                <video controls class="chat-attached-video" preload="metadata">
                                    <source src="{{ $m['file_path'] }}" type="{{ $m['file_mime'] ?: 'video/mp4' }}">
                                    Browser Anda tidak mendukung pemutar video.
                                </video>
                            </div>
                        @endif

                        <!-- Audio Voice Note -->
                        @if($m['message_type'] === 'audio' && !empty($m['file_path']))
                            <div class="msg-attachment-audio">
                                <div class="voice-note-card">
                                    <div class="vn-icon">
                                        <i class="fas fa-microphone"></i>
                                    </div>
                                    <div class="vn-player">
                                        <audio controls class="chat-audio-element" preload="metadata">
                                            <source src="{{ $m['file_path'] }}" type="{{ $m['file_mime'] ?: 'audio/webm' }}">
                                        </audio>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Document Attachment -->
                        @if($m['message_type'] === 'document' && !empty($m['file_path']))
                            <div class="msg-attachment-doc">
                                <a href="{{ $m['file_path'] }}" target="_blank" download="{{ $m['file_name'] }}" class="doc-file-card">
                                    <div class="doc-icon">
                                        @php
                                            $ext = strtolower(pathinfo($m['file_name'] ?? '', PATHINFO_EXTENSION));
                                            $docIcon = match($ext) {
                                                'pdf' => 'fa-file-pdf text-rose-500',
                                                'zip', 'rar', 'tar', 'gz' => 'fa-file-archive text-amber-500',
                                                'xls', 'xlsx', 'csv' => 'fa-file-excel text-emerald-600',
                                                'doc', 'docx' => 'fa-file-word text-blue-600',
                                                default => 'fa-file-alt text-slate-500'
                                            };
                                        @endphp
                                        <i class="fas {{ $docIcon }}"></i>
                                    </div>
                                    <div class="doc-info">
                                        <span class="doc-name">{{ $m['file_name'] }}</span>
                                        <span class="doc-size">{{ $m['file_size'] ?: 'Berkas' }}</span>
                                    </div>
                                    <div class="doc-download-btn">
                                        <i class="fas fa-download"></i>
                                    </div>
                                </a>
                            </div>
                        @endif

                        <!-- GIF or Sticker -->
                        @if(in_array($m['message_type'], ['gif', 'sticker']) && !empty($m['file_path']))
                            <div class="msg-attachment-gif">
                                <img src="{{ $m['file_path'] }}" alt="GIF" class="chat-gif-img" />
                            </div>
                        @endif
                    @endif
                </div>

                <!-- Unified Footer inside Bubble: Reactions, Thread Link, Edited Tag & Receipt -->
                <div class="msg-bottom-bar">
                    <div class="msg-reactions-row" id="reactions-{{ $m['id'] }}">
                        @foreach($m['reactions'] as $r)
                            @php $reactedByMe = in_array($user->id, $r['user_ids']); @endphp
                            <button type="button"
                                    class="reaction-pill {{ $reactedByMe ? 'reacted-by-me' : '' }}"
                                    onclick="toggleReactionDirect({{ $m['id'] }}, '{{ $r['emoji'] }}')"
                                    title="{{ implode(', ', $r['users']) }}">
                                <span>{{ $r['emoji'] }}</span>
                                <span class="reaction-count">{{ $r['count'] }}</span>
                            </button>
                        @endforeach
                    </div>

                    <div class="msg-footer-row">
                        @if($m['thread_count'] > 0)
                            <button type="button" class="btn-thread-badge" onclick="openThreadPane({{ $m['id'] }})">
                                <i class="fas fa-reply-all"></i>
                                <span>{{ $m['thread_count'] }} balasan</span>
                            </button>
                        @endif

                        @if($m['is_edited'] && !$m['is_deleted'])
                            <span class="edited-tag">(diedit)</span>
                        @endif

                        @if($isMe && !$m['is_deleted'])
                            <span class="receipt-status {{ $m['receipt_status'] }}" id="receipt-{{ $m['id'] }}" title="Status pengiriman: {{ $m['receipt_status'] }}">
                                @if($m['receipt_status'] === 'read')
                                    <i class="fas fa-check-double text-blue-500"></i>
                                @elseif($m['receipt_status'] === 'delivered')
                                    <i class="fas fa-check-double text-slate-400"></i>
                                @else
                                    <i class="fas fa-check text-slate-400"></i>
                                @endif
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

    </div>
@endif
