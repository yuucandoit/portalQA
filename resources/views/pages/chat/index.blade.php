@extends('layouts.app')

@section('title', config('app.name', 'QA Portal') . ' - Team Chat')
@section('page_title', 'Team Chat')
@section('page_subtitle', 'Ruang komunikasi real-time tim QA, kolaborasi channel, grup, dan berbagi berkas')

@section('content')
<div class="chat-studio-container" id="chatStudioContainer">

    <!-- 1. LEFT PANEL: CHANNELS, GROUPS & DIRECT MESSAGES -->
    <aside class="chat-sidebar">
        <!-- Unified Top Header & Search -->
        <div class="chat-sidebar-top">
            <div class="chat-sidebar-header">
                <div class="ws-info">
                    <h2 class="ws-name">Ruang Percakapan</h2>
                    <span class="ws-status" id="wsConnectionStatus">
                        <span class="ws-dot offline" id="wsStatusDot"></span>
                        <span id="wsStatusText">Menghubungkan...</span>
                    </span>
                </div>
                <button type="button" class="btn-create-channel" onclick="openCreateChannelModal()" title="Buat Channel atau Grup Baru" aria-label="Buat Channel Baru">
                    <i class="fas fa-plus"></i>
                    <span>Buat Ruang</span>
                </button>
            </div>

            <!-- Search Channels & Team -->
            <div class="chat-sidebar-search">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="channelSearchInput" placeholder="Filter channel atau anggota..." oninput="filterChannelList(this.value)" />
            </div>
        </div>

        <!-- Scrollable Channel Navigation List -->
        <div class="chat-channel-list" id="channelListContainer">

            <!-- Section A: Channels & Broadcasts -->
            <div class="nav-section">
                <div class="nav-section-title">
                    <span>CHANNEL &amp; SIARAN</span>
                    <button type="button" class="btn-section-add" onclick="openCreateChannelModal('public_channel')" title="Tambah Channel Publik">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>

                <div class="channel-group" id="publicChannelList">
                    @forelse($publicChannels as $ch)
                        @php
                            $isActive = $activeChannel && $activeChannel->id === $ch->id;
                            $icon = $ch->type === 'broadcast' ? 'fa-bullhorn' : 'fa-hashtag';
                        @endphp
                        <a href="{{ route('chat.index', ['channel' => $ch->slug]) }}"
                           class="channel-item {{ $isActive ? 'active' : '' }}"
                           data-name="{{ strtolower($ch->name) }}"
                           data-slug="{{ $ch->slug }}"
                           data-id="{{ $ch->id }}">
                            <div class="ch-left">
                                <i class="fas {{ $icon }} ch-icon {{ $ch->type === 'broadcast' ? 'broadcast' : '' }}"></i>
                                <span class="ch-name">{{ $ch->name }}</span>
                            </div>
                            <div class="ch-right">
                                @if($ch->type === 'broadcast')
                                    <span class="badge-broadcast">Siaran</span>
                                @endif
                                <span class="unread-badge" id="unread-{{ $ch->id }}" style="display: none;">0</span>
                            </div>
                        </a>
                    @empty
                        <div class="nav-empty">Belum ada channel publik</div>
                    @endforelse
                </div>
            </div>

            <!-- Section B: Private Groups -->
            <div class="nav-section">
                <div class="nav-section-title">
                    <span>GRUP PRIVAT</span>
                    <button type="button" class="btn-section-add" onclick="openCreateChannelModal('private_group')" title="Buat Grup Privat">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>

                <div class="channel-group" id="privateGroupList">
                    @forelse($privateGroups as $ch)
                        @php
                            $isActive = $activeChannel && $activeChannel->id === $ch->id;
                        @endphp
                        <a href="{{ route('chat.index', ['channel' => $ch->slug]) }}"
                           class="channel-item {{ $isActive ? 'active' : '' }}"
                           data-name="{{ strtolower($ch->name) }}"
                           data-slug="{{ $ch->slug }}"
                           data-id="{{ $ch->id }}">
                            <div class="ch-left">
                                <i class="fas fa-lock ch-icon private"></i>
                                <span class="ch-name">{{ $ch->name }}</span>
                            </div>
                            <div class="ch-right">
                                <span class="unread-badge" id="unread-{{ $ch->id }}" style="display: none;">0</span>
                            </div>
                        </a>
                    @empty
                        <div class="nav-empty">Belum ada grup privat</div>
                    @endforelse
                </div>
            </div>

            <!-- Section C: Direct Messages (1-on-1) -->
            <div class="nav-section">
                <div class="nav-section-title">
                    <span>PESAN LANGSUNG (DM)</span>
                    <button type="button" class="btn-section-add" onclick="openNewDmModal()" title="Mulai Chat Baru">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>

                <div class="channel-group" id="directMessageList">
                    @forelse($teamUsers as $tu)
                        @php
                            $dmSlug = 'dm-' . min($user->id, $tu->id) . '-' . max($user->id, $tu->id);
                            $isActive = $activeChannel && $activeChannel->slug === $dmSlug;
                            $initial = strtoupper(substr($tu->name, 0, 2));
                        @endphp
                        <a href="{{ route('chat.dm', $tu->id) }}"
                           class="channel-item dm-item {{ $isActive ? 'active' : '' }}"
                           data-name="{{ strtolower($tu->name) }}"
                           data-user-id="{{ $tu->id }}">
                            <div class="ch-left">
                                <div class="dm-avatar-wrap">
                                    <div class="dm-avatar">{{ $initial }}</div>
                                    <span class="user-presence-dot offline" id="presence-{{ $tu->id }}"></span>
                                </div>
                                <span class="ch-name">{{ $tu->name }}</span>
                            </div>
                        </a>
                    @empty
                        <div class="nav-empty">Tidak ada anggota tim lain</div>
                    @endforelse
                </div>
            </div>

        </div>
    </aside>

    <!-- 2. MAIN CHAT AREA -->
    <main class="chat-main" id="chatMainArea">
        @if($activeChannel)
            <!-- Unified Top Header Stack -->
            <div class="chat-top-stack">
                <!-- Channel Header -->
                <div class="chat-header">
                    <div class="chat-header-info">
                        <div class="ch-header-icon {{ $activeChannel->type }}">
                            @if($activeChannel->type === 'broadcast')
                                <i class="fas fa-bullhorn"></i>
                            @elseif($activeChannel->type === 'private_group')
                                <i class="fas fa-lock"></i>
                            @elseif($activeChannel->type === 'direct')
                                <i class="fas fa-user"></i>
                            @else
                                <i class="fas fa-hashtag"></i>
                            @endif
                        </div>
                        <div class="ch-header-text">
                            <div class="ch-title-row">
                                <h2 class="ch-title">{{ $activeChannel->getDisplayTitleForUser($user) }}</h2>
                                <span class="ch-type-badge {{ $activeChannel->type }}">
                                    {{ match($activeChannel->type) {
                                        'broadcast' => 'Siaran 1-Arah',
                                        'private_group' => 'Grup Privat',
                                        'direct' => 'Pesan Langsung',
                                        default => 'Publik'
                                    } }}
                                </span>
                            </div>
                            @if($activeChannel->topic)
                                <div class="ch-topic" title="{{ $activeChannel->topic }}">
                                    {{ $activeChannel->topic }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Unified Header Action Group -->
                    <div class="chat-header-actions">
                        <button type="button" class="header-action-btn" id="btnChatNotifyPerm"
                                onclick="if (window.requestChatNotificationPermission) window.requestChatNotificationPermission();" title="Aktifkan Notifikasi Desktop/Push">
                            <i class="fas fa-bell"></i>
                            <span class="btn-label">Notif</span>
                        </button>

                        <button type="button" class="header-action-btn" onclick="toggleSearchBox()" title="Cari di obrolan ini">
                            <i class="fas fa-search"></i>
                            <span class="btn-label">Cari</span>
                        </button>

                        <button type="button" class="header-action-btn {{ $pinnedMessages->count() > 0 ? 'has-pinned' : '' }}"
                                onclick="togglePinnedDrawer()" title="Pesan Tersemat ({{ $pinnedMessages->count() }})">
                            <i class="fas fa-thumbtack"></i>
                            <span class="btn-label">Sematan</span>
                            <span class="action-count-pill" id="headerPinnedCountBadge">{{ $pinnedMessages->count() }}</span>
                        </button>

                        <button type="button" class="header-action-btn" onclick="openChannelInfoModal()" title="Info & Anggota Channel">
                            <i class="fas fa-users"></i>
                            <span class="btn-label">Anggota</span>
                            <span class="action-count-pill">{{ $activeChannel->members()->count() }}</span>
                        </button>
                    </div>
                </div>

                <!-- Pinned Message Alert Strip (Integrated under header) -->
                <div class="pinned-banner" id="pinnedMessageBanner" style="{{ $pinnedMessages->count() > 0 ? 'display: flex;' : 'display: none;' }}">
                    <div class="pinned-banner-left" onclick="togglePinnedDrawer()">
                        <i class="fas fa-thumbtack text-amber-500"></i>
                        <span class="pinned-count-text">
                            <strong id="pinnedBannerCount">{{ $pinnedMessages->count() }}</strong> Tersemat:
                        </span>
                        <span class="pinned-snippet" id="pinnedBannerSnippet">
                            {{ $pinnedMessages->first()['message'] ?? 'Pesan penting channel disematkan di sini.' }}
                        </span>
                    </div>
                    <button type="button" class="btn-pinned-view" onclick="togglePinnedDrawer()">
                        Lihat Semua
                    </button>
                </div>

                <!-- In-Chat Quick Search Bar (Collapsible under header) -->
                <div class="in-chat-search-bar" id="inChatSearchBar" style="display: none;">
                    <div class="search-bar-top-row">
                        <div class="search-input-group">
                            <i class="fas fa-search"></i>
                            <input type="text" id="inChatSearchInput" placeholder="Ketik kata kunci pesan, nama berkas, atau tautan..." oninput="handleInChatSearch(this.value)" />
                            <button type="button" class="btn-close-search" onclick="toggleSearchBox()" title="Tutup Pencarian">&times;</button>
                        </div>
                        <div class="search-filter-chips">
                            <button type="button" class="filter-chip active" data-filter="" onclick="setSearchFilter('', this)">Semua</button>
                            <button type="button" class="filter-chip" data-filter="media" onclick="setSearchFilter('media', this)">Media</button>
                            <button type="button" class="filter-chip" data-filter="documents" onclick="setSearchFilter('documents', this)">Dokumen</button>
                            <button type="button" class="filter-chip" data-filter="links" onclick="setSearchFilter('links', this)">Tautan</button>
                            <button type="button" class="filter-chip" data-filter="pinned" onclick="setSearchFilter('pinned', this)">Tersemat</button>
                        </div>
                    </div>
                    <!-- Search Results Dropdown -->
                    <div class="search-results-box" id="searchResultsBox" style="display: none;"></div>
                </div>
            </div>

            <!-- Chat Message Feed -->
            <div class="chat-messages-viewport" id="chatMessagesViewport" onscroll="handleViewportScroll()">
                <!-- Compact Channel Intro Header -->
                <div class="channel-welcome-banner">
                    <div class="welcome-icon">
                        @if($activeChannel->type === 'direct')
                            <i class="fas fa-user-friends"></i>
                        @elseif($activeChannel->type === 'broadcast')
                            <i class="fas fa-bullhorn"></i>
                        @else
                            <i class="fas fa-comments"></i>
                        @endif
                    </div>
                    <div class="welcome-text-wrap">
                        <h3 class="welcome-title">{{ $activeChannel->getDisplayTitleForUser($user) }}</h3>
                        <p class="welcome-desc">
                            {{ $activeChannel->description ?: 'Awal dari riwayat percakapan ruang ini. Seluruh pesan dan berkas tersinkronisasi secara real-time.' }}
                        </p>
                    </div>
                </div>

                <!-- Messages Stream -->
                <div class="messages-stream" id="messagesStream">
                    @php $lastDate = null; @endphp
                    @foreach($messages as $m)
                        @php
                            $msgDate = $m['date_str'];
                            $isMe = $m['user_id'] === $user->id;
                        @endphp

                        @if($msgDate !== $lastDate)
                            <div class="date-divider">
                                <span>{{ \Carbon\Carbon::parse($msgDate)->translatedFormat('l, d F Y') }}</span>
                            </div>
                            @php $lastDate = $msgDate; @endphp
                        @endif

                        @include('pages.chat.partials.message-item', ['m' => $m, 'isMe' => $isMe])
                    @endforeach
                </div>
            </div>

            <!-- Live Typing Notification Bar -->
            <div class="typing-indicator-bar" id="typingIndicatorBar" style="display: none;">
                <span class="typing-dots">
                    <span></span><span></span><span></span>
                </span>
                <span id="typingIndicatorText">Seseorang sedang mengetik...</span>
            </div>

            <!-- Unified Chat Composer Area -->
            <div class="chat-input-wrapper" style="position: relative;">
                <!-- Floating Mention Autocomplete Dropdown (Outside composer card to prevent clipping) -->
                <div id="mentionDropdown" class="mention-autocomplete-dropdown" style="display: none;">
                    <div class="mention-dropdown-header">
                        <div class="mention-header-title">
                            <i class="fas fa-at text-blue-500"></i>
                            <span>Pilih Anggota Tim atau Siaran</span>
                        </div>
                        <button type="button" class="btn-close-mention" onclick="hideMentionDropdown()" title="Tutup">&times;</button>
                    </div>
                    <div class="mention-list" id="mentionList"></div>
                </div>

                @if($activeChannel->type === 'broadcast' && !in_array($activeChannel->members()->where('user_id', $user->id)->first()?->role, ['owner', 'admin']))
                    <div class="broadcast-readonly-notice">
                        <i class="fas fa-bullhorn text-amber-500 mr-2"></i>
                        Hanya Admin yang dapat mengirim pesan pada Channel Siaran ini.
                    </div>
                @else
                    <div class="chat-composer-card">
                        <!-- 1. Quoted Reply Context Strip (Inside Composer) -->
                        <div class="reply-preview-bar" id="replyPreviewBar" style="display: none;">
                            <div class="reply-info">
                                <i class="fas fa-reply text-blue-500"></i>
                                <div class="reply-text">
                                    <span>Membalas <strong id="replySenderName">User</strong>:</span>
                                    <span id="replySnippetText" class="reply-snippet-inline">Kutipan pesan...</span>
                                </div>
                            </div>
                            <button type="button" class="btn-cancel-reply" onclick="cancelReply()" title="Batal mengutip">&times;</button>
                        </div>

                        <!-- 2. Attached File Strip (Inside Composer) -->
                        <div class="file-preview-strip" id="filePreviewStrip" style="display: none;">
                            <i class="fas fa-paperclip text-blue-500"></i>
                            <span class="file-name-text" id="filePreviewName">dokumen.pdf</span>
                            <span class="file-size-text" id="filePreviewSize">2.4 MB</span>
                            <button type="button" class="btn-remove-attachment" onclick="removePendingAttachment()" title="Hapus lampiran">&times;</button>
                        </div>

                        <!-- 3. Voice Recording Bar (Inside Composer) -->
                        <div class="voice-recording-bar" id="voiceRecordingBar" style="display: none;">
                            <div class="record-status-left">
                                <div class="record-pulse"></div>
                                <span class="record-timer" id="voiceRecordTimer">00:00</span>
                                <span class="record-label">Merekam Voice Note...</span>
                            </div>
                            <div class="record-actions">
                                <button type="button" class="btn-record-cancel" onclick="cancelVoiceRecording()">
                                    <i class="fas fa-trash"></i> Batal
                                </button>
                                <button type="button" class="btn-record-send" onclick="stopAndSendVoiceRecording()">
                                    <i class="fas fa-paper-plane"></i> Kirim Audio
                                </button>
                            </div>
                        </div>

                        <!-- 4. Main Textarea Input -->
                        <div class="composer-editor-row" id="chatInputRow">
                            <textarea id="chatMessageInput" rows="1" class="chat-textarea"
                                      placeholder="Kirim pesan atau tag @anggota ke {{ $activeChannel->getDisplayTitleForUser($user) }}..."
                                      oninput="autoResizeTextarea(this); handleTypingEvent(); checkMentionTrigger(this);"
                                      onkeydown="handleMessageKeydown(event)"></textarea>
                        </div>

                        <!-- 5. Unified Bottom Toolbar (Format + Media + Send) -->
                        <div class="composer-toolbar-row">
                            <div class="composer-tools-left">
                                <!-- Text Formatting Group -->
                                <div class="tool-cluster">
                                    <button type="button" class="format-btn" onclick="applyFormat('bold')" title="Tebal (*teks*)">
                                        <i class="fas fa-bold"></i>
                                    </button>
                                    <button type="button" class="format-btn" onclick="applyFormat('italic')" title="Miring (_teks_)">
                                        <i class="fas fa-italic"></i>
                                    </button>
                                    <button type="button" class="format-btn" onclick="applyFormat('strike')" title="Coret (~teks~)">
                                        <i class="fas fa-strikethrough"></i>
                                    </button>
                                    <button type="button" class="format-btn" onclick="applyFormat('code')" title="Monospace (`code`)">
                                        <i class="fas fa-code"></i>
                                    </button>
                                    <button type="button" class="format-btn" onclick="applyFormat('codeblock')" title="Code Block (```)">
                                        <i class="fas fa-file-code"></i>
                                    </button>
                                    <button type="button" class="format-btn" onclick="applyFormat('link')" title="Tautan ([judul](url))">
                                        <i class="fas fa-link"></i>
                                    </button>
                                </div>

                                <span class="tool-divider"></span>

                                <!-- Media, Mention & Rich Picker Group -->
                                <div class="tool-cluster">
                                    <button type="button" class="btn-input-tool" onclick="triggerMentionPrompt()" title="Sebut / Tag Anggota (@)">
                                        <i class="fas fa-at"></i>
                                    </button>

                                    <button type="button" class="btn-input-tool" onclick="document.getElementById('fileUploadInput').click()" title="Lampirkan Berkas (Foto, Video, PDF, ZIP)">
                                        <i class="fas fa-paperclip"></i>
                                    </button>
                                    <input type="file" id="fileUploadInput" style="display: none;" onchange="handleFileSelected(this.files)" />

                                    <button type="button" class="btn-input-tool" onclick="toggleEmojiPicker(event)" title="Pilih Emoji">
                                        <i class="far fa-smile"></i>
                                    </button>

                                    <button type="button" class="btn-input-tool text-btn" onclick="toggleGifPicker(event)" title="Kirim GIF">
                                        GIF
                                    </button>

                                    <button type="button" class="btn-input-tool mic-btn" id="btnMicRecord" onclick="startVoiceRecording()" title="Rekam Voice Note">
                                        <i class="fas fa-microphone"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="composer-tools-right">
                                <span class="shortcut-tip"><kbd>Enter</kbd> kirim &bull; <kbd>Shift+Enter</kbd> baris baru</span>
                                <button type="button" class="btn-send-message" id="btnSendMessage" onclick="submitMessage()" title="Kirim Pesan (Enter)">
                                    <i class="fas fa-paper-plane"></i>
                                    <span>Kirim</span>
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

        @else
            <!-- Empty State when no channel exists -->
            <div class="no-channel-state">
                <div class="empty-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <h3>Pilih atau Buat Ruang Percakapan</h3>
                <p>Silakan pilih salah satu channel atau anggota tim di sebelah kiri untuk mulai berdiskusi secara real-time.</p>
                <button type="button" class="qa-btn-primary blue" onclick="openCreateChannelModal()">
                    <i class="fas fa-plus"></i> Buat Ruang Baru
                </button>
            </div>
        @endif
    </main>

    <!-- 3. RIGHT SIDEBAR: THREADING PANE (SLACK-STYLE) -->
    <aside class="chat-thread-pane" id="chatThreadPane" style="display: none;">
        <div class="thread-header">
            <div class="thread-title-wrap">
                <i class="fas fa-comments text-blue-500"></i>
                <h3 class="thread-title">Thread Balasan</h3>
            </div>
            <button type="button" class="btn-close-thread" onclick="closeThreadPane()" title="Tutup Thread">&times;</button>
        </div>

        <div class="thread-viewport" id="threadViewport">
            <!-- Parent Root Message -->
            <div class="thread-parent-box" id="threadParentBox"></div>

            <div class="thread-divider">
                <span id="threadReplyCountLabel">Balasan</span>
            </div>

            <!-- Thread Replies Stream -->
            <div class="thread-replies-list" id="threadRepliesList"></div>
        </div>

        <!-- Thread Reply Input Box -->
        <div class="thread-input-box">
            <textarea id="threadMessageInput" rows="1" class="thread-textarea"
                      placeholder="Balas dalam thread ini..."
                      onkeydown="if(event.key==='Enter' && !event.shiftKey){ event.preventDefault(); submitThreadMessage(); }"></textarea>
            <button type="button" class="btn-send-thread" onclick="submitThreadMessage()" title="Kirim Balasan">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </aside>

    <!-- 4. RIGHT SIDEBAR: PINNED MESSAGES DRAWER -->
    <aside class="chat-pinned-pane" id="chatPinnedPane" style="display: none;">
        <div class="pinned-pane-header">
            <div class="pinned-title-wrap">
                <i class="fas fa-thumbtack text-amber-500"></i>
                <h3 class="pinned-pane-title">Pesan Tersemat</h3>
                <span class="pinned-count-pill" id="pinnedPaneCountBadge">{{ $pinnedMessages->count() }}</span>
            </div>
            <button type="button" class="btn-close-thread" onclick="togglePinnedDrawer()" title="Tutup Panel">&times;</button>
        </div>
        <div class="pinned-pane-body" id="pinnedPaneBody">
            @forelse($pinnedMessages as $pm)
                <div class="pinned-card" id="pinnedCard-{{ $pm['id'] }}">
                    <div class="pinned-card-header">
                        <span class="pinned-sender">{{ $pm['sender_name'] }}</span>
                        <span class="pinned-time">{{ $pm['pinned_at'] ?: $pm['created_at'] }}</span>
                    </div>
                    <div class="pinned-content">
                        {!! nl2br(e($pm['message'])) !!}
                    </div>
                    <div class="pinned-card-actions">
                        <button type="button" class="btn-jump-msg" onclick="jumpToMessage({{ $pm['id'] }})">
                            <i class="fas fa-external-link-alt"></i> Lompat ke Pesan
                        </button>
                        <button type="button" class="btn-unpin-msg" onclick="unpinMessageDirect({{ $pm['id'] }})">
                            Lepas Sematan
                        </button>
                    </div>
                </div>
            @empty
                <div class="pinned-empty">
                    <i class="fas fa-thumbtack"></i>
                    <p>Belum ada pesan yang disematkan di ruang ini.</p>
                </div>
            @endforelse
        </div>
    </aside>

</div>

<!-- FLOATING EMOJI & REACTION POPOVER -->
<div id="emojiPickerPopover" class="qa-popover" style="display: none;">
    <div class="popover-tabs">
        <span class="popover-title">Pilih Emoji</span>
        <button type="button" class="btn-close-popover" onclick="closeEmojiPicker()">&times;</button>
    </div>
    <div class="emoji-grid" id="emojiGrid">
        @foreach(['👍','❤️','😂','🔥','🎉','🚀','👏','🐛','🎯','✅','❌','👀','🤔','💯','🙌','⚡','💡','📌','☕','🥳'] as $emo)
            <button type="button" class="emoji-btn" onclick="insertEmoji('{{ $emo }}')">{{ $emo }}</button>
        @endforeach
    </div>
</div>

<!-- FLOATING GIF PICKER POPOVER -->
<div id="gifPickerPopover" class="qa-popover gif-popover" style="display: none;">
    <div class="popover-tabs">
        <span class="popover-title">Kirim GIF Cepat</span>
        <button type="button" class="btn-close-popover" onclick="closeGifPicker()">&times;</button>
    </div>
    <div class="gif-grid" id="gifGrid">
        <div class="gif-item" onclick="sendPreloadedGif('https://media.giphy.com/media/26u4cqiYI30juCOGY/giphy.gif', 'LGTM Approved')">
            <img src="https://media.giphy.com/media/26u4cqiYI30juCOGY/giphy.gif" alt="Approved" loading="lazy" />
            <span>LGTM / Approved</span>
        </div>
        <div class="gif-item" onclick="sendPreloadedGif('https://media.giphy.com/media/l41lI4bYmcsPJX9Go/giphy.gif', 'Bug Found')">
            <img src="https://media.giphy.com/media/l41lI4bYmcsPJX9Go/giphy.gif" alt="Bug Found" loading="lazy" />
            <span>Bug Hunter</span>
        </div>
        <div class="gif-item" onclick="sendPreloadedGif('https://media.giphy.com/media/13HgwGsXF0aiGY/giphy.gif', 'Success Passed')">
            <img src="https://media.giphy.com/media/13HgwGsXF0aiGY/giphy.gif" alt="Passed" loading="lazy" />
            <span>All Tests Passed</span>
        </div>
        <div class="gif-item" onclick="sendPreloadedGif('https://media.giphy.com/media/unQ3IJU2RG7DO/giphy.gif', 'Work in Progress')">
            <img src="https://media.giphy.com/media/unQ3IJU2RG7DO/giphy.gif" alt="Coding" loading="lazy" />
            <span>Testing Mode</span>
        </div>
    </div>
</div>

<!-- MODAL: CREATE CHANNEL OR GROUP -->
<div id="createChannelModal" class="qa-modal-overlay" style="display: none;" onclick="if(event.target === this) closeCreateChannelModal()">
    <div class="qa-modal-dialog qa-modal-md">
        <div class="qa-modal-header">
            <div class="modal-title-group">
                <i class="fas fa-plus-circle text-blue-400"></i>
                <h3 class="qa-modal-title">Buat Channel atau Grup Baru</h3>
            </div>
            <button type="button" class="qa-modal-close" onclick="closeCreateChannelModal()">&times;</button>
        </div>

        <form id="createChannelForm" onsubmit="handleCreateChannelSubmit(event)">
            @csrf
            <div class="qa-modal-body">
                <div class="form-group">
                    <label class="form-label">Tipe Ruang Chat <span class="text-rose-500">*</span></label>
                    <div class="type-choice-grid">
                        <label class="type-choice-card">
                            <input type="radio" name="channel_type" value="public_channel" checked onchange="onChannelTypeChange(this.value)" />
                            <div class="choice-content">
                                <i class="fas fa-hashtag text-blue-500"></i>
                                <strong>Channel Publik</strong>
                                <span>Terbuka untuk semua anggota tim QA</span>
                            </div>
                        </label>
                        <label class="type-choice-card">
                            <input type="radio" name="channel_type" value="private_group" onchange="onChannelTypeChange(this.value)" />
                            <div class="choice-content">
                                <i class="fas fa-lock text-purple-500"></i>
                                <strong>Grup Privat</strong>
                                <span>Hanya anggota yang diundang</span>
                            </div>
                        </label>
                        <label class="type-choice-card">
                            <input type="radio" name="channel_type" value="broadcast" onchange="onChannelTypeChange(this.value)" />
                            <div class="choice-content">
                                <i class="fas fa-bullhorn text-amber-500"></i>
                                <strong>Siaran (1-Arah)</strong>
                                <span>Hanya admin yang dapat memposting</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Nama Channel / Grup <span class="text-rose-500">*</span></label>
                    <div class="input-prefix-wrap">
                        <span class="input-prefix" id="channelNamePrefix">#</span>
                        <input type="text" id="newChannelName" required class="qa-input with-prefix" placeholder="misal: release-v2, sprint-7, bug-triage" />
                    </div>
                    <span class="form-hint">Gunakan huruf kecil tanpa spasi (otomatis diformat menjadi slug).</span>
                </div>

                <div class="form-group">
                    <label class="form-label">Topik Singkat</label>
                    <input type="text" id="newChannelTopic" class="qa-input" placeholder="misal: Koordinasi pengujian rilis sprint 7" />
                </div>

                <div class="form-group">
                    <label class="form-label">Deskripsi (Opsional)</label>
                    <textarea id="newChannelDesc" rows="2" class="qa-input" placeholder="Tujuan dan panduan ruang chat ini..."></textarea>
                </div>

                <div class="form-group" id="memberSelectGroup" style="display: none;">
                    <label class="form-label">Undang Anggota</label>
                    <div class="members-checkbox-list">
                        @foreach($teamUsers as $tu)
                            <label class="member-check-item">
                                <input type="checkbox" name="invited_members[]" value="{{ $tu->id }}" />
                                <span>{{ $tu->name }} ({{ $tu->email }})</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="qa-modal-footer">
                <button type="button" class="qa-btn-secondary" onclick="closeCreateChannelModal()">Batal</button>
                <button type="submit" id="btnSubmitChannel" class="qa-btn-primary blue">
                    <i class="fas fa-check"></i> Buat Ruang Chat
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: START NEW DIRECT MESSAGE (DM) -->
<div id="newDmModal" class="qa-modal-overlay" style="display: none;" onclick="if(event.target === this) closeNewDmModal()">
    <div class="qa-modal-dialog qa-modal-sm">
        <div class="qa-modal-header">
            <div class="modal-title-group">
                <i class="fas fa-user-plus text-blue-400"></i>
                <h3 class="qa-modal-title">Mulai Pesan Langsung (DM)</h3>
            </div>
            <button type="button" class="qa-modal-close" onclick="closeNewDmModal()">&times;</button>
        </div>
        <div class="qa-modal-body">
            <p class="modal-subtitle-text">Pilih rekan tim untuk memulai obrolan langsung pribadi:</p>
            <div class="team-dm-select-list">
                @foreach($teamUsers as $tu)
                    <a href="{{ route('chat.dm', $tu->id) }}" class="team-dm-choice">
                        <div class="dm-avatar-mini">{{ strtoupper(substr($tu->name, 0, 2)) }}</div>
                        <div class="dm-choice-info">
                            <strong>{{ $tu->name }}</strong>
                            <span>{{ $tu->email }}</span>
                        </div>
                        <i class="fas fa-chevron-right text-slate-300"></i>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- MODAL: CHANNEL INFO & MEMBERS -->
<div id="channelInfoModal" class="qa-modal-overlay" style="display: none;" onclick="if(event.target === this) closeChannelInfoModal()">
    <div class="qa-modal-dialog qa-modal-md">
        <div class="qa-modal-header">
            <div class="modal-title-group">
                <i class="fas fa-info-circle text-blue-400"></i>
                <h3 class="qa-modal-title">Informasi Ruang &amp; Anggota</h3>
            </div>
            <button type="button" class="qa-modal-close" onclick="closeChannelInfoModal()">&times;</button>
        </div>
        <div class="qa-modal-body">
            @if($activeChannel)
                <div class="channel-info-card">
                    <h4>#{{ $activeChannel->name }}</h4>
                    <p class="topic-text">{{ $activeChannel->topic ?: 'Tidak ada topik spesifik.' }}</p>
                    <p class="desc-text">{{ $activeChannel->description ?: 'Tidak ada deskripsi rinci.' }}</p>
                </div>
                <div class="member-section-wrap">
                    <h5 class="member-section-heading">Anggota Terdaftar ({{ $activeChannel->members()->count() }})</h5>
                    <div class="channel-member-list">
                        @foreach($activeChannel->members()->with('user')->get() as $m)
                            <div class="ch-member-row">
                                <div class="member-avatar">{{ strtoupper(substr($m->user->name ?? 'User', 0, 2)) }}</div>
                                <div class="member-detail">
                                    <strong>{{ $m->user->name ?? 'User' }}</strong>
                                    <span>{{ $m->user->email ?? '' }}</span>
                                </div>
                                <span class="member-role-badge {{ $m->role }}">{{ ucfirst($m->role) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
    @include('pages.chat.partials.chat-styles')
@endpush

@push('scripts')
<script>
    const CURRENT_USER_ID = {{ $user->id }};
    const CURRENT_USER_NAME = "{{ addslashes($user->name) }}";
    const ACTIVE_CHANNEL_ID = {{ $activeChannel ? $activeChannel->id : 'null' }};
    const ACTIVE_CHANNEL_SLUG = "{{ $activeChannel ? $activeChannel->slug : '' }}";
    const CSRF_TOKEN = "{{ csrf_token() }}";
    const WS_PORT = {{ $wsPort }};
    const CHANNEL_MEMBERS = @json($channelMembers ?? []);
    const ALL_TEAM_USERS = @json($allUsers ?? []);
</script>
<script src="{{ asset('js/chat-client.js') }}"></script>
@endpush
