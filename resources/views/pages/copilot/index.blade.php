@extends('layouts.app')

@section('title', 'AI QA Copilot Studio — Chat & Live Draft (QA-TPL-001)')

@section('content')
<style>
    /* Full Studio Layout Styles */
    .copilot-container {
        display: flex;
        height: calc(100vh - 90px);
        background: #f8fafc;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,0.03);
    }

    /* Left Sidebar: Context & History */
    .copilot-sidebar {
        width: 290px;
        min-width: 290px;
        background: #ffffff;
        border-right: 1px solid #e2e8f0;
        display: flex;
        flex-direction: column;
    }
    .sidebar-section {
        padding: 14px 16px;
        border-bottom: 1px solid #f1f5f9;
    }
    .sidebar-label {
        font-size: 11px;
        font-weight: 800;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .chat-history-list {
        flex: 1;
        overflow-y: auto;
        padding: 10px;
    }
    .chat-session-item {
        padding: 10px 12px;
        border-radius: 10px;
        margin-bottom: 4px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.15s;
        border: 1px solid transparent;
    }
    .chat-session-item:hover {
        background: #f1f5f9;
    }
    .chat-session-item.active {
        background: #eff6ff;
        border-color: #bfdbfe;
    }
    .chat-session-title {
        font-size: 13px;
        font-weight: 700;
        color: #1e293b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 210px;
    }
    .chat-session-item.active .chat-session-title {
        color: #1d4ed8;
    }

    /* Center Pane: Chat Conversation */
    .copilot-chat-pane {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: #ffffff;
        border-right: 1px solid #e2e8f0;
        min-width: 420px;
    }
    .chat-header {
        padding: 14px 20px;
        border-bottom: 1px solid #e2e8f0;
        background: #ffffff;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 24px;
        display: flex;
        flex-direction: column;
        gap: 18px;
    }
    .chat-bubble {
        max-width: 82%;
        padding: 14px 18px;
        border-radius: 16px;
        font-size: 13.5px;
        line-height: 1.55;
    }
    .chat-bubble.user {
        align-self: flex-end;
        background: #2563eb;
        color: #ffffff;
        border-bottom-right-radius: 4px;
        box-shadow: 0 2px 8px rgba(37,99,235,0.2);
    }
    .chat-bubble.assistant {
        align-self: flex-start;
        background: #f8fafc;
        color: #0f172a;
        border: 1px solid #e2e8f0;
        border-bottom-left-radius: 4px;
    }
    .chat-input-area {
        padding: 14px 20px;
        border-top: 1px solid #e2e8f0;
        background: #ffffff;
    }
    .quick-prompts-bar {
        display: flex;
        gap: 8px;
        overflow-x: auto;
        padding-bottom: 10px;
        margin-bottom: 8px;
    }
    .quick-prompt-pill {
        white-space: nowrap;
        background: #f1f5f9;
        color: #334155;
        border: 1px solid #e2e8f0;
        padding: 5px 11px;
        border-radius: 20px;
        font-size: 11.5px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s;
    }
    .quick-prompt-pill:hover {
        background: #e2e8f0;
        border-color: #cbd5e1;
        color: #0f172a;
    }

    /* Right Pane: Live Draft Panel */
    .copilot-draft-pane {
        width: 500px;
        min-width: 480px;
        background: #fafafa;
        display: flex;
        flex-direction: column;
    }
    .draft-header {
        padding: 14px 20px;
        border-bottom: 1px solid #e2e8f0;
        background: #ffffff;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .draft-body {
        flex: 1;
        overflow-y: auto;
        padding: 16px;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .draft-card {
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        padding: 14px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.02);
        transition: all 0.15s;
        position: relative;
    }
    .draft-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 4px 12px rgba(0,0,0,0.04);
    }
    .draft-card.status-pass { border-left: 4px solid #10b981; }
    .draft-card.status-fail { border-left: 4px solid #ef4444; }
    .draft-card.status-blocked { border-left: 4px solid #f59e0b; }
    .draft-card.status-notrun { border-left: 4px solid #94a3b8; }
</style>

<div class="copilot-container">
    <!-- ======================================================== -->
    <!-- PANE 1: LEFT SIDEBAR (CONTEXT & CHAT HISTORY)            -->
    <!-- ======================================================== -->
    <div class="copilot-sidebar">
        <!-- Active Project Context -->
        <div class="sidebar-section">
            <div class="sidebar-label">
                <i class="fas fa-folder text-blue-500"></i> Project Aktif
            </div>
            <select id="activeProjectSelect" class="form-control" style="font-size: 12.5px; font-weight: 700; height: 38px; border-radius: 8px;" onchange="onProjectChanged(this.value)">
                @foreach($projects as $p)
                    <option value="{{ $p->id }}" {{ $activeProjectId == $p->id ? 'selected' : '' }}>
                        [{{ $p->project_code ?: 'PRJ' }}] {{ $p->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Active Report Context -->
        <div class="sidebar-section">
            <div class="sidebar-label">
                <i class="fas fa-file-alt text-indigo-500"></i> Laporan QA Terpilih
            </div>
            <select id="activeReportSelect" class="form-control" style="font-size: 12.5px; height: 38px; border-radius: 8px;" onchange="onReportChanged(this.value)">
                <option value="">+ (Buat Laporan QA Baru saat Simpan)</option>
                @foreach($reports as $r)
                    <option value="{{ $r->id }}" {{ $activeReportId == $r->id ? 'selected' : '' }}>
                        {{ $r->report_name }} ({{ ucfirst($r->test_type) }})
                    </option>
                @endforeach
            </select>
        </div>

        <!-- New Chat Button -->
        <div class="sidebar-section" style="padding: 12px 16px;">
            <button type="button" onclick="startNewChat()" class="btn-gradient-primary" style="width: 100%; padding: 9px 14px; font-size: 13px; font-weight: 700; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                <i class="fas fa-plus"></i> Percakapan Baru
            </button>
        </div>

        <!-- Chat History List -->
        <div style="padding: 10px 16px 4px;">
            <div class="sidebar-label">
                <i class="fas fa-history text-slate-400"></i> Riwayat Sesi Chat
            </div>
        </div>
        <div class="chat-history-list" id="chatHistoryList">
            @forelse($chats as $c)
                <div class="chat-session-item {{ ($activeChat && $activeChat->id == $c->id) ? 'active' : '' }}"
                     id="chat-item-{{ $c->id }}"
                     onclick="switchChat({{ $c->id }})">
                    <div style="display: flex; align-items: center; gap: 8px; overflow: hidden;">
                        <i class="far fa-comment-dots text-slate-400" style="font-size: 12px;"></i>
                        <div>
                            <div class="chat-session-title" title="{{ $c->title }}">{{ $c->title }}</div>
                            <div style="font-size: 10.5px; color: #94a3b8; margin-top: 1px;">
                                {{ $c->created_at ? $c->created_at->diffForHumans() : '-' }}
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="event.stopPropagation(); deleteChat({{ $c->id }})" title="Hapus chat" style="background: none; border: none; color: #cbd5e1; cursor: pointer; padding: 4px;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#cbd5e1'">
                        <i class="fas fa-trash-alt" style="font-size: 11px;"></i>
                    </button>
                </div>
            @empty
                <div style="text-align: center; color: #94a3b8; font-size: 12px; padding: 20px 10px;">
                    Belum ada riwayat sesi.
                </div>
            @endforelse
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- PANE 2: CENTER (CONVERSATION STREAM)                     -->
    <!-- ======================================================== -->
    <div class="copilot-chat-pane">
        <!-- Top Toolbar -->
        <div class="chat-header">
            <div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 16px;">🤖</span>
                    <h2 style="font-size: 15px; font-weight: 800; color: #0f172a; margin: 0;" id="chatTitleDisplay">
                        {{ $activeChat ? $activeChat->title : 'AI QA Copilot Studio' }}
                    </h2>
                </div>
                <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;">
                    <span style="color: #10b981; font-weight: 700;">● Auto-Fallback Aktif</span> &bull; OpenAI / Gemini / OpenRouter / OmniRoute
                </div>
            </div>

            <!-- Provider Selector -->
            <div style="display: flex; align-items: center; gap: 8px;">
                <label style="font-size: 11.5px; font-weight: 700; color: #64748b; margin: 0;">AI Model:</label>
                <select id="copilotProviderSelect" style="font-size: 12px; padding: 4px 8px; border-radius: 8px; border: 1px solid #cbd5e1; background: white; font-weight: 600;">
                    @php
                        $userDefAi = auth()->user()?->getResolvedDefaultAiProvider() ?: 'gemini';
                    @endphp
                    <option value="openai" {{ $userDefAi === 'openai' ? 'selected' : '' }}>OpenAI / Celeris (Custom Endpoint)</option>
                    <option value="gemini" {{ $userDefAi === 'gemini' ? 'selected' : '' }}>Google Gemini (Recommended)</option>
                    <option value="openrouter" {{ $userDefAi === 'openrouter' ? 'selected' : '' }}>OpenRouter Free Multi-Model</option>
                    <option value="omniroute" {{ $userDefAi === 'omniroute' ? 'selected' : '' }}>OmniRoute Server</option>
                    <option value="ollama" {{ $userDefAi === 'ollama' ? 'selected' : '' }}>Ollama (Offline Lokal)</option>
                </select>
            </div>
        </div>

        <!-- Messages Area -->
        <div class="chat-messages" id="chatMessagesContainer">
            @if(!$activeChat || $messages->isEmpty())
                <!-- Welcome Hero -->
                <div style="text-align: center; margin: auto 0; padding: 20px 30px;">
                    <div style="width: 56px; height: 56px; border-radius: 16px; background: linear-gradient(135deg, #2563eb, #7c3aed); color: white; display: inline-flex; align-items: center; justify-content: center; font-size: 26px; box-shadow: 0 4px 15px rgba(37,99,235,0.3); margin-bottom: 12px;">
                        <i class="fas fa-brain"></i>
                    </div>
                    <h3 style="font-size: 19px; font-weight: 800; color: #0f172a; margin: 0 0 6px;">
                        Halo! Saya Antigravity QA Copilot
                    </h3>
                    <p style="font-size: 13px; color: #64748b; max-width: 480px; margin: 0 auto 20px; line-height: 1.5;">
                        Saya siap membantu Anda menyusun test case, mem-parse log mentah, memperbarui status hasil uji, dan memvalidasi Quality Gate sesuai standar <strong>QA-TPL-001</strong>.
                    </p>
                    <div style="display: flex; flex-direction: column; gap: 8px; max-width: 420px; margin: 0 auto;">
                        <button type="button" class="quick-prompt-pill" style="text-align: left; padding: 8px 12px; border-radius: 10px;" onclick="sendQuickPrompt('Buatkan test case lengkap untuk fitur Checkout & Pembayaran QRIS (Positive, Negative, Edge cases)')">
                            🚀 <strong>Generate TC:</strong> "Buatkan test case fitur Checkout & Pembayaran QRIS..."
                        </button>
                        <button type="button" class="quick-prompt-pill" style="text-align: left; padding: 8px 12px; border-radius: 10px;" onclick="sendQuickPrompt('Tadi uji login: username admin password salah langsung keluar error merah (PASS), tapi pas coba sql injection input jebol (FAIL).')">
                            📋 <strong>Parse Log:</strong> "Tadi uji login: password salah PASS, tapi SQL injection FAIL..."
                        </button>
                        <button type="button" class="quick-prompt-pill" style="text-align: left; padding: 8px 12px; border-radius: 10px;" onclick="sendQuickPrompt('Bikinin laporan daily standup hari ini berdasarkan aktivitas testing QA yang sudah dikerjakan.')">
                            📝 <strong>Standup:</strong> "Bikinin standup hari ini berdasarkan hasil uji..."
                        </button>
                    </div>
                </div>
            @else
                @foreach($messages as $msg)
                    <div class="chat-bubble {{ $msg->role }}">
                        <div style="font-weight: 700; font-size: 11px; margin-bottom: 4px; opacity: 0.85;">
                            {{ $msg->role === 'user' ? 'Anda' : 'Antigravity QA Copilot' }}
                            @if($msg->provider_used)
                                <span style="font-size: 10px; font-weight: normal; margin-left: 4px;">via {{ $msg->provider_used }}</span>
                            @endif
                        </div>
                        <div style="white-space: pre-wrap;">{{ $msg->content }}</div>

                        @if(!empty($msg->draft_payload['test_cases']))
                            @php $tcCount = count($msg->draft_payload['test_cases']); @endphp
                            <div style="margin-top: 10px; padding: 8px 12px; background: rgba(0,0,0,0.04); border-radius: 8px; border-left: 3px solid #2563eb; font-size: 12px; display: flex; justify-content: space-between; align-items: center;">
                                <span>✨ <strong>{{ $tcCount }} Test Case Draft</strong> siap di panel kanan</span>
                                <button type="button" onclick="loadDraftPayload({{ json_encode($msg->draft_payload['test_cases']) }})" style="background: #2563eb; color: white; border: none; padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; cursor: pointer;">
                                    Tampilkan di Panel
                                </button>
                            </div>
                        @endif
                    </div>
                @endforeach
            @endif
        </div>

        <!-- Input Area -->
        <div class="chat-input-area">
            <!-- Quick prompt suggestions -->
            <div class="quick-prompts-bar">
                <span class="quick-prompt-pill" onclick="sendQuickPrompt('Buatkan test case Positive, Negative, dan Edge cases untuk fitur Reset Password')">
                    💡 Reset Password
                </span>
                <span class="quick-prompt-pill" onclick="sendQuickPrompt('TC-001 fail, tombol simpan tidak merespon saat diklik')">
                    ⚡ Update: TC-001 Fail
                </span>
                <span class="quick-prompt-pill" onclick="sendQuickPrompt('Apakah report aktif ini sudah layak status GO untuk rilis?')">
                    🔍 Evaluasi Quality Gate
                </span>
                <span class="quick-prompt-pill" onclick="sendQuickPrompt('Bikinin standup hari ini')">
                    📝 Standup Hari Ini
                </span>
            </div>

            <!-- Form input -->
            <div style="display: flex; gap: 8px; align-items: flex-end;">
                <textarea id="copilotInput"
                          rows="2"
                          placeholder="Ketik instruksi, requirement fitur, atau paste catatan testing mentah di sini... (Enter untuk kirim, Shift+Enter untuk baris baru)"
                          style="flex: 1; resize: none; border: 1px solid #cbd5e1; border-radius: 12px; padding: 10px 14px; font-size: 13.5px; outline: none; font-family: inherit; line-height: 1.4;"
                          onkeydown="handleInputKeydown(event)"></textarea>

                <button type="button" id="sendBtn" onclick="sendMessage()" class="btn-gradient-primary" style="height: 44px; width: 44px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0;">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- PANE 3: RIGHT (LIVE DRAFT TEST CASES PANEL)              -->
    <!-- ======================================================== -->
    <div class="copilot-draft-pane">
        <!-- Draft Header -->
        <div class="draft-header">
            <div>
                <div style="display: flex; align-items: center; gap: 6px;">
                    <h3 style="font-size: 15px; font-weight: 800; color: #0f172a; margin: 0;">
                        Draft Test Cases
                    </h3>
                    <span id="draftCountBadge" style="background: #2563eb; color: white; font-size: 11px; font-weight: 800; padding: 2px 7px; border-radius: 20px;">
                        0
                    </span>
                </div>
                <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;">
                    Edit langsung & centang untuk disimpan ke database
                </div>
            </div>

            <!-- Action Buttons -->
            <div style="display: flex; gap: 6px;">
                <button type="button" onclick="addNewManualDraft()" title="Tambah baris manual" style="background: white; border: 1px solid #cbd5e1; color: #334155; padding: 6px 10px; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer;">
                    <i class="fas fa-plus"></i>
                </button>
                <button type="button" onclick="clearSelectedDrafts()" title="Hapus yang dicentang" style="background: white; border: 1px solid #cbd5e1; color: #ef4444; padding: 6px 10px; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer;">
                    <i class="fas fa-trash"></i>
                </button>
                <button type="button" onclick="openSaveModal()" style="background: #10b981; color: white; border: none; padding: 6px 14px; border-radius: 8px; font-size: 12.5px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 6px rgba(16,185,129,0.3);">
                    <i class="fas fa-save"></i> Simpan ke Report
                </button>
            </div>
        </div>

        <!-- Bulk Filter Toolbar -->
        <div style="padding: 8px 16px; background: #ffffff; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; font-size: 12px;">
            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-weight: 700; color: #475569; margin: 0;">
                <input type="checkbox" id="selectAllCheckbox" checked onchange="toggleSelectAll(this.checked)">
                Pilih Semua
            </label>
            <span style="color: #64748b; font-size: 11.5px;" id="selectedCountText">0 terpilih</span>
        </div>

        <!-- Draft Cards Body -->
        <div class="draft-body" id="draftContainer">
            <!-- Empty state -->
            <div id="draftEmptyState" style="text-align: center; margin: auto 0; padding: 30px; color: #94a3b8;">
                <div style="font-size: 32px; margin-bottom: 8px; opacity: 0.6;">📋</div>
                <div style="font-weight: 700; font-size: 14px; color: #64748b;">Belum Ada Draft Test Case</div>
                <p style="font-size: 12px; margin-top: 4px; line-height: 1.4;">
                    Minta AI Copilot untuk mengenerate test case dari requirement atau mem-parse log di panel percakapan.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL: SIMPAN DRAFT KE REPORT DATABASE                   -->
<!-- ======================================================== -->
<div id="saveDraftModal" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.6); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(2px);">
    <div style="background: white; border-radius: 16px; width: 100%; max-width: 520px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.2);">
        <div style="background: #0f172a; padding: 16px 20px; color: white; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-database text-emerald-400"></i>
                <h3 style="font-size: 15px; font-weight: 800; margin: 0;">Simpan Test Case ke Database</h3>
            </div>
            <button type="button" onclick="closeSaveModal()" style="background: none; border: none; color: #94a3b8; font-size: 16px; cursor: pointer;">&times;</button>
        </div>

        <div style="padding: 20px;">
            <p style="font-size: 13px; color: #64748b; margin-top: 0;">
                Menyimpan <strong id="modalSaveCount" class="text-blue-600">0</strong> test case terpilih ke dalam database QA Portal.
            </p>

            <div style="margin-bottom: 14px;">
                <label style="font-size: 12px; font-weight: 700; color: #334155;">Pilihan Penyimpanan:</label>
                <div style="display: flex; gap: 12px; margin-top: 6px;">
                    <label style="font-size: 12.5px; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                        <input type="radio" name="saveModeRadio" value="existing" checked onchange="toggleSaveMode(this.value)">
                        Tambahkan ke Laporan Aktif
                    </label>
                    <label style="font-size: 12.5px; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                        <input type="radio" name="saveModeRadio" value="new" onchange="toggleSaveMode(this.value)">
                        Buat Laporan Baru
                    </label>
                </div>
            </div>

            <!-- Existing Report Section -->
            <div id="saveModeExistingSection" style="margin-bottom: 14px;">
                <label style="font-size: 12px; font-weight: 700; color: #334155;">Target Laporan QA:</label>
                <select id="modalReportTargetSelect" class="form-control" style="font-size: 12.5px; height: 38px; border-radius: 8px;">
                    @foreach($reports as $r)
                        <option value="{{ $r->id }}">{{ $r->report_name }} ({{ ucfirst($r->test_type) }})</option>
                    @endforeach
                </select>
            </div>

            <!-- New Report Section -->
            <div id="saveModeNewSection" style="display: none; background: #f8fafc; padding: 12px; border-radius: 10px; border: 1px solid #e2e8f0; margin-bottom: 14px;">
                <div style="margin-bottom: 10px;">
                    <label style="font-size: 11.5px; font-weight: 700; color: #334155;">Nama Laporan Baru:</label>
                    <input type="text" id="modalNewReportName" class="form-control" placeholder="Contoh: Sprint 26 - Feature Checkout Testing" style="font-size: 12.5px; border-radius: 6px;">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 10px;">
                    <div>
                        <label style="font-size: 11px; font-weight: 700; color: #334155;">Tipe Testing:</label>
                        <select id="modalNewTestType" class="form-control" style="font-size: 12px; height: 34px; border-radius: 6px;">
                            <option value="functional">Functional Test</option>
                            <option value="hardware">Hardware Test</option>
                            <option value="integration">Integration Test</option>
                            <option value="regression">Regression Test</option>
                            <option value="performance">Performance Test</option>
                            <option value="reliability">Reliability Test</option>
                            <option value="stress">Stress Test</option>
                            <option value="negative">Negative Test</option>
                            <option value="validation">Validation Test</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size: 11px; font-weight: 700; color: #334155;">Siklus:</label>
                        <input type="text" id="modalNewCycle" class="form-control" value="Siklus 1" style="font-size: 12px; height: 34px; border-radius: 6px;">
                    </div>
                </div>
                <div>
                    <label style="font-size: 11px; font-weight: 700; color: #334155;">Sprint:</label>
                    <input type="text" id="modalNewSprint" class="form-control" placeholder="Sprint 26" style="font-size: 12px; height: 34px; border-radius: 6px;">
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 18px;">
                <button type="button" onclick="closeSaveModal()" style="background: #f1f5f9; border: 1px solid #cbd5e1; color: #475569; padding: 8px 14px; border-radius: 8px; font-size: 12.5px; font-weight: 600; cursor: pointer;">
                    Batal
                </button>
                <button type="button" id="confirmSaveBtn" onclick="confirmSaveDraft()" class="btn-gradient-primary" style="padding: 8px 18px; border-radius: 8px; font-size: 12.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-check"></i> Konfirmasi & Simpan
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Include Shared Modals for Inline Rule Triggers -->
@include('pages.bugs.partials.quick-bug-modal')
@include('pages.report.partials.hw-library-modal')

<script>
    // Global State
    let currentChatId = {{ $activeChat ? $activeChat->id : 'null' }};
    let activeProjectId = {{ $activeProjectId ?: 'null' }};
    let activeReportId = {{ $activeReportId ?: 'null' }};
    let draftTestCases = []; // Live draft list
    let targetDraftIndexForModal = null;

    document.addEventListener('DOMContentLoaded', () => {
        // Load initial draft from last assistant message if exists
        @if(isset($activeChat) && $activeChat)
            @php
                $initialDraft = null;
                foreach ($messages->reverse() as $m) {
                    if (!empty($m->draft_payload['test_cases'])) {
                        $initialDraft = $m->draft_payload['test_cases'];
                        break;
                    }
                }
            @endphp
            @if(!empty($initialDraft))
                loadDraftPayload({!! json_encode($initialDraft) !!});
            @endif
        @endif
        scrollChatToBottom();
    });

    function scrollChatToBottom() {
        const container = document.getElementById('chatMessagesContainer');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    }

    function handleInputKeydown(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    }

    function sendQuickPrompt(promptText) {
        document.getElementById('copilotInput').value = promptText;
        sendMessage();
    }

    // ────────────────────────────────────────────────────────
    //  Chat & Message Operations
    // ────────────────────────────────────────────────────────

    async function sendMessage() {
        const input = document.getElementById('copilotInput');
        const text = input.value.trim();
        if (!text) return;

        const sendBtn = document.getElementById('sendBtn');
        sendBtn.disabled = true;
        sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        input.value = '';

        // Append user bubble to chat
        appendChatBubble('user', text);

        // Append loading placeholder for assistant
        const assistantPlaceholder = appendChatBubble('assistant', 'Sedang memproses instruksi dan menyusun data...');

        try {
            const provider = document.getElementById('copilotProviderSelect').value;

            const res = await fetch("{{ route('copilot.message') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    message: text,
                    chat_id: currentChatId,
                    project_id: activeProjectId,
                    report_id: activeReportId,
                    current_drafts: draftTestCases,
                    provider: provider
                })
            });

            const data = await res.json();

            if (!data.success) {
                assistantPlaceholder.innerHTML = `<span style="color: #ef4444;"><i class="fas fa-exclamation-triangle"></i> Gagal: ${data.message || 'Terjadi kesalahan sistem'}</span>`;
                return;
            }

            // Update chat session ID and title
            currentChatId = data.chat_id;
            document.getElementById('chatTitleDisplay').innerText = data.chat_title || 'AI QA Copilot Studio';

            // Replace assistant placeholder with formatted content
            assistantPlaceholder.innerHTML = `
                <div style="font-weight: 700; font-size: 11px; margin-bottom: 4px; opacity: 0.85;">
                    Antigravity QA Copilot <span style="font-size: 10px; font-weight: normal; margin-left: 4px;">via ${data.provider_used}</span>
                </div>
                <div style="white-space: pre-wrap;">${escapeHtml(data.response_text)}</div>
            `;

            // If draft test cases were generated / updated, push to right panel
            if (data.payload && data.payload.test_cases && data.payload.test_cases.length > 0) {
                loadDraftPayload(data.payload.test_cases);
                
                const cardBadge = document.createElement('div');
                cardBadge.style.cssText = 'margin-top: 10px; padding: 8px 12px; background: rgba(0,0,0,0.04); border-radius: 8px; border-left: 3px solid #2563eb; font-size: 12px; display: flex; justify-content: space-between; align-items: center;';
                cardBadge.innerHTML = `
                    <span>✨ <strong>${data.payload.test_cases.length} Test Case</strong> telah dimuat ke panel Draft kanan</span>
                    <button type="button" onclick="highlightDraftPanel()" style="background: #2563eb; color: white; border: none; padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; cursor: pointer;">
                        Lihat Panel &rarr;
                    </button>
                `;
                assistantPlaceholder.appendChild(cardBadge);
            }

            // Refresh chat list title in sidebar if new
            updateSidebarItem(data.chat_id, data.chat_title);

        } catch (err) {
            assistantPlaceholder.innerHTML = `<span style="color: #ef4444;"><i class="fas fa-exclamation-triangle"></i> Koneksi bermasalah: ${err.message}</span>`;
        } finally {
            sendBtn.disabled = false;
            sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
            scrollChatToBottom();
        }
    }

    function appendChatBubble(role, content) {
        const container = document.getElementById('chatMessagesContainer');
        const emptyHero = container.querySelector('div[style*="text-align: center; margin: auto 0;"]');
        if (emptyHero) emptyHero.remove();

        const bubble = document.createElement('div');
        bubble.className = `chat-bubble ${role}`;
        bubble.innerHTML = `
            <div style="font-weight: 700; font-size: 11px; margin-bottom: 4px; opacity: 0.85;">
                ${role === 'user' ? 'Anda' : 'Antigravity QA Copilot'}
            </div>
            <div style="white-space: pre-wrap;">${escapeHtml(content)}</div>
        `;
        container.appendChild(bubble);
        scrollChatToBottom();
        return bubble;
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function startNewChat() {
        currentChatId = null;
        document.getElementById('chatMessagesContainer').innerHTML = `
            <div style="text-align: center; margin: auto 0; padding: 20px 30px;">
                <div style="width: 56px; height: 56px; border-radius: 16px; background: linear-gradient(135deg, #2563eb, #7c3aed); color: white; display: inline-flex; align-items: center; justify-content: center; font-size: 26px; box-shadow: 0 4px 15px rgba(37,99,235,0.3); margin-bottom: 12px;">
                    <i class="fas fa-brain"></i>
                </div>
                <h3 style="font-size: 19px; font-weight: 800; color: #0f172a; margin: 0 0 6px;">Sesi Baru AI QA Copilot</h3>
                <p style="font-size: 13px; color: #64748b; max-width: 450px; margin: 0 auto;">Ketik requirement atau paste catatan testing untuk mulai bekerja.</p>
            </div>
        `;
        document.getElementById('chatTitleDisplay').innerText = 'Percakapan Baru';
        document.querySelectorAll('.chat-session-item').forEach(el => el.classList.remove('active'));
    }

    async function switchChat(chatId) {
        currentChatId = chatId;
        document.querySelectorAll('.chat-session-item').forEach(el => el.classList.remove('active'));
        const item = document.getElementById(`chat-item-${chatId}`);
        if (item) item.classList.add('active');

        const container = document.getElementById('chatMessagesContainer');
        container.innerHTML = '<div style="text-align: center; padding: 30px; color: #64748b;"><i class="fas fa-spinner fa-spin"></i> Memuat percakapan...</div>';

        try {
            const res = await fetch(`/copilot/chats/${chatId}`);
            const data = await res.json();
            if (data.success) {
                document.getElementById('chatTitleDisplay').innerText = data.chat.title;
                container.innerHTML = '';
                data.messages.forEach(m => {
                    const bubble = document.createElement('div');
                    bubble.className = `chat-bubble ${m.role}`;
                    bubble.innerHTML = `
                        <div style="font-weight: 700; font-size: 11px; margin-bottom: 4px; opacity: 0.85;">
                            ${m.role === 'user' ? 'Anda' : 'Antigravity QA Copilot'}
                            ${m.provider_used ? `<span style="font-size: 10px; font-weight: normal; margin-left: 4px;">via ${m.provider_used}</span>` : ''}
                        </div>
                        <div style="white-space: pre-wrap;">${escapeHtml(m.content)}</div>
                    `;
                    container.appendChild(bubble);
                });

                if (data.last_draft && data.last_draft.test_cases) {
                    loadDraftPayload(data.last_draft.test_cases);
                }
                scrollChatToBottom();
            }
        } catch (e) {
            container.innerHTML = `<div style="color: #ef4444; padding: 20px;">Gagal memuat chat: ${e.message}</div>`;
        }
    }

    async function deleteChat(chatId) {
        if (!confirm('Hapus sesi percakapan ini?')) return;
        try {
            const res = await fetch(`/copilot/chats/${chatId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });
            const data = await res.json();
            if (data.success) {
                const el = document.getElementById(`chat-item-${chatId}`);
                if (el) el.remove();
                if (currentChatId == chatId) startNewChat();
            }
        } catch (e) {
            alert('Gagal menghapus: ' + e.message);
        }
    }

    function updateSidebarItem(chatId, title) {
        let item = document.getElementById(`chat-item-${chatId}`);
        if (!item) {
            const list = document.getElementById('chatHistoryList');
            item = document.createElement('div');
            item.className = 'chat-session-item active';
            item.id = `chat-item-${chatId}`;
            item.setAttribute('onclick', `switchChat(${chatId})`);
            item.innerHTML = `
                <div style="display: flex; align-items: center; gap: 8px; overflow: hidden;">
                    <i class="far fa-comment-dots text-slate-400" style="font-size: 12px;"></i>
                    <div>
                        <div class="chat-session-title" title="${escapeHtml(title)}">${escapeHtml(title)}</div>
                        <div style="font-size: 10.5px; color: #94a3b8; margin-top: 1px;">Baru saja</div>
                    </div>
                </div>
                <button type="button" onclick="event.stopPropagation(); deleteChat(${chatId})" title="Hapus chat" style="background: none; border: none; color: #cbd5e1; cursor: pointer; padding: 4px;">
                    <i class="fas fa-trash-alt" style="font-size: 11px;"></i>
                </button>
            `;
            list.insertBefore(item, list.firstChild);
        } else {
            const titleEl = item.querySelector('.chat-session-title');
            if (titleEl) titleEl.innerText = title;
        }
    }

    async function onProjectChanged(projectId) {
        activeProjectId = projectId;
        const repSelect = document.getElementById('activeReportSelect');
        repSelect.innerHTML = '<option value="">Memuat laporan...</option>';

        try {
            const res = await fetch(`/copilot/reports/${projectId}`);
            const data = await res.json();
            repSelect.innerHTML = '<option value="">+ (Buat Laporan QA Baru saat Simpan)</option>';
            if (data.reports) {
                data.reports.forEach(r => {
                    const opt = document.createElement('option');
                    opt.value = r.id;
                    opt.innerText = `${r.report_name} (${r.test_type})`;
                    repSelect.appendChild(opt);
                });
            }
        } catch (e) {
            repSelect.innerHTML = '<option value="">+ (Buat Laporan QA Baru saat Simpan)</option>';
        }
    }

    function onReportChanged(reportId) {
        activeReportId = reportId ? parseInt(reportId) : null;
    }

    // ────────────────────────────────────────────────────────
    //  Live Draft Panel Operations
    // ────────────────────────────────────────────────────────

    function loadDraftPayload(newTestCases) {
        if (!Array.isArray(newTestCases)) return;
        draftTestCases = newTestCases;
        renderDraftList();
    }

    function renderDraftList() {
        const container = document.getElementById('draftContainer');
        const emptyState = document.getElementById('draftEmptyState');
        const badge = document.getElementById('draftCountBadge');

        badge.innerText = draftTestCases.length;

        if (draftTestCases.length === 0) {
            container.innerHTML = '';
            if (emptyState) container.appendChild(emptyState);
            updateSelectedCount();
            return;
        }

        container.innerHTML = '';

        draftTestCases.forEach((tc, idx) => {
            const stClass = 'status-' + (tc.status || 'notrun').toLowerCase().replace(/[\s\/_]/g, '');

            // Rule 3 Check: Fail without Bug ID
            const isFail = (tc.status === 'Fail');
            const hasBug = (tc.bugTicket && tc.bugTicket.trim().length > 0);
            const rule3Warning = isFail && !hasBug;

            // Rule 7 Check: HW Pass without Spec
            const isPass = (tc.status === 'Pass');
            const spec = (tc.passLimitSpec || '').trim().toUpperCase();
            const rule7Warning = isPass && (spec === '' || spec === 'TBD');

            const card = document.createElement('div');
            card.className = `draft-card ${stClass}`;
            card.id = `draft-card-${idx}`;

            card.innerHTML = `
                <!-- Card Header -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" ${tc.selected !== false ? 'checked' : ''} onchange="toggleDraftSelect(${idx}, this.checked)">
                        <input type="text" value="${escapeHtml(tc.testCaseId || 'TC-' + (idx+1))}"
                               onchange="updateDraftField(${idx}, 'testCaseId', this.value)"
                               style="font-family: monospace; font-weight: 800; font-size: 12px; width: 110px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 2px 6px; color: #2563eb;">
                        <select onchange="updateDraftField(${idx}, 'caseType', this.value)" style="font-size: 11px; font-weight: 700; border-radius: 6px; border: 1px solid #cbd5e1; padding: 2px 4px;">
                            <option value="Positive" ${tc.caseType === 'Positive' ? 'selected' : ''}>Positive</option>
                            <option value="Negative" ${tc.caseType === 'Negative' ? 'selected' : ''}>Negative</option>
                            <option value="Edge" ${tc.caseType === 'Edge' ? 'selected' : ''}>Edge</option>
                        </select>
                        <select onchange="updateDraftField(${idx}, 'priority', this.value)" style="font-size: 11px; font-weight: 700; border-radius: 6px; border: 1px solid #cbd5e1; padding: 2px 4px;">
                            <option value="High" ${tc.priority === 'High' ? 'selected' : ''}>High</option>
                            <option value="Medium" ${tc.priority === 'Medium' ? 'selected' : ''}>Medium</option>
                            <option value="Low" ${tc.priority === 'Low' ? 'selected' : ''}>Low</option>
                            <option value="Safety-Critical (HW)" ${tc.priority === 'Safety-Critical (HW)' ? 'selected' : ''}>Safety-Critical</option>
                        </select>
                    </div>
                    <button type="button" onclick="deleteDraftItem(${idx})" title="Hapus draft ini" style="background: none; border: none; color: #94a3b8; cursor: pointer; padding: 2px 4px;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#94a3b8'">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Test Title -->
                <div style="margin-bottom: 8px;">
                    <input type="text" value="${escapeHtml(tc.testTitle || '')}"
                           placeholder="Judul / Skenario Pengujian..."
                           onchange="updateDraftField(${idx}, 'testTitle', this.value)"
                           style="width: 100%; font-size: 13px; font-weight: 700; color: #0f172a; border: 1px solid #e2e8f0; border-radius: 6px; padding: 5px 8px;">
                </div>

                <!-- Module & Feature / Subsystem -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-bottom: 8px;">
                    <input type="text" value="${escapeHtml(tc.moduleFeature || tc.componentSubsystem || '')}"
                           placeholder="Modul / Subsistem..."
                           onchange="updateDraftField(${idx}, 'moduleFeature', this.value)"
                           style="font-size: 11.5px; border: 1px solid #e2e8f0; border-radius: 6px; padding: 4px 6px;">
                    <input type="text" value="${escapeHtml(tc.feature || tc.parameterMeasured || '')}"
                           placeholder="Sub-Fitur / Parameter..."
                           onchange="updateDraftField(${idx}, 'feature', this.value)"
                           style="font-size: 11.5px; border: 1px solid #e2e8f0; border-radius: 6px; padding: 4px 6px;">
                </div>

                <!-- Status Selector Badges -->
                <div style="display: flex; gap: 4px; align-items: center; margin-bottom: 8px; flex-wrap: wrap;">
                    <span style="font-size: 11px; font-weight: 700; color: #64748b;">Status:</span>
                    ${['Not Run', 'Pass', 'Fail', 'Blocked', 'N/A'].map(st => `
                        <button type="button" onclick="updateDraftField(${idx}, 'status', '${st}')"
                                style="font-size: 10.5px; font-weight: 800; padding: 2px 7px; border-radius: 5px; cursor: pointer; border: 1px solid #cbd5e1;
                                background: ${tc.status === st ? (st === 'Pass' ? '#dcfce7' : (st === 'Fail' ? '#fee2e2' : (st === 'Blocked' ? '#fef3c7' : '#0f172a'))) : '#ffffff'};
                                color: ${tc.status === st ? (st === 'Pass' ? '#15803d' : (st === 'Fail' ? '#b91c1c' : (st === 'Blocked' ? '#b45309' : '#ffffff'))) : '#475569'};">
                            ${st}
                        </button>
                    `).join('')}
                </div>

                <!-- Inline Warning: Rule 3 (Fail without Bug ID) -->
                ${rule3Warning ? `
                    <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; padding: 6px 10px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center; font-size: 11px;">
                        <span style="color: #b91c1c; font-weight: 700;">
                            <i class="fas fa-exclamation-circle text-red-500 mr-1"></i> Aturan 3: Fail wajib tercatat di Bug List
                        </span>
                        <button type="button" onclick="triggerQuickBugForDraft(${idx})" style="background: #ef4444; color: white; border: none; padding: 2px 8px; border-radius: 4px; font-weight: 700; cursor: pointer; font-size: 10.5px;">
                            + Buat Bug
                        </button>
                    </div>
                ` : ''}

                <!-- Inline Warning: Rule 7 (HW Pass without Spec) -->
                ${rule7Warning ? `
                    <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 6px; padding: 6px 10px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center; font-size: 11px;">
                        <span style="color: #b45309; font-weight: 700;">
                            <i class="fas fa-exclamation-triangle text-amber-500 mr-1"></i> Aturan 7: Pass wajib ada Batas Lulus / Spec valid
                        </span>
                        <button type="button" onclick="triggerHwLibraryForDraft(${idx})" style="background: #f59e0b; color: white; border: none; padding: 2px 8px; border-radius: 4px; font-weight: 700; cursor: pointer; font-size: 10.5px;">
                            Library HW
                        </button>
                    </div>
                ` : ''}

                <!-- Actual Result & Bug Ticket Row -->
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 6px;">
                    <textarea rows="1" placeholder="Hasil aktual..."
                              onchange="updateDraftField(${idx}, 'actualResult', this.value)"
                              style="font-size: 11.5px; border: 1px solid #e2e8f0; border-radius: 6px; padding: 4px 6px; resize: vertical;">${escapeHtml(tc.actualResult || '')}</textarea>
                    <input type="text" value="${escapeHtml(tc.bugTicket || '')}"
                           placeholder="Bug Ticket..."
                           onchange="updateDraftField(${idx}, 'bugTicket', this.value)"
                           style="font-family: monospace; font-size: 11.5px; border: 1px solid #e2e8f0; border-radius: 6px; padding: 4px 6px;">
                </div>
            `;

            container.appendChild(card);
        });

        updateSelectedCount();
    }

    function updateDraftField(index, field, value) {
        if (draftTestCases[index]) {
            draftTestCases[index][field] = value;
            renderDraftList();
        }
    }

    function toggleDraftSelect(index, isChecked) {
        if (draftTestCases[index]) {
            draftTestCases[index].selected = isChecked;
            updateSelectedCount();
        }
    }

    function toggleSelectAll(isChecked) {
        draftTestCases.forEach(tc => tc.selected = isChecked);
        renderDraftList();
    }

    function updateSelectedCount() {
        const count = draftTestCases.filter(tc => tc.selected !== false).length;
        document.getElementById('selectedCountText').innerText = `${count} terpilih`;
        document.getElementById('modalSaveCount').innerText = count;
    }

    function deleteDraftItem(index) {
        draftTestCases.splice(index, 1);
        renderDraftList();
    }

    function clearSelectedDrafts() {
        draftTestCases = draftTestCases.filter(tc => tc.selected === false);
        renderDraftList();
    }

    function addNewManualDraft() {
        draftTestCases.push({
            selected: true,
            testCaseId: 'TC-' + String(draftTestCases.length + 1).padStart(3, '0'),
            testTitle: 'Skenario Baru',
            caseType: 'Positive',
            priority: 'Medium',
            moduleFeature: 'General',
            status: 'Not Run',
            steps: [{ step: 'Lakukan langkah uji', expected: 'Hasil sesuai spesifikasi' }],
            actualResult: '',
            bugTicket: '',
            evidenceUrl: ''
        });
        renderDraftList();
    }

    function highlightDraftPanel() {
        const panel = document.querySelector('.copilot-draft-pane');
        panel.style.boxShadow = 'inset 0 0 0 2px #2563eb';
        setTimeout(() => panel.style.boxShadow = '', 800);
    }

    // ────────────────────────────────────────────────────────
    //  Inline Rule Triggers: Quick Bug & HW Library
    // ────────────────────────────────────────────────────────

    function triggerQuickBugForDraft(index) {
        targetDraftIndexForModal = index;
        const tc = draftTestCases[index];
        openQuickBugModal(null, {
            projectId: activeProjectId,
            reportId: activeReportId,
            testCaseId: tc.testCaseId,
            title: 'Defect: ' + tc.testTitle
        });
    }

    // Overwrite / hook quick bug saved callback to auto-populate bug ID back to draft
    window.onQuickBugSaved = function(bugId) {
        if (targetDraftIndexForModal !== null && draftTestCases[targetDraftIndexForModal]) {
            draftTestCases[targetDraftIndexForModal].bugTicket = bugId;
            renderDraftList();
            targetDraftIndexForModal = null;
        }
    };

    function triggerHwLibraryForDraft(index) {
        targetDraftIndexForModal = index;
        openHwLibraryModal(index);
    }

    // Overwrite / hook HW library apply callback
    window.onHwLibraryApplied = function(rowIndex, paramData) {
        if (targetDraftIndexForModal !== null && draftTestCases[targetDraftIndexForModal]) {
            draftTestCases[targetDraftIndexForModal].componentSubsystem = paramData.subsystem || paramData.category;
            draftTestCases[targetDraftIndexForModal].parameterMeasured  = paramData.param;
            draftTestCases[targetDraftIndexForModal].unit               = paramData.unit;
            draftTestCases[targetDraftIndexForModal].passLimitSpec      = paramData.limit;
            draftTestCases[targetDraftIndexForModal].specSource         = paramData.specSource || 'Datasheet';
            renderDraftList();
            targetDraftIndexForModal = null;
        }
    };

    // ────────────────────────────────────────────────────────
    //  Save Draft to Database Operations
    // ────────────────────────────────────────────────────────

    function openSaveModal() {
        const selected = draftTestCases.filter(tc => tc.selected !== false);
        if (selected.length === 0) {
            alert('Pilih minimal 1 draft test case yang ingin disimpan.');
            return;
        }

        document.getElementById('modalSaveCount').innerText = selected.length;
        document.getElementById('saveDraftModal').style.display = 'flex';
    }

    function closeSaveModal() {
        document.getElementById('saveDraftModal').style.display = 'none';
    }

    function toggleSaveMode(mode) {
        if (mode === 'new') {
            document.getElementById('saveModeExistingSection').style.display = 'none';
            document.getElementById('saveModeNewSection').style.display = 'block';
        } else {
            document.getElementById('saveModeExistingSection').style.display = 'block';
            document.getElementById('saveModeNewSection').style.display = 'none';
        }
    }

    async function confirmSaveDraft() {
        const selected = draftTestCases.filter(tc => tc.selected !== false);
        if (selected.length === 0) return;

        const saveMode = document.querySelector('input[name="saveModeRadio"]:checked').value;
        const confirmBtn = document.getElementById('confirmSaveBtn');
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

        const payload = {
            save_mode: saveMode,
            project_id: activeProjectId,
            report_id: saveMode === 'existing' ? document.getElementById('modalReportTargetSelect').value : null,
            report_name: saveMode === 'new' ? document.getElementById('modalNewReportName').value : null,
            test_type: saveMode === 'new' ? document.getElementById('modalNewTestType').value : null,
            sprint: saveMode === 'new' ? document.getElementById('modalNewSprint').value : null,
            cycle: saveMode === 'new' ? document.getElementById('modalNewCycle').value : null,
            test_cases: selected
        };

        try {
            const res = await fetch("{{ route('copilot.save-draft') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json();

            if (data.success) {
                alert(data.message);
                closeSaveModal();
                // Clear saved items from draft
                draftTestCases = draftTestCases.filter(tc => tc.selected === false);
                renderDraftList();

                // If user wants to open report detail
                if (confirm('Buka halaman Detail & Quality Gate laporan sekarang?')) {
                    window.location.href = data.report_url;
                }
            } else {
                alert('Gagal menyimpan: ' + (data.message || 'Error'));
            }
        } catch (e) {
            alert('Terjadi kesalahan: ' + e.message);
        } finally {
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<i class="fas fa-check"></i> Konfirmasi & Simpan';
        }
    }
</script>
@endsection
