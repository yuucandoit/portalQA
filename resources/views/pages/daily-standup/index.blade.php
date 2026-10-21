@extends('layouts.app')

@section('title', config('app.name', 'QA Portal') . ' - AI Assistant Daily Standup')

@section('page_title', 'AI Assistant: Daily Standup & Activity')
@section('page_subtitle', 'Rangkum pekerjaan harian otomatis dari QA Reports atau input bebas ke format laporan WhatsApp / Slack')

@section('content')
<div class="standup-wrapper">

    <!-- ── Top Header Banner ── -->
    <div class="standup-hero mb-6">
        <div class="hero-left">
            <div class="hero-icon-box">
                <i class="fas fa-robot"></i>
            </div>
            <div>
                <div class="hero-tag">
                    <span class="pulse-indicator"></span>
                    <span>AI Productivity Assistant</span>
                </div>
                <h2 class="hero-title">Daily Standup & Activity Generator</h2>
                <p class="hero-subtitle">
                    Otomatis rangkum pekerjaan pengetesan QA di portal atau tulis catatan manual bebas, lalu dapatkan format standup rapi yang siap dibagikan ke WhatsApp / Slack tim Anda.
                </p>
            </div>
        </div>
        <div class="hero-right">
            <div class="quick-stat-box">
                <div class="stat-lbl">Riwayat Standup</div>
                <div class="stat-val">{{ $history->total() }} <span style="font-size: 13px; font-weight: 500; color: #94a3b8;">tersimpan</span></div>
            </div>
        </div>
    </div>

    <!-- ── Main 2-Column Grid ── -->
    <div class="standup-layout mb-8">

        <!-- ════════ LEFT COLUMN: INPUT CONTROLS ════════ -->
        <div class="layout-col">
            <div class="panel-card">
                <div class="panel-header">
                    <div class="header-info">
                        <div class="step-num">1</div>
                        <div>
                            <h3 class="panel-heading">Input &amp; Sumber Data</h3>
                            <p class="panel-subheading">Pilih tanggal, sinkronkan data QA, atau tulis catatan manual</p>
                        </div>
                    </div>
                </div>

                <div class="panel-body">
                    <form id="standupForm" autocomplete="off">

                        <!-- Section: Date & Name -->
                        <div class="form-row-2 mb-4">
                            <div class="form-field">
                                <label class="field-label">
                                    <i class="fas fa-calendar-alt text-indigo-500 mr-1"></i> Tanggal Pekerjaan
                                </label>
                                <div class="input-with-action">
                                    <input type="date" id="standupDate" name="date" value="{{ $todayDate }}" required class="form-input" />
                                    <button type="button" id="setTodayBtn" class="btn-date-today" title="Pilih Hari Ini">Hari Ini</button>
                                </div>
                            </div>

                            <div class="form-field">
                                <label class="field-label">
                                    <i class="fas fa-user-circle text-indigo-500 mr-1"></i> Nama Pelapor
                                </label>
                                <input type="text" id="standupUserName" name="user_name" value="{{ $user->name }}" required class="form-input" placeholder="e.g. WAHYU SANJAYA" />
                            </div>
                        </div>

                        <!-- Section: System Activity QA Sync -->
                        <div class="activity-sync-box mb-4">
                            <div class="sync-header">
                                <label class="custom-switch-label">
                                    <input type="checkbox" id="includeSystemData" name="include_system_data" value="1" checked class="custom-switch-input">
                                    <span class="custom-switch-slider"></span>
                                    <span class="switch-text">
                                        <strong>Tarik Otomatis dari QA Portal</strong>
                                        <span class="switch-hint">Ambil Test Report &amp; Test Case pada tanggal ini</span>
                                    </span>
                                </label>
                                <button type="button" id="refreshActivityBtn" class="btn-refresh-sync" title="Sinkronkan ulang">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>

                            <!-- Dynamic Activity Status -->
                            <div id="activityStatusBox" class="sync-body">
                                @if($systemActivities['total_items'] > 0)
                                    <div class="sync-badge-row">
                                        <span class="pill-badge green">
                                            <i class="fas fa-check-circle"></i> {{ count($systemActivities['reports']) }} Test Report
                                        </span>
                                        <span class="pill-badge purple">
                                            <i class="fas fa-vial"></i> {{ count($systemActivities['test_cases']) }} Test Cases
                                        </span>
                                    </div>
                                    <div class="sync-list-collapsible">
                                        @foreach($systemActivities['reports'] as $rep)
                                            <div class="sync-item">
                                                <i class="fas fa-file-alt text-blue-500"></i>
                                                <div class="sync-item-text">
                                                    <strong>{{ $rep['name'] }}</strong>
                                                    <span class="text-slate-500">({{ $rep['project'] }}) — {{ $rep['total_cases'] }} case [{{ $rep['pass'] }} PASS, {{ $rep['fail'] }} FAIL]</span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="sync-empty">
                                        <i class="fas fa-info-circle text-blue-500"></i>
                                        <span>Tidak ada data pengetesan QA di portal pada tanggal ini. Anda dapat menuliskan catatan manual di bawah.</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Section: Manual Notes / Prompt -->
                        <div class="form-field mb-4">
                            <div class="field-header">
                                <label class="field-label" style="margin: 0;">
                                    <i class="fas fa-pen-nib text-indigo-500 mr-1"></i> Catatan Manual Bebas (Prompt)
                                </label>
                                <button type="button" id="fillExampleNotesBtn" class="btn-ghost-example">
                                    <i class="fas fa-magic mr-1"></i> Isi Contoh Catatan
                                </button>
                            </div>
                            
                            <textarea id="manualNotes" name="manual_notes" rows="4" class="form-textarea"
                                placeholder="Tuliskan catatan aktivitas bebas Anda di sini tanpa perlu merapikan format:
- Pekerjaan selesai: Ikut Pengiriman wifi phantom, screen record untuk video shoot...
- Kendala: ga ada
- Rencana besok: Transmate testing"></textarea>

                            <div class="quick-tags-container mt-2">
                                <span class="tag-label">💡 Template Cepat:</span>
                                <button type="button" class="quick-tag" data-text="Ikut Pengiriman wifi phantom, screen record untuk kebutuhan video shoot wifi phantom">+ Pengiriman wifi phantom &amp; video shoot</button>
                                <button type="button" class="quick-tag" data-text="Meeting harian koordinasi sprint dan review blocker tim">+ Meeting harian koordinasi</button>
                                <button type="button" class="quick-tag" data-text="Testing modul Checkout &amp; verifikasi bug payment gateway">+ Testing modul checkout</button>
                            </div>
                        </div>

                        <!-- Section: AI Provider Engine Selection -->
                        @php
                            $savedGeminiKey       = $user ? $user->gemini_api_key : '';
                            $savedOpenRouterKey   = $user ? $user->openrouter_api_key : '';
                            $savedOpenRouterModel = $user ? $user->getResolvedOpenRouterModel() : 'meta-llama/llama-3.1-8b-instruct:free';
                            $savedOllamaUrl       = $user ? $user->getResolvedOllamaBaseUrl() : 'http://localhost:11434';
                            $savedOllamaModel     = $user ? $user->getResolvedOllamaModel() : 'llama3.2';
                            $savedOmnirouteKey    = $user ? $user->omniroute_api_key : '';
                            $savedOmnirouteUrl    = $user ? $user->getResolvedOmnirouteBaseUrl() : 'http://202.155.143.157:20128/v1';
                            $savedOmnirouteModel  = $user ? $user->getResolvedOmnirouteModel() : 'auto/best-coding';
                            $savedOpenAiKey       = $user ? $user->openai_api_key : '';
                            $savedOpenAiModel     = $user ? $user->getResolvedOpenAiModel() : 'gpt-4o-mini';
                            $defaultProvider      = $user ? $user->getResolvedDefaultAiProvider() : 'gemini';
                        @endphp

                        <div class="form-field mb-5">
                            <label class="field-label" style="font-size: 12px; margin-bottom: 8px;">
                                <i class="fas fa-microchip text-indigo-500 mr-1"></i> AI Provider Engine
                            </label>
                            
                            <div class="provider-pill-grid">
                                <!-- Gemini -->
                                <label class="provider-pill {{ $defaultProvider === 'gemini' ? 'selected' : '' }}" data-provider="gemini">
                                    <input type="radio" name="ai_provider" value="gemini" {{ $defaultProvider === 'gemini' ? 'checked' : '' }}>
                                    <div class="pill-icon">🤖</div>
                                    <div class="pill-content">
                                        <div class="pill-name">Gemini</div>
                                        <div class="pill-desc">Google Flash</div>
                                    </div>
                                    @if($savedGeminiKey)
                                        <div class="pill-status ready" title="Configured">✓</div>
                                    @endif
                                </label>

                                <!-- OpenRouter -->
                                <label class="provider-pill {{ $defaultProvider === 'openrouter' ? 'selected' : '' }}" data-provider="openrouter">
                                    <input type="radio" name="ai_provider" value="openrouter" {{ $defaultProvider === 'openrouter' ? 'checked' : '' }}>
                                    <div class="pill-icon">🌐</div>
                                    <div class="pill-content">
                                        <div class="pill-name">OpenRouter</div>
                                        <div class="pill-desc">Multi-Model</div>
                                    </div>
                                    @if($savedOpenRouterKey)
                                        <div class="pill-status ready" title="Configured">✓</div>
                                    @endif
                                </label>

                                <!-- Ollama -->
                                <label class="provider-pill {{ $defaultProvider === 'ollama' ? 'selected' : '' }}" data-provider="ollama">
                                    <input type="radio" name="ai_provider" value="ollama" {{ $defaultProvider === 'ollama' ? 'checked' : '' }}>
                                    <div class="pill-icon">💻</div>
                                    <div class="pill-content">
                                        <div class="pill-name">Ollama</div>
                                        <div class="pill-desc">Local Offline</div>
                                    </div>
                                </label>

                                <!-- OmniRoute -->
                                <label class="provider-pill {{ $defaultProvider === 'omniroute' ? 'selected' : '' }}" data-provider="omniroute">
                                    <input type="radio" name="ai_provider" value="omniroute" {{ $defaultProvider === 'omniroute' ? 'checked' : '' }}>
                                    <div class="pill-icon">🔀</div>
                                    <div class="pill-content">
                                        <div class="pill-name">OmniRoute</div>
                                        <div class="pill-desc">Server 20128</div>
                                    </div>
                                </label>

                                <!-- OpenAI / Celeris -->
                                <label class="provider-pill {{ $defaultProvider === 'openai' ? 'selected' : '' }}" data-provider="openai">
                                    <input type="radio" name="ai_provider" value="openai" {{ $defaultProvider === 'openai' ? 'checked' : '' }}>
                                    <div class="pill-icon">⚡</div>
                                    <div class="pill-content">
                                        <div class="pill-name">OpenAI/Celeris</div>
                                        <div class="pill-desc">{{ $savedOpenAiModel }}</div>
                                    </div>
                                    @if($savedOpenAiKey)
                                        <div class="pill-status ready" title="Configured">✓</div>
                                    @endif
                                </label>
                            </div>
                        </div>

                        <!-- Error Alert Box -->
                        <div id="standupError" class="alert-error-box mb-4" style="display: none;">
                            <i class="fas fa-exclamation-circle text-red-500"></i>
                            <span id="standupErrorText"></span>
                        </div>

                        <!-- Generate Action Button -->
                        <button type="button" id="generateStandupBtn" class="btn-main-generate">
                            <span id="genBtnIcon" class="btn-icon-sparkle">✨</span>
                            <span id="genBtnText" class="btn-text-label">Generate Daily Standup</span>
                        </button>

                    </form>
                </div>
            </div>
        </div>

        <!-- ════════ RIGHT COLUMN: OUTPUT PREVIEW & ACTION ════════ -->
        <div class="layout-col">
            <div class="panel-card output-card">
                <div class="panel-header">
                    <div class="header-info">
                        <div class="step-num step-green">2</div>
                        <div>
                            <h3 class="panel-heading">Hasil Format Standup</h3>
                            <p class="panel-subheading">Teks terformat siap disalin ke chat WhatsApp / Slack</p>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="header-actions">
                        <button type="button" id="copyWhatsAppBtn" class="btn-copy-whatsapp" title="Salin teks ke clipboard">
                            <i class="fab fa-whatsapp"></i>
                            <span>Salin ke WhatsApp</span>
                        </button>
                        <button type="button" id="saveStandupBtn" class="btn-save-database" title="Simpan ke histori database">
                            <i class="fas fa-bookmark"></i>
                            <span>Simpan</span>
                        </button>
                    </div>
                </div>

                <div class="panel-body output-body">
                    <!-- Standup AI Loader State -->
                    <div id="standupLoadingCard" style="display: none; background: linear-gradient(135deg, #f8fafc, #f1f5f9); border: 1.5px dashed #cbd5e1; border-radius: 16px; padding: 36px 20px; text-align: center; margin-bottom: 20px; animation: slideUpFade 0.3s ease;">
                        <div style="display: inline-flex; position: relative; width: 68px; height: 68px; align-items: center; justify-content: center; margin-bottom: 14px;">
                            <div class="ai-spinner-ring"></div>
                            <i class="fas fa-brain text-purple-600" style="font-size: 24px; animation: pulseGlow 1.5s infinite ease-in-out;"></i>
                        </div>
                        <div style="font-size: 16px; font-weight: 700; color: #1e293b; margin-bottom: 4px;">
                            Sedang Merangkai Daily Standup...
                        </div>
                        <div id="standupLoadingSubtitle" style="font-size: 13px; color: #64748b; line-height: 1.5; min-height: 20px; transition: opacity 0.2s ease;">
                            Membaca log aktivitas pengujian QA & menyusun kalimat...
                        </div>
                        <div style="width: 220px; height: 5px; background: #e2e8f0; border-radius: 999px; margin: 16px auto 0; overflow: hidden; position: relative;">
                            <div class="ai-indeterminate-bar"></div>
                        </div>
                    </div>

                    <!-- Standup AI Success Checkmark Animation State -->
                    <div id="standupSuccessCard" style="display: none; background: #f0fdf4; border: 1.5px solid #86efac; border-radius: 16px; padding: 32px 20px; text-align: center; margin-bottom: 20px; animation: popIn 0.35s ease;">
                        <div class="checkmark-wrapper" style="margin: 0 auto 12px; display: inline-block;">
                            <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                                <circle class="checkmark__circle" cx="26" cy="26" r="23" fill="none"/>
                                <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                            </svg>
                        </div>
                        <div style="font-size: 17px; font-weight: 700; color: #15803d; margin-bottom: 4px; letter-spacing: -0.01em;">
                            Selesai! Standup Berhasil Digenerate
                        </div>
                        <div style="font-size: 13px; color: #16a34a;">
                            Menampilkan format pesan WhatsApp...
                        </div>
                    </div>

                    <!-- WhatsApp style container wrapper -->
                    <div id="standupBubbleWrapper" class="whatsapp-bubble-wrapper">
                        <div class="bubble-header">
                            <div class="bubble-title">
                                <i class="fab fa-whatsapp text-green-500 mr-1"></i>
                                <span>Pesan Standup Harian</span>
                            </div>
                            <div class="bubble-indicator">
                                <span class="format-badge">Plain Text Format</span>
                            </div>
                        </div>

                        <div class="bubble-editor-area">
                            <textarea id="standupResultOutput" rows="11" class="form-textarea whatsapp-textarea" placeholder="Teks hasil Daily Standup akan muncul di sini...

TANGGAL: 21-09-2026
NAMA: WAHYU SANJAYA
PEKERJAAN SELESAI: Ikut Pengiriman wifi phantom, screen record untuk kebutuhan video shoot wifi phantom

KENDALA: ga ada
RENCANA BERIKUTNYA: Transmate testing"></textarea>
                        </div>

                        <div class="bubble-footer">
                            <span class="edit-hint">
                                <i class="fas fa-pencil-alt mr-1"></i> Teks di atas dapat langsung diedit jika ingin mengubah detail secara manual sebelum menyalin.
                            </span>
                            <span id="copyFeedback" class="copy-success-pill" style="display: none;">
                                <i class="fas fa-check-circle"></i> Berhasil disalin ke clipboard!
                            </span>
                        </div>
                    </div>

                    <!-- Visual Breakdown Summary Accordion -->
                    <div class="visual-breakdown-card mt-4" id="breakdownSection" style="display: none;">
                        <h4 class="breakdown-title">
                            <i class="fas fa-layer-group text-indigo-500 mr-1"></i> Ringkasan Komponen Standup
                        </h4>
                        <div class="breakdown-grid">
                            <div class="breakdown-item">
                                <span class="item-label">TANGGAL</span>
                                <span id="bdDate" class="item-val font-semibold text-slate-800">-</span>
                            </div>
                            <div class="breakdown-item">
                                <span class="item-label">NAMA</span>
                                <span id="bdName" class="item-val font-semibold text-slate-800">-</span>
                            </div>
                            <div class="breakdown-item full">
                                <span class="item-label">PEKERJAAN SELESAI</span>
                                <span id="bdTasks" class="item-val text-slate-700">-</span>
                            </div>
                            <div class="breakdown-item">
                                <span class="item-label">KENDALA</span>
                                <span id="bdBlockers" class="item-val text-green-600 font-medium">ga ada</span>
                            </div>
                            <div class="breakdown-item">
                                <span class="item-label">RENCANA BERIKUTNYA</span>
                                <span id="bdNext" class="item-val text-slate-700">-</span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>

    <!-- ── Bottom Section: History Log ── -->
    <div class="panel-card shadow-sm">
        <div class="panel-header border-b border-slate-100">
            <div class="header-info">
                <div class="step-num step-slate">
                    <i class="fas fa-history" style="font-size: 13px;"></i>
                </div>
                <div>
                    <h3 class="panel-heading">Histori Laporan Harian Tersimpan</h3>
                    <p class="panel-subheading">Catatan standup yang pernah Anda simpan sebelumnya</p>
                </div>
            </div>
            <div class="history-count-badge">
                {{ $history->total() }} Total Laporan
            </div>
        </div>

        <div class="panel-body p-0">
            <div class="table-responsive">
                <table class="modern-history-table">
                    <thead>
                        <tr>
                            <th style="width: 140px;">Tanggal</th>
                            <th style="width: 180px;">Nama</th>
                            <th>Pekerjaan Selesai</th>
                            <th style="width: 160px;">Kendala</th>
                            <th style="width: 200px;">Rencana Berikutnya</th>
                            <th style="width: 130px; text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="historyTableBody">
                        @forelse($history as $item)
                            <tr id="row-standup-{{ $item->id }}">
                                <td>
                                    <div class="date-chip">
                                        <i class="far fa-calendar-alt"></i>
                                        <span>{{ \Carbon\Carbon::parse($item->date)->format('d-m-Y') }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="user-chip">
                                        <div class="user-mini-avatar">{{ strtoupper(substr($item->user_name, 0, 1)) }}</div>
                                        <span class="user-name-text">{{ $item->user_name }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="task-summary-text" title="{{ $item->completed_tasks }}">
                                        {{ Str::limit($item->completed_tasks, 75) }}
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $isClean = in_array(strtolower(trim($item->blockers)), ['ga ada', 'tidak ada', 'tidak ada kendala', '-', 'aman']);
                                    @endphp
                                    <span class="status-pill {{ $isClean ? 'safe' : 'issue' }}">
                                        {{ $isClean ? '✓ ' . $item->blockers : '⚠️ ' . Str::limit($item->blockers, 24) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="plan-summary-text" title="{{ $item->next_plans }}">
                                        {{ Str::limit($item->next_plans, 50) }}
                                    </div>
                                </td>
                                <td style="text-align: right;">
                                    <div class="action-btn-group">
                                        <button type="button" class="btn-action-load btn-load-history" 
                                                data-output="{{ e($item->formatted_output) }}"
                                                data-date="{{ \Carbon\Carbon::parse($item->date)->format('d-m-Y') }}"
                                                data-name="{{ $item->user_name }}"
                                                data-tasks="{{ $item->completed_tasks }}"
                                                data-blockers="{{ $item->blockers }}"
                                                data-next="{{ $item->next_plans }}"
                                                title="Muat teks ke editor">
                                            <i class="fas fa-eye"></i> Tampilkan
                                        </button>
                                        <button type="button" class="btn-action-del btn-delete-history" 
                                                data-id="{{ $item->id }}"
                                                title="Hapus laporan ini">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="empty-history-state">
                                        <div class="empty-icon-circle">
                                            <i class="fas fa-clipboard-list"></i>
                                        </div>
                                        <div class="empty-title">Belum Ada Riwayat Laporan Harian</div>
                                        <div class="empty-desc">Laporan yang Anda simpan akan tersimpan di sini agar mudah dilihat dan disalin kembali kapan saja.</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($history->hasPages())
                <div class="pagination-footer">
                    {{ $history->links() }}
                </div>
            @endif
        </div>
    </div>

</div>
@endsection

@push('styles')
<style>
/* ─────────────────────────────────────────────────────────────
   AI Daily Standup Refined Styling
───────────────────────────────────────────────────────────── */
.standup-wrapper {
    max-width: 1360px;
    margin: 0 auto;
    font-family: inherit;
}

/* ── Top Hero Banner ── */
.standup-hero {
    background: linear-gradient(135deg, #1e1b4b 0%, #0f172a 60%, #1e293b 100%);
    border-radius: 20px;
    padding: 24px 30px;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.08);
}
.hero-left {
    display: flex;
    align-items: center;
    gap: 20px;
    max-width: 820px;
}
.hero-icon-box {
    width: 60px;
    height: 60px;
    border-radius: 16px;
    background: linear-gradient(135deg, #6366f1, #a855f7, #ec4899);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
    color: white;
    flex-shrink: 0;
    box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
}
.hero-tag {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.15);
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    color: #e0e7ff;
    margin-bottom: 6px;
}
.pulse-indicator {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #22c55e;
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.35);
}
.hero-title {
    font-size: 20px;
    font-weight: 800;
    color: white;
    margin: 0 0 4px 0;
    letter-spacing: -0.3px;
}
.hero-subtitle {
    font-size: 13.5px;
    color: #cbd5e1;
    margin: 0;
    line-height: 1.5;
}
.quick-stat-box {
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 14px;
    padding: 12px 20px;
    text-align: right;
}
.quick-stat-box .stat-lbl {
    font-size: 11px;
    text-transform: uppercase;
    color: #94a3b8;
    font-weight: 600;
}
.quick-stat-box .stat-val {
    font-size: 24px;
    font-weight: 800;
    color: white;
}

/* ── 2-Column Grid ── */
.standup-layout {
    display: grid;
    grid-template-columns: 1.15fr 1fr;
    gap: 24px;
    align-items: stretch;
}
@media (max-width: 1024px) {
    .standup-layout {
        grid-template-columns: 1fr;
    }
}

/* ── Modern Panel Card ── */
.panel-card {
    background: white;
    border-radius: 18px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 20px -4px rgba(0, 0, 0, 0.04);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
.panel-header {
    padding: 18px 22px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fafbfc;
}
.header-info {
    display: flex;
    align-items: center;
    gap: 12px;
}
.step-num {
    width: 32px;
    height: 32px;
    border-radius: 10px;
    background: #eef2ff;
    color: #4f46e5;
    font-weight: 800;
    font-size: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.step-green {
    background: #dcfce7;
    color: #166534;
}
.step-slate {
    background: #f1f5f9;
    color: #475569;
}
.panel-heading {
    font-size: 15.5px;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
}
.panel-subheading {
    font-size: 12px;
    color: #64748b;
    margin: 2px 0 0 0;
}
.panel-body {
    padding: 22px;
    flex: 1;
}

/* ── Form Inputs ── */
.form-row-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
@media (max-width: 600px) {
    .form-row-2 {
        grid-template-columns: 1fr;
    }
}
.form-field {
    display: flex;
    flex-direction: column;
}
.field-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
}
.field-label {
    font-size: 13px;
    font-weight: 600;
    color: #334155;
    display: flex;
    align-items: center;
}
.input-with-action {
    display: flex;
    gap: 6px;
}
.form-input {
    width: 100%;
    padding: 9px 13px;
    border: 1.5px solid #cbd5e1;
    border-radius: 10px;
    font-size: 13.5px;
    color: #1e293b;
    background: white;
    outline: none;
    transition: all 0.2s ease;
}
.form-input:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
}
.btn-date-today {
    background: #f1f5f9;
    color: #475569;
    border: 1.5px solid #cbd5e1;
    padding: 0 12px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.15s ease;
}
.btn-date-today:hover {
    background: #e2e8f0;
    color: #1e293b;
}

.form-textarea {
    width: 100%;
    padding: 12px 14px;
    border: 1.5px solid #cbd5e1;
    border-radius: 12px;
    font-size: 13.5px;
    line-height: 1.6;
    color: #1e293b;
    background: white;
    outline: none;
    resize: vertical;
    transition: all 0.2s ease;
}
.form-textarea:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
}

.btn-ghost-example {
    background: #eef2ff;
    color: #4f46e5;
    border: 1px solid #c7d2fe;
    padding: 3px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.15s ease;
}
.btn-ghost-example:hover {
    background: #e0e7ff;
}

/* ── Activity Sync Box ── */
.activity-sync-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 14px 16px;
}
.sync-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.custom-switch-label {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    user-select: none;
}
.custom-switch-input {
    display: none;
}
.custom-switch-slider {
    width: 38px;
    height: 22px;
    background: #cbd5e1;
    border-radius: 20px;
    position: relative;
    transition: all 0.25s ease;
    flex-shrink: 0;
}
.custom-switch-slider::after {
    content: '';
    position: absolute;
    top: 2.5px;
    left: 3px;
    width: 17px;
    height: 17px;
    border-radius: 50%;
    background: white;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    transition: all 0.25s ease;
}
.custom-switch-input:checked + .custom-switch-slider {
    background: #6366f1;
}
.custom-switch-input:checked + .custom-switch-slider::after {
    transform: translateX(15px);
}
.switch-text {
    display: flex;
    flex-direction: column;
}
.switch-text strong {
    font-size: 13px;
    color: #1e293b;
}
.switch-hint {
    font-size: 11px;
    color: #64748b;
}
.btn-refresh-sync {
    background: white;
    border: 1px solid #cbd5e1;
    color: #475569;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s ease;
}
.btn-refresh-sync:hover {
    background: #f1f5f9;
    color: #1e293b;
    border-color: #94a3b8;
}
.sync-body {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #e2e8f0;
}
.sync-badge-row {
    display: flex;
    gap: 8px;
    margin-bottom: 8px;
}
.pill-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
}
.pill-badge.green {
    background: #dcfce7;
    color: #15803d;
}
.pill-badge.purple {
    background: #f3e8ff;
    color: #7e22ce;
}
.sync-list-collapsible {
    display: flex;
    flex-direction: column;
    gap: 6px;
    max-height: 120px;
    overflow-y: auto;
}
.sync-item {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    font-size: 12px;
    color: #334155;
    background: white;
    padding: 7px 10px;
    border-radius: 8px;
    border: 1px solid #f1f5f9;
}
.sync-empty {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: #64748b;
}

/* ── Quick Tags ── */
.quick-tags-container {
    display: flex;
    gap: 6px;
    align-items: center;
    flex-wrap: wrap;
}
.tag-label {
    font-size: 11px;
    color: #94a3b8;
    font-weight: 600;
}
.quick-tag {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
    padding: 3px 9px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
}
.quick-tag:hover {
    background: #e2e8f0;
    color: #0f172a;
    border-color: #cbd5e1;
}

/* ── Provider Pills ── */
.provider-pill-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
}
@media (max-width: 600px) {
    .provider-pill-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
.provider-pill {
    border: 1.5px solid #e2e8f0;
    background: #f8fafc;
    border-radius: 12px;
    padding: 10px;
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    user-select: none;
}
.provider-pill:hover {
    border-color: #cbd5e1;
    background: #f1f5f9;
}
.provider-pill.selected {
    border-color: #6366f1;
    background: #eef2ff;
    box-shadow: 0 2px 8px rgba(99, 102, 241, 0.12);
}
.provider-pill input[type="radio"] {
    display: none;
}
.pill-icon {
    font-size: 20px;
    flex-shrink: 0;
}
.pill-content {
    flex: 1;
    min-width: 0;
}
.pill-name {
    font-size: 12px;
    font-weight: 700;
    color: #1e293b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.pill-desc {
    font-size: 10.5px;
    color: #64748b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.pill-status.ready {
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: #22c55e;
    color: white;
    font-size: 9px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    position: absolute;
    top: 6px;
    right: 6px;
}

/* ── Main Generate Button ── */
.btn-main-generate {
    width: 100%;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%);
    color: white;
    border: none;
    padding: 13px 20px;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    box-shadow: 0 4px 18px rgba(99, 102, 241, 0.35);
    transition: all 0.2s ease;
}
.btn-main-generate:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 24px rgba(99, 102, 241, 0.45);
}
.btn-icon-sparkle {
    font-size: 18px;
}

/* ── WhatsApp Output Section ── */
.output-card {
    height: 100%;
}
.output-body {
    display: flex;
    flex-direction: column;
}
.header-actions {
    display: flex;
    gap: 8px;
}
.btn-copy-whatsapp {
    background: #25D366;
    color: white;
    border: none;
    padding: 8px 14px;
    border-radius: 10px;
    font-size: 12.5px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 2px 8px rgba(37, 211, 102, 0.35);
    transition: all 0.15s ease;
}
.btn-copy-whatsapp:hover {
    background: #20ba59;
    transform: translateY(-1px);
}
.btn-copy-whatsapp i {
    font-size: 15px;
}
.btn-save-database {
    background: white;
    color: #334155;
    border: 1.5px solid #cbd5e1;
    padding: 8px 14px;
    border-radius: 10px;
    font-size: 12.5px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.15s ease;
}
.btn-save-database:hover {
    background: #f8fafc;
    border-color: #94a3b8;
    color: #0f172a;
}

.whatsapp-bubble-wrapper {
    background: #efeae2;
    background-image: radial-gradient(#d4ccc0 1px, transparent 1px);
    background-size: 16px 16px;
    border-radius: 16px;
    border: 1px solid #dcd7ce;
    padding: 14px;
    flex: 1;
    display: flex;
    flex-direction: column;
}
.bubble-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}
.bubble-title {
    font-size: 12.5px;
    font-weight: 700;
    color: #475569;
    display: flex;
    align-items: center;
}
.format-badge {
    background: rgba(255,255,255,0.8);
    color: #64748b;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}
.bubble-editor-area {
    flex: 1;
    display: flex;
}
.whatsapp-textarea {
    flex: 1;
    background: white;
    border: 1px solid #d4ccc0;
    border-radius: 12px;
    font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, Courier, monospace;
    font-size: 13.5px;
    line-height: 1.7;
    color: #111827;
    padding: 16px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.05);
}
.whatsapp-textarea:focus {
    background: white;
    border-color: #25D366;
    box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.15);
}
.bubble-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 10px;
    font-size: 11.5px;
    color: #64748b;
}
.copy-success-pill {
    background: #dcfce7;
    color: #15803d;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

/* ── Visual Breakdown Card ── */
.visual-breakdown-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 14px;
}
.breakdown-title {
    font-size: 13px;
    font-weight: 700;
    color: #334155;
    margin: 0 0 10px 0;
}
.breakdown-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}
.breakdown-item {
    background: white;
    border: 1px solid #f1f5f9;
    padding: 8px 12px;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
}
.breakdown-item.full {
    grid-column: span 2;
}
.item-label {
    font-size: 10.5px;
    color: #94a3b8;
    font-weight: 700;
    text-transform: uppercase;
}
.item-val {
    font-size: 13px;
    margin-top: 2px;
}

/* ── Modern History Table ── */
.history-count-badge {
    background: #e2e8f0;
    color: #334155;
    font-size: 11.5px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 20px;
}
.modern-history-table {
    width: 100%;
    border-collapse: collapse;
}
.modern-history-table th {
    background: #f8fafc;
    padding: 12px 18px;
    font-size: 11.5px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid #e2e8f0;
}
.modern-history-table td {
    padding: 14px 18px;
    font-size: 13px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
.modern-history-table tbody tr:hover {
    background: #fafbfc;
}
.date-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-weight: 700;
    color: #1e293b;
    background: #f1f5f9;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
}
.date-chip i {
    color: #6366f1;
}
.user-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.user-mini-avatar {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: white;
    font-size: 11px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
}
.user-name-text {
    font-weight: 600;
    color: #334155;
}
.task-summary-text {
    color: #1e293b;
    max-width: 320px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.plan-summary-text {
    color: #64748b;
    max-width: 220px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.status-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
}
.status-pill.safe {
    background: #dcfce7;
    color: #15803d;
}
.status-pill.issue {
    background: #fee2e2;
    color: #b91c1c;
}
.action-btn-group {
    display: flex;
    gap: 6px;
    justify-content: flex-end;
}
.btn-action-load {
    background: #eef2ff;
    color: #4338ca;
    border: 1px solid #c7d2fe;
    padding: 5px 11px;
    border-radius: 7px;
    font-size: 11.5px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.15s ease;
}
.btn-action-load:hover {
    background: #e0e7ff;
}
.btn-action-del {
    background: #fff1f2;
    color: #e11d48;
    border: 1px solid #fecdd3;
    padding: 5px 9px;
    border-radius: 7px;
    font-size: 11.5px;
    cursor: pointer;
    transition: all 0.15s ease;
}
.btn-action-del:hover {
    background: #ffe4e6;
}
.empty-history-state {
    text-align: center;
    padding: 36px 20px;
    color: #94a3b8;
}
.empty-icon-circle {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: #f1f5f9;
    color: #94a3b8;
    font-size: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px auto;
}
.empty-title {
    font-size: 14px;
    font-weight: 700;
    color: #475569;
    margin-bottom: 4px;
}
.empty-desc {
    font-size: 12.5px;
    color: #94a3b8;
    max-width: 380px;
    margin: 0 auto;
}
.pagination-footer {
    padding: 16px 20px;
    border-top: 1px solid #f1f5f9;
}
.alert-error-box {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #dc2626;
    padding: 12px 16px;
    border-radius: 10px;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* ── Responsive & Touch Refinements ── */
.table-responsive {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

@media (max-width: 1024px) {
    .standup-layout {
        grid-template-columns: 1fr;
        gap: 20px;
    }
}

@media (max-width: 768px) {
    .standup-hero {
        flex-direction: column;
        align-items: flex-start;
        padding: 20px;
        gap: 16px;
    }
    .hero-left {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    .hero-icon-box {
        width: 50px;
        height: 50px;
        font-size: 22px;
        border-radius: 14px;
    }
    .hero-title {
        font-size: 18px;
    }
    .quick-stat-box {
        width: 100%;
        text-align: left;
    }
    .modern-history-table {
        min-width: 680px;
    }
}

@media (max-width: 640px) {
    .panel-header {
        flex-direction: column;
        align-items: stretch;
        gap: 14px;
    }
    .header-actions {
        width: 100%;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }
    .btn-copy-whatsapp, .btn-save-database {
        justify-content: center;
        width: 100%;
        padding: 9px 10px;
        font-size: 12px;
    }
    .breakdown-grid {
        grid-template-columns: 1fr;
    }
    .breakdown-item.full {
        grid-column: span 1;
    }
    .provider-pill-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
    }
    .bubble-footer {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
}

@keyframes slideUpFade {
    0%   { transform: translateY(16px); opacity: 0; }
    100% { transform: translateY(0);    opacity: 1; }
}
@keyframes popIn {
    0%   { transform: scale(0.85); opacity: 0; }
    100% { transform: scale(1);    opacity: 1; }
}

/* AI Loader styling */
.ai-spinner-ring {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    border: 3.5px solid #e2e8f0;
    border-top-color: #6366f1;
    border-right-color: #8b5cf6;
    animation: spinRing 0.9s linear infinite;
}
@keyframes spinRing {
    to { transform: rotate(360deg); }
}
@keyframes pulseGlow {
    0%, 100% { transform: scale(1); opacity: 0.85; }
    50% { transform: scale(1.15); opacity: 1; filter: drop-shadow(0 0 8px rgba(139,92,246,0.6)); }
}
.ai-indeterminate-bar {
    position: absolute;
    top: 0;
    bottom: 0;
    width: 45%;
    background: linear-gradient(90deg, #6366f1, #8b5cf6);
    border-radius: 999px;
    animation: indeterminateSlide 1.3s infinite ease-in-out;
}
@keyframes indeterminateSlide {
    0%   { left: -45%; }
    100% { left: 100%; }
}

/* Checkmark animation */
.checkmark {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    display: block;
    stroke-width: 3.5;
    stroke: #10b981;
    stroke-miterlimit: 10;
    box-shadow: inset 0px 0px 0px #10b981;
    animation: checkmark-fill .4s ease-in-out .4s forwards, checkmark-scale .3s ease-in-out .7s both;
}
.checkmark__circle {
    stroke-dasharray: 166;
    stroke-dashoffset: 166;
    stroke-width: 3.5;
    stroke-miterlimit: 10;
    stroke: #10b981;
    fill: none;
    animation: checkmark-stroke 0.5s cubic-bezier(0.65, 0, 0.45, 1) forwards;
}
.checkmark__check {
    transform-origin: 50% 50%;
    stroke-dasharray: 48;
    stroke-dashoffset: 48;
    stroke: #ffffff;
    stroke-width: 3.5;
    stroke-linecap: round;
    stroke-linejoin: round;
    animation: checkmark-stroke 0.35s cubic-bezier(0.65, 0, 0.45, 1) 0.45s forwards;
}
@keyframes checkmark-stroke {
    100% { stroke-dashoffset: 0; }
}
@keyframes checkmark-scale {
    0%, 100% { transform: none; }
    50% { transform: scale3d(1.1, 1.1, 1); }
}
@keyframes checkmark-fill {
    100% { box-shadow: inset 0px 0px 0px 30px #10b981; }
}
</style>
@endpush

@push('scripts')
<script>
(function() {
    'use strict';

    const CSRF_TOKEN      = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
    const GENERATE_URL    = "{{ route('daily-standup.generate') }}";
    const SAVE_URL        = "{{ route('daily-standup.store') }}";
    const ACTIVITY_URL    = "{{ route('daily-standup.activity') }}";

    // Elements
    const dateInput       = document.getElementById('standupDate');
    const setTodayBtn     = document.getElementById('setTodayBtn');
    const userNameInput   = document.getElementById('standupUserName');
    const includeSysCheck = document.getElementById('includeSystemData');
    const manualNotesEl   = document.getElementById('manualNotes');
    const generateBtn     = document.getElementById('generateStandupBtn');
    const resultOutputEl  = document.getElementById('standupResultOutput');
    const copyBtn         = document.getElementById('copyWhatsAppBtn');
    const saveBtn         = document.getElementById('saveStandupBtn');
    const copyFeedback    = document.getElementById('copyFeedback');
    const errBox          = document.getElementById('standupError');
    const errText         = document.getElementById('standupErrorText');
    const statusBox       = document.getElementById('activityStatusBox');
    const refreshBtn      = document.getElementById('refreshActivityBtn');
    const fillExampleBtn  = document.getElementById('fillExampleNotesBtn');

    // Visual breakdown elements
    const breakdownSection = document.getElementById('breakdownSection');
    const bdDate           = document.getElementById('bdDate');
    const bdName           = document.getElementById('bdName');
    const bdTasks          = document.getElementById('bdTasks');
    const bdBlockers       = document.getElementById('bdBlockers');
    const bdNext           = document.getElementById('bdNext');

    // Loader & Success elements
    const loadingCard      = document.getElementById('standupLoadingCard');
    const successCard      = document.getElementById('standupSuccessCard');
    const bubbleWrapper    = document.getElementById('standupBubbleWrapper');

    let currentParsedData = null;

    // ── Shortcut Today Button ──
    if (setTodayBtn) {
        setTodayBtn.addEventListener('click', () => {
            const today = new Date().toISOString().split('T')[0];
            if (dateInput.value !== today) {
                dateInput.value = today;
                fetchSystemActivity();
            }
        });
    }

    // ── Quick Chips Click ──
    document.querySelectorAll('.quick-tag').forEach(tag => {
        tag.addEventListener('click', () => {
            const txt = tag.getAttribute('data-text');
            if (!txt) return;
            const current = manualNotesEl.value.trim();
            if (current) {
                manualNotesEl.value = current + ',\n' + txt;
            } else {
                manualNotesEl.value = txt;
            }
            manualNotesEl.focus();
        });
    });

    // ── Fill Example Preset ──
    if (fillExampleBtn) {
        fillExampleBtn.addEventListener('click', () => {
            manualNotesEl.value = 'Ikut Pengiriman wifi phantom, screen record untuk kebutuhan video shoot wifi phantom.\nKendala: ga ada\nRencana berikutnya: Transmate testing';
            manualNotesEl.focus();
            showToast('📋 Contoh catatan berhasil diisikan!');
        });
    }

    // ── Provider Card Selection ──
    document.querySelectorAll('.provider-pill').forEach(pill => {
        pill.addEventListener('click', () => {
            document.querySelectorAll('.provider-pill').forEach(p => p.classList.remove('selected'));
            pill.classList.add('selected');
            const radio = pill.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
        });
    });

    // ── Fetch Activity When Date Changes ──
    dateInput.addEventListener('change', fetchSystemActivity);
    if (refreshBtn) refreshBtn.addEventListener('click', fetchSystemActivity);

    async function fetchSystemActivity() {
        const dateVal = dateInput.value;
        if (!dateVal) return;

        statusBox.innerHTML = '<div class="sync-empty"><i class="fas fa-spinner fa-spin mr-1"></i><span>Memeriksa aktivitas pengujian QA di sistem...</span></div>';

        try {
            const res = await fetch(`${ACTIVITY_URL}?date=${encodeURIComponent(dateVal)}`, {
                headers: { 'Accept': 'application/json' }
            });
            const json = await res.json();
            if (json.success && json.data) {
                renderActivityStatus(json.data);
            }
        } catch (e) {
            statusBox.innerHTML = '<div class="sync-empty" style="color:#ef4444;"><i class="fas fa-exclamation-triangle mr-1"></i><span>Gagal memeriksa aktivitas sistem.</span></div>';
        }
    }

    function renderActivityStatus(data) {
        if (data.total_items > 0) {
            let html = `
                <div class="sync-badge-row">
                    <span class="pill-badge green">
                        <i class="fas fa-check-circle"></i> ${data.reports.length} Test Report
                    </span>
                    <span class="pill-badge purple">
                        <i class="fas fa-vial"></i> ${data.test_cases.length} Test Cases
                    </span>
                </div>
                <div class="sync-list-collapsible">
            `;
            data.reports.forEach(r => {
                html += `
                    <div class="sync-item">
                        <i class="fas fa-file-alt text-blue-500"></i>
                        <div class="sync-item-text">
                            <strong>${esc(r.name)}</strong>
                            <span class="text-slate-500">(${esc(r.project)}) — ${r.total_cases} case [${r.pass} PASS, ${r.fail} FAIL]</span>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            statusBox.innerHTML = html;
        } else {
            statusBox.innerHTML = `
                <div class="sync-empty">
                    <i class="fas fa-info-circle text-blue-500"></i>
                    <span>Tidak ada data pengetesan QA di portal pada tanggal ini. Anda dapat menuliskan catatan manual di bawah.</span>
                </div>
            `;
        }
    }

    // ── Generate Daily Standup ──
    generateBtn.addEventListener('click', async () => {
        const dateVal     = dateInput.value;
        const userName    = userNameInput.value.trim();
        const provider    = document.querySelector('input[name="ai_provider"]:checked')?.value || 'gemini';
        const includeSys  = includeSysCheck.checked ? 1 : 0;
        const manualNotes = manualNotesEl.value.trim();

        if (!userName) {
            showError('Nama pelapor wajib diisi.');
            userNameInput.focus();
            return;
        }

        hideError();
        setLoading(true);

        const payload = {
            date: dateVal,
            user_name: userName,
            provider: provider,
            include_system_data: includeSys,
            manual_notes: manualNotes,
        };

        try {
            const res = await fetch(GENERATE_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept':       'application/json',
                },
                body: JSON.stringify(payload),
            });

            const json = await res.json();
            if (!res.ok || !json.success) {
                throw new Error(json.message || 'Gagal memproses Daily Standup.');
            }

            currentParsedData = json.data;

            // 1. Matikan loader
            setLoading(false);

            // 2. Tampilkan animasi centang terlebih dahulu, baru munculkan datanya
            showSuccessCheckmark(() => {
                resultOutputEl.value = currentParsedData.formatted_output || '';
                
                // Populate breakdown
                updateBreakdown(currentParsedData);
                if (bubbleWrapper) {
                    bubbleWrapper.style.display = 'block';
                    bubbleWrapper.style.animation = 'slideUpFade 0.4s ease forwards';
                }
                if (breakdownSection) {
                    breakdownSection.style.display = 'block';
                    breakdownSection.style.animation = 'slideUpFade 0.4s ease forwards';
                }

                resultOutputEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                showToast('✨ Daily Standup berhasil digenerate!');
            });

        } catch (err) {
            setLoading(false);
            if (bubbleWrapper) bubbleWrapper.style.display = 'block';
            showError(err.message || 'Terjadi kesalahan saat memproses data.');
        }
    });

    function updateBreakdown(data) {
        if (!data) return;
        bdDate.textContent     = data.tanggal || '-';
        bdName.textContent     = data.nama || '-';
        bdTasks.textContent    = data.pekerjaan_selesai || '-';
        bdBlockers.textContent = data.kendala || 'ga ada';
        bdNext.textContent     = data.rencana_berikutnya || '-';

        const isClean = ['ga ada', 'tidak ada', 'tidak ada kendala', '-', 'aman'].includes((data.kendala || '').toLowerCase().trim());
        bdBlockers.className = isClean ? 'item-val text-green-600 font-medium' : 'item-val text-red-600 font-medium';
    }

    // ── Copy to Clipboard (WhatsApp) ──
    copyBtn.addEventListener('click', async () => {
        const textToCopy = resultOutputEl.value.trim();
        if (!textToCopy) {
            showError('Tidak ada teks untuk disalin. Silakan generate standup terlebih dahulu.');
            return;
        }

        try {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                await navigator.clipboard.writeText(textToCopy);
            } else {
                resultOutputEl.select();
                document.execCommand('copy');
            }

            copyFeedback.style.display = 'inline-flex';
            copyBtn.style.background = '#15803d';
            copyBtn.innerHTML = '<i class="fas fa-check mr-1"></i> Tersalin!';
            showToast('📋 Format Daily Standup berhasil disalin!');

            setTimeout(() => {
                copyFeedback.style.display = 'none';
                copyBtn.style.background = '#25D366';
                copyBtn.innerHTML = '<i class="fab fa-whatsapp mr-1"></i> Salin ke WhatsApp';
            }, 3000);
        } catch (err) {
            showError('Gagal menyalin teks ke clipboard: ' + err.message);
        }
    });

    // ── Save to Database History ──
    saveBtn.addEventListener('click', async () => {
        const textVal = resultOutputEl.value.trim();
        if (!textVal) {
            showError('Belum ada teks laporan yang bisa disimpan.');
            return;
        }

        const dateVal  = dateInput.value;
        const userName = userNameInput.value.trim();

        const payload = {
            date: dateVal,
            user_name: userName,
            formatted_output: textVal,
            completed_tasks: currentParsedData?.pekerjaan_selesai || '',
            blockers: currentParsedData?.kendala || 'ga ada',
            next_plans: currentParsedData?.rencana_berikutnya || '',
            raw_input: manualNotesEl.value.trim(),
        };

        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...';

        try {
            const res = await fetch(SAVE_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept':       'application/json',
                },
                body: JSON.stringify(payload),
            });

            const json = await res.json();
            if (!res.ok || !json.success) {
                throw new Error(json.message || 'Gagal menyimpan laporan.');
            }

            showToast('💾 ' + json.message);
            setTimeout(() => location.reload(), 1000);
        } catch (err) {
            showError(err.message || 'Gagal menyimpan.');
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fas fa-bookmark mr-1"></i> Simpan';
        }
    });

    // ── Load History Item to Output ──
    document.querySelectorAll('.btn-load-history').forEach(btn => {
        btn.addEventListener('click', () => {
            const output = btn.getAttribute('data-output');
            if (output) {
                resultOutputEl.value = output;
                
                updateBreakdown({
                    tanggal: btn.getAttribute('data-date'),
                    nama: btn.getAttribute('data-name'),
                    pekerjaan_selesai: btn.getAttribute('data-tasks'),
                    kendala: btn.getAttribute('data-blockers'),
                    rencana_berikutnya: btn.getAttribute('data-next')
                });
                breakdownSection.style.display = 'block';

                resultOutputEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                showToast('📋 Laporan berhasil dimuat ke panel editor!');
            }
        });
    });

    // ── Delete History Item ──
    document.querySelectorAll('.btn-delete-history').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id = btn.getAttribute('data-id');
            if (!id || !confirm('Yakin ingin menghapus laporan harian ini?')) return;

            try {
                const res = await fetch(`/daily-standup/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json',
                    }
                });

                const json = await res.json();
                if (json.success) {
                    const row = document.getElementById(`row-standup-${id}`);
                    if (row) row.remove();
                    showToast('Laporan harian berhasil dihapus.');
                }
            } catch (e) {
                alert('Gagal menghapus item.');
            }
        });
    });

    // ── UI Helpers ──
    let standupLoadingInterval = null;

    function setLoading(on) {
        generateBtn.disabled = on;
        const iconEl = document.getElementById('genBtnIcon');
        const textEl = document.getElementById('genBtnText');

        if (on) {
            if (iconEl) iconEl.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i>';
            if (textEl) textEl.textContent = 'Sedang Menganalisis...';
            generateBtn.style.opacity = '0.85';
            generateBtn.style.background = 'linear-gradient(135deg, #4f46e5, #7c3aed)';

            if (loadingCard) {
                loadingCard.style.display = 'block';
                loadingCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
            if (successCard) successCard.style.display = 'none';
            if (bubbleWrapper) bubbleWrapper.style.display = 'none';
            if (breakdownSection) breakdownSection.style.display = 'none';

            const msgs = [
                'Membaca log aktivitas pengujian QA di sistem...',
                'Mengekstrak catatan & mengidentifikasi kendala...',
                'Merangkai kalimat standup format profesional WhatsApp...'
            ];
            let msgIdx = 0;
            const subTitleEl = document.getElementById('standupLoadingSubtitle');
            if (subTitleEl) subTitleEl.textContent = msgs[0];

            clearInterval(standupLoadingInterval);
            standupLoadingInterval = setInterval(() => {
                msgIdx = (msgIdx + 1) % msgs.length;
                if (subTitleEl) {
                    subTitleEl.style.opacity = '0';
                    setTimeout(() => {
                        subTitleEl.textContent = msgs[msgIdx];
                        subTitleEl.style.opacity = '1';
                    }, 200);
                }
            }, 2200);
        } else {
            clearInterval(standupLoadingInterval);
            if (loadingCard) loadingCard.style.display = 'none';
            generateBtn.disabled = false;
            generateBtn.style.opacity = '1';
            generateBtn.style.background = 'linear-gradient(135deg, #6366f1, #8b5cf6)';
            if (iconEl) iconEl.textContent = '✨';
            if (textEl) textEl.textContent = 'Generate Daily Standup';
        }
    }

    function showSuccessCheckmark(onComplete) {
        if (successCard) {
            successCard.style.display = 'block';
            successCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        const iconEl = document.getElementById('genBtnIcon');
        const textEl = document.getElementById('genBtnText');
        if (iconEl) iconEl.innerHTML = '<i class="fas fa-check"></i>';
        if (textEl) textEl.textContent = 'Selesai!';
        generateBtn.style.background = 'linear-gradient(135deg, #10b981, #059669)';

        setTimeout(() => {
            if (successCard) {
                successCard.style.display = 'none';
            }
            if (iconEl) iconEl.textContent = '✨';
            if (textEl) textEl.textContent = 'Generate Ulang Standup';
            generateBtn.style.background = 'linear-gradient(135deg, #6366f1, #8b5cf6)';

            if (typeof onComplete === 'function') {
                onComplete();
            }
        }, 950);
    }

    function showError(msg) {
        errText.textContent = msg;
        errBox.style.display = 'flex';
    }

    function hideError() {
        errBox.style.display = 'none';
    }

    function esc(str) {
        const d = document.createElement('div');
        d.textContent = str || '';
        return d.innerHTML;
    }

    function showToast(msg) {
        const t = document.createElement('div');
        t.style.cssText = `
            position:fixed;bottom:24px;right:24px;z-index:99999;
            background:linear-gradient(135deg,#10b981,#059669);color:white;
            padding:12px 20px;border-radius:12px;font-size:14px;font-weight:600;
            box-shadow:0 8px 24px rgba(16,185,129,0.35);
            animation:slideUp 0.3s ease;
        `;
        t.textContent = msg;
        document.body.appendChild(t);
        setTimeout(() => t.remove(), 3500);
    }
})();
</script>
@endpush
