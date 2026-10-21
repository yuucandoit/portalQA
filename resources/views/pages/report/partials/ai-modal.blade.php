{{--
    AI Auto-Fill Modal
    Usage: @include('pages.report.partials.ai-modal')
    Depends on: addTestCase() function from the parent form's JS scope
--}}

<!-- ✨ AI Mode Trigger Button -->
<button type="button" id="aiModeBtn"
        style="display: inline-flex; align-items: center; gap: 8px;
               background: linear-gradient(135deg, #6366f1, #8b5cf6, #ec4899);
               color: white; border: none; padding: 9px 18px; border-radius: 10px;
               font-weight: 700; font-size: 13.5px; cursor: pointer;
               box-shadow: 0 4px 15px rgba(99,102,241,0.4);
               transition: all 0.2s ease; white-space: nowrap;">
    <span style="font-size: 16px;">✨</span> AI Auto-Fill
</button>

<!-- ───────────────────────── AI MODAL OVERLAY ───────────────────────── -->
<div id="aiModal"
     style="display: none; position: fixed; inset: 0; z-index: 9999;
            background: rgba(15,23,42,0.6); backdrop-filter: blur(4px);
            align-items: center; justify-content: center; padding: 20px;">

    <div style="background: white; border-radius: 20px; width: 100%; max-width: 680px;
                max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 60px rgba(0,0,0,0.3);
                animation: slideUp 0.25s ease;">

        <!-- Modal Header -->
        <div style="background: linear-gradient(135deg, #6366f1, #8b5cf6); padding: 20px 24px; border-radius: 20px 20px 0 0; position: sticky; top: 0; z-index: 1;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <h2 style="color: white; font-size: 18px; font-weight: 800; margin: 0; display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 22px;">✨</span> AI Auto-Fill Mode
                    </h2>
                    <p style="color: rgba(255,255,255,0.88); font-size: 13px; margin: 4px 0 0;">
                        Paste data mentah bebas (chat obrolan, tiket bug, log error, catatan kasar) → AI susun &amp; isi form otomatis
                    </p>
                </div>
                <button type="button" id="aiModalClose"
                        style="background: rgba(255,255,255,0.2); border: none; color: white;
                               width: 36px; height: 36px; border-radius: 50%; cursor: pointer;
                               font-size: 20px; display: flex; align-items: center; justify-content: center;">
                    &times;
                </button>
            </div>
        </div>

        <div style="padding: 24px;">

            @php
                /** @var \App\Models\User|null $user */
                $user = Auth::user();
                $savedGeminiKey      = $user ? $user->gemini_api_key : '';
                $savedOpenRouterKey  = $user ? $user->openrouter_api_key : '';
                $savedOpenRouterModel = $user ? $user->getResolvedOpenRouterModel() : 'meta-llama/llama-3.1-8b-instruct:free';
                $savedOllamaUrl      = $user ? $user->getResolvedOllamaBaseUrl() : 'http://localhost:11434';
                $savedOllamaModel    = $user ? $user->getResolvedOllamaModel() : 'llama3.2';
                $savedOmnirouteKey   = $user ? $user->omniroute_api_key : '';
                $savedOmnirouteUrl   = $user ? $user->getResolvedOmnirouteBaseUrl() : 'http://202.155.143.157:20128/v1';
                $savedOmnirouteModel = $user ? $user->getResolvedOmnirouteModel() : 'auto/best-coding';
                $savedOpenAiKey      = $user ? $user->openai_api_key : '';
                $savedOpenAiUrl      = $user ? $user->getResolvedOpenAiBaseUrl() : 'https://api.openai.com/v1';
                $savedOpenAiModel    = $user ? $user->getResolvedOpenAiModel() : 'gpt-4o-mini';
                $defaultProvider     = $user ? $user->getResolvedDefaultAiProvider() : 'gemini';
            @endphp

            <!-- Step 1: Provider Selection -->
            <div style="margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <label style="font-size: 13px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">
                        1. Pilih AI Provider
                    </label>
                    <a href="{{ route('settings.index') }}?tab=apikeys" target="_blank" style="font-size: 12px; color: #6366f1; text-decoration: none; font-weight: 600;">
                        <i class="fas fa-cog mr-1"></i> Simpan Permanent di Settings →
                    </a>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(115px, 1fr)); gap: 10px;">

                    <!-- Gemini -->
                    <div id="card-gemini" class="provider-card" data-provider="gemini" style="border: 2px solid {{ $defaultProvider === 'gemini' ? '#6366f1' : '#e2e8f0' }}; background: {{ $defaultProvider === 'gemini' ? '#eef2ff' : '#f8fafc' }}; border-radius: 12px; padding: 12px; cursor: pointer; transition: all 0.2s;">
                        <input type="radio" name="ai_provider" value="gemini" {{ $defaultProvider === 'gemini' ? 'checked' : '' }} style="display:none;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                            <span style="font-size: 20px;">🤖</span>
                            <span style="font-weight: 700; font-size: 13px; color: #3730a3;">Google Gemini</span>
                        </div>
                        <div style="font-size: 11.5px; color: #6366f1; font-weight: 600;">gemini-1.5-flash</div>
                        <div style="font-size: 11px; color: #64748b; margin-top: 4px;">
                            @if($savedGeminiKey)
                                <span style="color:#16a34a; font-weight:600;">✓ Saved in Settings</span>
                            @else
                                Cepat & akurat
                            @endif
                        </div>
                    </div>

                    <!-- OpenRouter -->
                    <div id="card-openrouter" class="provider-card" data-provider="openrouter" style="border: 2px solid {{ $defaultProvider === 'openrouter' ? '#6366f1' : '#e2e8f0' }}; background: {{ $defaultProvider === 'openrouter' ? '#eef2ff' : '#f8fafc' }}; border-radius: 12px; padding: 12px; cursor: pointer; transition: all 0.2s;">
                        <input type="radio" name="ai_provider" value="openrouter" {{ $defaultProvider === 'openrouter' ? 'checked' : '' }} style="display:none;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                            <span style="font-size: 20px;">🌐</span>
                            <span style="font-weight: 700; font-size: 13px; color: #334155;">OpenRouter</span>
                        </div>
                        <div style="font-size: 11.5px; color: #64748b; font-weight: 600;">{{ $savedOpenRouterModel }}</div>
                        <div style="font-size: 11px; color: #64748b; margin-top: 4px;">
                            @if($savedOpenRouterKey)
                                <span style="color:#16a34a; font-weight:600;">✓ Saved in Settings</span>
                            @else
                                Multi-model, gratis
                            @endif
                        </div>
                    </div>

                    <!-- Ollama -->
                    <div id="card-ollama" class="provider-card" data-provider="ollama" style="border: 2px solid {{ $defaultProvider === 'ollama' ? '#6366f1' : '#e2e8f0' }}; background: {{ $defaultProvider === 'ollama' ? '#eef2ff' : '#f8fafc' }}; border-radius: 12px; padding: 12px; cursor: pointer; transition: all 0.2s;">
                        <input type="radio" name="ai_provider" value="ollama" {{ $defaultProvider === 'ollama' ? 'checked' : '' }} style="display:none;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                            <span style="font-size: 20px;">💻</span>
                            <span style="font-weight: 700; font-size: 13px; color: #334155;">Ollama Lokal</span>
                        </div>
                        <div style="font-size: 11.5px; color: #64748b; font-weight: 600;">{{ $savedOllamaModel }}</div>
                        <div style="font-size: 11px; color: #64748b; margin-top: 4px;">100% offline</div>
                    </div>

                    <!-- OmniRoute -->
                    <div id="card-omniroute" class="provider-card" data-provider="omniroute" style="border: 2px solid {{ $defaultProvider === 'omniroute' ? '#6366f1' : '#e2e8f0' }}; background: {{ $defaultProvider === 'omniroute' ? '#eef2ff' : '#f8fafc' }}; border-radius: 12px; padding: 12px; cursor: pointer; transition: all 0.2s;">
                        <input type="radio" name="ai_provider" value="omniroute" {{ $defaultProvider === 'omniroute' ? 'checked' : '' }} style="display:none;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                            <span style="font-size: 20px;">🔀</span>
                            <span style="font-weight: 700; font-size: 13px; color: #334155;">OmniRoute</span>
                        </div>
                        <div style="font-size: 11.5px; color: #64748b; font-weight: 600;">{{ $savedOmnirouteModel }}</div>
                        <div style="font-size: 11px; color: #64748b; margin-top: 4px;">Server 20128</div>
                    </div>

                    <!-- OpenAI / Celeris -->
                    <div id="card-openai" class="provider-card" data-provider="openai" style="border: 2px solid {{ $defaultProvider === 'openai' ? '#6366f1' : '#e2e8f0' }}; background: {{ $defaultProvider === 'openai' ? '#eef2ff' : '#f8fafc' }}; border-radius: 12px; padding: 12px; cursor: pointer; transition: all 0.2s;">
                        <input type="radio" name="ai_provider" value="openai" {{ $defaultProvider === 'openai' ? 'checked' : '' }} style="display:none;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                            <span style="font-size: 20px;">⚡</span>
                            <span style="font-weight: 700; font-size: 13px; color: #334155;">OpenAI / Celeris</span>
                        </div>
                        <div style="font-size: 11.5px; color: #64748b; font-weight: 600;">{{ $savedOpenAiModel }}</div>
                        <div style="font-size: 11px; color: #64748b; margin-top: 4px;">
                            @if($savedOpenAiKey)
                                <span style="color:#16a34a; font-weight:600;">✓ Saved in Settings</span>
                            @else
                                Custom endpoint
                            @endif
                        </div>
                    </div>

                </div>
            </div>

            <!-- API Key / Model Fields (dynamic) -->
            <div id="aiProviderConfig" style="margin-bottom: 20px;">
                <!-- Gemini config -->
                <div id="config-gemini" style="display: {{ $defaultProvider === 'gemini' ? 'block' : 'none' }};">
                    <label style="font-size: 12.5px; font-weight: 600; color: #475569; display: block; margin-bottom: 6px;">
                        🔑 Gemini API Key <span style="color: #ef4444;">*</span>
                        <a href="https://aistudio.google.com/app/apikey" target="_blank"
                           style="color: #6366f1; font-weight: 400; font-size: 11.5px; margin-left: 6px;">Dapatkan di sini →</a>
                    </label>
                    <input type="password" id="geminiApiKey" value="{{ $savedGeminiKey }}" placeholder="AIzaSy..."
                           style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none; font-family: monospace;" />
                    <p style="font-size: 11px; color: #94a3b8; margin-top: 4px;">
                        @if($savedGeminiKey)
                            ✨ Menggunakan API Key dari Settings Anda. Anda dapat mengubahnya per-request di sini.
                        @else
                            Silakan masukkan API Key Anda untuk memproses AI, atau atur permanen di menu Settings.
                        @endif
                    </p>
                </div>

                <!-- OpenRouter config -->
                <div id="config-openrouter" style="display: {{ $defaultProvider === 'openrouter' ? 'block' : 'none' }};">
                    <label style="font-size: 12.5px; font-weight: 600; color: #475569; display: block; margin-bottom: 6px;">
                        🔑 OpenRouter API Key <span style="color: #ef4444;">*</span>
                        <a href="https://openrouter.ai/keys" target="_blank"
                           style="color: #6366f1; font-weight: 400; font-size: 11.5px; margin-left: 6px;">Dapatkan gratis →</a>
                    </label>
                    <input type="password" id="openrouterApiKey" value="{{ $savedOpenRouterKey }}" placeholder="sk-or-..."
                           style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none; font-family: monospace;" />
                    <label style="font-size: 12.5px; font-weight: 600; color: #475569; display: block; margin: 10px 0 6px;">Model Name</label>
                    <input type="text" id="openrouterModel" value="{{ $savedOpenRouterModel }}" placeholder="meta-llama/llama-3.1-8b-instruct:free"
                           style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px; outline: none;" />
                </div>

                <!-- Ollama config -->
                <div id="config-ollama" style="display: {{ $defaultProvider === 'ollama' ? 'block' : 'none' }};">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div>
                            <label style="font-size: 12.5px; font-weight: 600; color: #475569; display: block; margin-bottom: 6px;">Model</label>
                            <input type="text" id="ollamaModel" value="{{ $savedOllamaModel }}"
                                   style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px; outline: none;" />
                        </div>
                        <div>
                            <label style="font-size: 12.5px; font-weight: 600; color: #475569; display: block; margin-bottom: 6px;">Base URL</label>
                            <input type="text" id="ollamaBaseUrl" value="{{ $savedOllamaUrl }}"
                                   style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px; outline: none;" />
                        </div>
                    </div>
                    <p style="font-size: 11px; color: #94a3b8; margin-top: 4px;">Pastikan Ollama sudah berjalan. Jalankan: <code style="background:#f1f5f9;padding:2px 6px;border-radius:4px;">ollama serve</code></p>
                </div>

                <!-- OmniRoute config -->
                <div id="config-omniroute" style="display: {{ $defaultProvider === 'omniroute' ? 'block' : 'none' }};">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                        <div>
                            <label style="font-size: 12.5px; font-weight: 600; color: #475569; display: block; margin-bottom: 6px;">Model Name</label>
                            <input type="text" id="omnirouteModel" value="{{ $savedOmnirouteModel }}"
                                   style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px; outline: none;" />
                        </div>
                        <div>
                            <label style="font-size: 12.5px; font-weight: 600; color: #475569; display: block; margin-bottom: 6px;">Base URL</label>
                            <input type="text" id="omnirouteBaseUrl" value="{{ $savedOmnirouteUrl }}"
                                   style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px; outline: none;" />
                        </div>
                    </div>
                    <label style="font-size: 12.5px; font-weight: 600; color: #475569; display: block; margin-bottom: 6px;">API Key (Opsional)</label>
                    <input type="password" id="omnirouteApiKey" value="{{ $savedOmnirouteKey }}" placeholder="Kosongkan jika tidak ada key"
                           style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none; font-family: monospace;" />
                </div>

                <!-- OpenAI / Celeris config -->
                <div id="config-openai" style="display: {{ $defaultProvider === 'openai' ? 'block' : 'none' }};">
                    <label style="font-size: 12.5px; font-weight: 600; color: #475569; display: block; margin-bottom: 6px;">
                        🔑 API Key (ck_... / sk-...) <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="password" id="openaiApiKey" value="{{ $savedOpenAiKey }}" placeholder="ck_REPLACE_WITH_YOUR_KEY atau sk-..."
                           style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none; font-family: monospace;" />
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 10px;">
                        <div>
                            <label style="font-size: 12.5px; font-weight: 600; color: #475569; display: block; margin-bottom: 6px;">Model Name</label>
                            <input type="text" id="openaiModel" value="{{ $savedOpenAiModel }}" placeholder="gpt-4o-mini"
                                   style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px; outline: none;" />
                        </div>
                        <div>
                            <label style="font-size: 12.5px; font-weight: 600; color: #475569; display: block; margin-bottom: 6px;">Base URL</label>
                            <input type="text" id="openaiBaseUrl" value="{{ $savedOpenAiUrl }}" placeholder="https://api.openai.com/v1"
                                   style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px; outline: none;" />
                        </div>
                    </div>
                    <p style="font-size: 11px; color: #94a3b8; margin-top: 4px;">Mendukung OpenAI resmi, proxy Celeris (ck_...), vLLM, dan endpoint OpenAI-compatible lainnya.</p>
                </div>
            </div>

            <!-- Step 2: Target Test Type Selection -->
            <div style="margin-bottom: 20px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <label style="font-size: 13px; font-weight: 700; color: #334155; display: flex; align-items: center; gap: 6px; margin: 0;">
                        🧪 Target Tipe Testing
                    </label>
                    <span style="font-size: 11px; color: #64748b; font-weight: 500;">Tersinkronisasi dengan form</span>
                </div>
                <select id="aiModalTestType"
                        style="width: 100%; padding: 10px 12px; border: 1.5px solid #cbd5e1; border-radius: 10px;
                               font-size: 13.5px; outline: none; background: white; font-weight: 600; color: #1e293b;
                               cursor: pointer; transition: all 0.2s;">
                    <option value="functional">Functional Test (Module / Fitur & Test Data)</option>
                    <option value="integration">Integration Test (Integration Point & Dependencies)</option>
                    <option value="regression">Regression Test (Related Feature & Reason Linked)</option>
                    <option value="performance">Performance Test (Test Type & Test Parameters)</option>
                    <option value="reliability">Reliability Test (Test Type & Failure Duration)</option>
                    <option value="stress">Stress Test (Stress Type & Parameters)</option>
                    <option value="negative">Negative Test (Module & Negative Test Data)</option>
                    <option value="validation">Validation Test (Validation Type & Kriteria Validasi)</option>
                    <option value="hardware">Hardware Test (Perangkat/Hardware & Lingkungan Uji)</option>
                </select>
                <p style="font-size: 11px; color: #94a3b8; margin-top: 4px;">
                    Prompt AI akan difokuskan untuk mengekstrak field dinamis yang sesuai dengan tipe testing ini.
                </p>
            </div>

            <!-- Step 3: Raw Input -->
            <div style="margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 8px;">
                    <div>
                        <label style="font-size: 13px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">
                            3. Masukkan Data Mentah Bebas
                        </label>
                        <span style="font-size: 11px; color: #6366f1; font-weight: 600; margin-left: 6px;">(Format apa saja diterima)</span>
                    </div>
                    <div style="display: flex; gap: 6px;">
                        <button type="button" id="aiFillSampleBtn"
                                style="background: #eef2ff; color: #4f46e5; border: 1px solid #c7d2fe;
                                       padding: 5px 11px; border-radius: 8px; font-size: 12px; font-weight: 600;
                                       cursor: pointer; display: inline-flex; align-items: center; gap: 4px; transition: all 0.2s;">
                            <i class="fas fa-magic"></i> Isi Contoh Data
                        </button>
                        <button type="button" id="aiPasteClipboardBtn"
                                style="background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1;
                                       padding: 5px 11px; border-radius: 8px; font-size: 12px; font-weight: 600;
                                       cursor: pointer; display: inline-flex; align-items: center; gap: 4px; transition: all 0.2s;">
                            <i class="fas fa-paste"></i> Paste Clipboard
                        </button>
                    </div>
                </div>

                <!-- Format tags -->
                <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 10px;">
                    <span style="background: #f1f5f9; color: #475569; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 600;">💬 Chat WA / Slack</span>
                    <span style="background: #f1f5f9; color: #475569; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 600;">🐞 Tiket Bug / Jira</span>
                    <span style="background: #f1f5f9; color: #475569; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 600;">📋 Catatan Tester Kasar</span>
                    <span style="background: #f1f5f9; color: #475569; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 600;">⚡ Log Error / API Response</span>
                </div>

                <textarea id="aiRawInput" rows="8" placeholder="Tuliskan atau paste data mentah bebas apa saja di sini tanpa perlu merapikan format:
- Chat tim: &quot;Tadi nyoba checkout barang stok habis, eh malah tembus ke payment (FAILED)...&quot;
- Tiket bug / issue Jira / user story
- Catatan acak / bullet point hasil testing
- Log error console / dump response API

AI akan otomatis menganalisis, memecah test case, menyusun langkah (steps), dan menyesuaikan ke form!"
                          style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 12px;
                                 font-size: 13.5px; font-family: inherit; resize: vertical; outline: none;
                                 line-height: 1.6; color: #334155;"></textarea>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 6px;">
                    <span style="font-size: 11.5px; color: #94a3b8;">Format bebas — AI akan mengabstraksi dan merekonstruksi langkah secara otomatis.</span>
                    <span id="aiCharCount" style="font-size: 11.5px; color: #94a3b8;">0 / 10000</span>
                </div>
            </div>

            <!-- Generate Button -->
            <button type="button" id="aiGenerateBtn"
                    style="width: 100%; background: linear-gradient(135deg, #6366f1, #8b5cf6);
                           color: white; border: none; padding: 13px; border-radius: 12px;
                           font-size: 15px; font-weight: 700; cursor: pointer; display: flex;
                           align-items: center; justify-content: center; gap: 10px;
                           box-shadow: 0 4px 15px rgba(99,102,241,0.35); margin-bottom: 20px;
                           transition: all 0.2s;">
                <span id="aiBtnIcon">✨</span>
                <span id="aiBtnText">Generate dengan AI</span>
            </button>

            <!-- Error Box -->
            <div id="aiError" style="display: none; background: #fef2f2; border: 1px solid #fecaca;
                 color: #dc2626; padding: 12px 16px; border-radius: 10px; font-size: 13.5px;
                 margin-bottom: 16px; align-items: flex-start; gap: 10px;">
                <i class="fas fa-exclamation-circle" style="margin-top: 2px; flex-shrink: 0;"></i>
                <span id="aiErrorText"></span>
            </div>

            <!-- AI Loader State -->
            <div id="aiLoadingCard" style="display: none; background: linear-gradient(135deg, #f8fafc, #f1f5f9); border: 1.5px dashed #cbd5e1; border-radius: 14px; padding: 26px 20px; text-align: center; margin-bottom: 20px; animation: slideUpFade 0.3s ease;">
                <div style="display: inline-flex; position: relative; width: 68px; height: 68px; align-items: center; justify-content: center; margin-bottom: 14px;">
                    <div class="ai-spinner-ring"></div>
                    <i class="fas fa-brain text-purple-600" style="font-size: 24px; animation: pulseGlow 1.5s infinite ease-in-out;"></i>
                </div>
                <div id="aiLoadingTitle" style="font-size: 15px; font-weight: 700; color: #1e293b; margin-bottom: 4px;">
                    Sedang Menganalisis dengan AI...
                </div>
                <div id="aiLoadingSubtitle" style="font-size: 12.5px; color: #64748b; line-height: 1.5; min-height: 20px; transition: opacity 0.2s ease;">
                    Membaca catatan mentah & mengekstrak skenario testing...
                </div>
                <div style="width: 220px; height: 5px; background: #e2e8f0; border-radius: 999px; margin: 14px auto 0; overflow: hidden; position: relative;">
                    <div class="ai-indeterminate-bar"></div>
                </div>
            </div>

            <!-- AI Success Checkmark Animation State -->
            <div id="aiSuccessCard" style="display: none; background: #f0fdf4; border: 1.5px solid #86efac; border-radius: 14px; padding: 24px 20px; text-align: center; margin-bottom: 20px; animation: popIn 0.35s ease;">
                <div class="checkmark-wrapper" style="margin: 0 auto 12px; display: inline-block;">
                    <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                        <circle class="checkmark__circle" cx="26" cy="26" r="23" fill="none"/>
                        <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                    </svg>
                </div>
                <div style="font-size: 16px; font-weight: 700; color: #15803d; margin-bottom: 4px; letter-spacing: -0.01em;">
                    Selesai! Berhasil Digenerate
                </div>
                <div style="font-size: 13px; color: #16a34a;">
                    Menampilkan hasil pratinjau test case...
                </div>
            </div>

            <!-- Step 4: Preview Result -->
            <div id="aiPreviewSection" style="display: none;">
                <div style="border-top: 1px solid #f1f5f9; padding-top: 20px; margin-bottom: 16px;">
                    <label style="font-size: 13px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 12px;">
                        4. Preview Hasil AI
                    </label>

                    <!-- Report Info Preview -->
                    <div id="aiPreviewMeta"
                         style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;
                                 padding: 14px 16px; margin-bottom: 14px;">
                    </div>

                    <!-- Test Cases Preview -->
                    <div id="aiPreviewCases" style="display: flex; flex-direction: column; gap: 10px; max-height: 280px; overflow-y: auto;"></div>
                </div>

                <!-- Apply Button -->
                <button type="button" id="aiApplyBtn"
                        style="width: 100%; background: linear-gradient(135deg, #10b981, #059669);
                               color: white; border: none; padding: 13px; border-radius: 12px;
                               font-size: 15px; font-weight: 700; cursor: pointer; display: flex;
                               align-items: center; justify-content: center; gap: 10px;
                               box-shadow: 0 4px 15px rgba(16,185,129,0.3); transition: all 0.2s;">
                    <i class="fas fa-magic"></i> Terapkan ke Form
                </button>
            </div>

        </div><!-- /padding -->
    </div><!-- /modal box -->
</div><!-- /overlay -->

<style>
@keyframes slideUp {
    from { transform: translateY(30px); opacity: 0; }
    to   { transform: translateY(0);    opacity: 1; }
}
@keyframes slideUpFade {
    0%   { transform: translateY(16px); opacity: 0; }
    100% { transform: translateY(0);    opacity: 1; }
}
@keyframes popIn {
    0%   { transform: scale(0.85); opacity: 0; }
    100% { transform: scale(1);    opacity: 1; }
}
.provider-card:hover { box-shadow: 0 4px 14px rgba(99,102,241,0.15); }
#aiGenerateBtn:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(99,102,241,0.4); }
#aiApplyBtn:hover    { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(16,185,129,0.4); }

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

<script>
(function () {
    const PARSE_URL   = "{{ route('reports.ai-parse') }}";
    const CSRF_TOKEN  = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

    // ── Elements ──────────────────────────────────────────
    const btn         = document.getElementById('aiModeBtn');
    const modal       = document.getElementById('aiModal');
    const closeBtn    = document.getElementById('aiModalClose');
    const radios      = document.querySelectorAll('input[name="ai_provider"]');
    const generateBtn = document.getElementById('aiGenerateBtn');
    const applyBtn    = document.getElementById('aiApplyBtn');
    const rawInput    = document.getElementById('aiRawInput');
    const charCount   = document.getElementById('aiCharCount');
    const errBox      = document.getElementById('aiError');
    const errText     = document.getElementById('aiErrorText');
    const previewSec  = document.getElementById('aiPreviewSection');
    const previewMeta = document.getElementById('aiPreviewMeta');
    const previewCase = document.getElementById('aiPreviewCases');
    const loadingCard = document.getElementById('aiLoadingCard');
    const successCard = document.getElementById('aiSuccessCard');
    const fillSampleBtn = document.getElementById('aiFillSampleBtn');
    const pasteClipboardBtn = document.getElementById('aiPasteClipboardBtn');
    const modalTestTypeSelect = document.getElementById('aiModalTestType');
    const mainTestTypeSelect  = document.getElementById('testType');

    let parsedData = null;

    // ── Open / Close & Sync Test Type ───────────────────────
    btn.addEventListener('click', () => {
        if (mainTestTypeSelect && modalTestTypeSelect) {
            modalTestTypeSelect.value = mainTestTypeSelect.value || 'functional';
        }
        modal.style.display = 'flex';
        rawInput.focus();
    });
    closeBtn.addEventListener('click', () => closeModal());
    modal.addEventListener('click',    (e) => { if (e.target === modal) closeModal(); });

    if (modalTestTypeSelect) {
        modalTestTypeSelect.addEventListener('change', () => {
            if (mainTestTypeSelect) {
                mainTestTypeSelect.value = modalTestTypeSelect.value;
                mainTestTypeSelect.dispatchEvent(new Event('change'));
            }
        });
    }

    if (mainTestTypeSelect) {
        mainTestTypeSelect.addEventListener('change', () => {
            if (modalTestTypeSelect) {
                modalTestTypeSelect.value = mainTestTypeSelect.value;
            }
        });
    }

    function closeModal() {
        modal.style.display = 'none';
        if (previewSec)  previewSec.style.display  = 'none';
        if (loadingCard) loadingCard.style.display = 'none';
        if (successCard) successCard.style.display = 'none';
        clearInterval(loadingInterval);
        hideError();
        parsedData = null;
    }

    // ── Sample QA Data Presets ─────────────────────────────
    const sampleTexts = [
`Halo tim, ini catatan hasil tes pengujian fitur checkout & promo sprint 26 tadi siang:
1. Pas nyoba login akun tester terus checkout 2 item dan bayar via BCA Virtual Account sukses, status transaksi langsung update jadi PAID (PASS).
2. Pas coba pake voucher promo DISCOUNT50 yang harusnya udah expired dari kemarin, diskonnya masih kepotong 50% di checkout! (FAILED - bug kritikal, mohon backend cek validasi tanggal expired voucher).
3. Pas coba checkout barang yang stoknya 0, ada pop-up error 'Stok produk tidak mencukupi' dan tombol bayar langsung terblokir (PASS).
Project: E-Commerce Mobile App. Tester: Chandra Maulana. Tanggal: 13 Agustus 2026.`,

`Laporan Testing Sprint 26 - E-Commerce Mobile App
Tanggal: 13 Agustus 2026
Tester: Chandra Maulana

Hasil Pengujian Feature Checkout & Payment Gateway:

1. Pengujian Checkout dengan Produk Out of Stock:
- Precondition: Produk "Sepatu Running Nike" stok = 0 di database.
- Steps: User menambahkan produk ke keranjang lalu menekan tombol Checkout.
- Expected Result: Sistem menampilkan error "Stok barang tidak mencukupi" dan memblokir proses checkout.
- Actual Result: Sistem berhasil memvalidasi dan pesan error muncul sesuai ekspektasi (STATUS: PASS).

2. Pengujian Diskon Voucher Expired:
- Precondition: Voucher "DISCOUNT50" sudah kedaluwarsa sejak kemarin.
- Steps: User menginput kode voucher "DISCOUNT50" pada kolom promo di halaman checkout.
- Expected Result: Sistem menolak voucher dan menampilkan "Kode promo sudah tidak berlaku".
- Actual Result: Kode voucher tetap memotong harga total belanja sebesar 50% (STATUS: FAILED).
- Notes QA: Bug kritikal, API promo tidak mengecek expiry date di backend!

3. Pengujian Webhook Callback Payment Mandiri VA:
- Steps: Trigger webhook payment callback saat transaksi sukses.
- Expected Result: Nomor VA ter-generate dan status transaksi terupdate menjadi PAID.
- Actual Result: Callback diterima HTTP 200 OK dan status DB terupdate otomatis (STATUS: PASS).`,

`Report Integration Test Payment Gateway & Order Service
Project: Core Payment Gateway System (v2.1.0)
Tanggal: 13 Agustus 2026

1. Test Case: Webhook Callback Payment Notification
- Syarat: Order #ORD-9923 dibuat dengan status PENDING.
- Step 1: Kirim payload webhook callback dari Payment Gateway ke endpoint /api/v1/payments/callback.
- Expected Result: Callback diterima dengan HTTP status 200 OK dan status Order di DB terupdate menjadi PAID.
- Actual Result: Callback diterima 200 OK, status DB terupdate otomatis (STATUS: PASS).

2. Test Case: Callback Timeout & Automatic Retry
- Syarat: Payment Gateway mengalami latency 6000ms.
- Step 1: Trigger webhook payment callback saat gateway slow response.
- Expected Result: Service mencatat retry_pending dan scheduler menjalankan retry pada percobaan ke-2.
- Actual Result: Job retry berjalan dan transaksi sukses ter-verify pada percobaan kedua (STATUS: PASS).`
    ];
    let sampleIdx = 0;

    if (fillSampleBtn) {
        fillSampleBtn.addEventListener('click', () => {
            rawInput.value = sampleTexts[sampleIdx % sampleTexts.length];
            sampleIdx++;
            rawInput.dispatchEvent(new Event('input'));
            hideError();
            showToast('📋 Contoh data mentah berhasil diisikan!');
        });
    }

    if (pasteClipboardBtn) {
        pasteClipboardBtn.addEventListener('click', async () => {
            try {
                if (!navigator.clipboard || !navigator.clipboard.readText) {
                    throw new Error('Clipboard API tidak didukung browser ini. Silakan gunakan Ctrl+V / Cmd+V.');
                }
                const text = await navigator.clipboard.readText();
                if (!text.trim()) {
                    showError('Clipboard kosong. Silakan copy teks terlebih dahulu.');
                    return;
                }
                rawInput.value = text;
                rawInput.dispatchEvent(new Event('input'));
                hideError();
                showToast('📋 Teks dari clipboard berhasil dipaste!');
            } catch (err) {
                showError(err.message || 'Gagal membaca clipboard.');
            }
        });
    }

    // ── Provider Card Selection ───────────────────────────
    document.querySelectorAll('.provider-card').forEach(card => {
        card.addEventListener('click', () => {
            const provider = card.getAttribute('data-provider');
            if (provider) {
                switchProvider(provider);
            }
        });
    });

    function switchProvider(provider) {
        hideError();
        // Update card styles & checked radios
        ['gemini', 'openrouter', 'ollama', 'omniroute', 'openai'].forEach(p => {
            const card = document.getElementById(`card-${p}`);
            const radio = card ? card.querySelector('input[name="ai_provider"]') : null;
            const config = document.getElementById(`config-${p}`);

            if (card) {
                if (p === provider) {
                    card.style.borderColor = '#6366f1';
                    card.style.background  = '#eef2ff';
                    if (radio) radio.checked = true;
                } else {
                    card.style.borderColor = '#e2e8f0';
                    card.style.background  = '#f8fafc';
                    if (radio) radio.checked = false;
                }
            }

            if (config) {
                config.style.display = (p === provider) ? 'block' : 'none';
            }
        });
    }

    // ── Character Counter ─────────────────────────────────
    rawInput.addEventListener('input', () => {
        const len = rawInput.value.length;
        charCount.textContent = `${len} / 10000`;
        charCount.style.color = len > 9000 ? '#ef4444' : '#94a3b8';
    });

    // ── Generate ──────────────────────────────────────────
    generateBtn.addEventListener('click', async () => {
        const rawText  = rawInput.value.trim();
        const provider = document.querySelector('input[name="ai_provider"]:checked')?.value ?? 'gemini';
        const testType = modalTestTypeSelect?.value || mainTestTypeSelect?.value || 'functional';

        if (!rawText) {
            showError('Paste data mentah terlebih dahulu.');
            return;
        }

        hideError();

        const payload = { raw_text: rawText, provider, test_type: testType };

        // Validate API key requirement
        if (provider === 'gemini') {
            const keyEl = document.getElementById('geminiApiKey');
            const key   = keyEl ? keyEl.value.trim() : '';
            if (!key) {
                showError('🔑 Gemini API Key belum diisi! Silakan masukkan API Key Anda pada kolom bertanda kunci di atas atau simpan permanen di menu Settings.');
                if (keyEl) {
                    keyEl.style.borderColor = '#ef4444';
                    keyEl.focus();
                }
                return;
            } else if (keyEl) {
                keyEl.style.borderColor = '#cbd5e1';
            }
            payload.api_key = key;
        } else if (provider === 'openrouter') {
            const keyEl = document.getElementById('openrouterApiKey');
            const key   = keyEl ? keyEl.value.trim() : '';
            if (!key) {
                showError('🔑 OpenRouter API Key belum diisi! Silakan masukkan API Key Anda pada kolom bertanda kunci di atas atau simpan permanen di menu Settings.');
                if (keyEl) {
                    keyEl.style.borderColor = '#ef4444';
                    keyEl.focus();
                }
                return;
            } else if (keyEl) {
                keyEl.style.borderColor = '#cbd5e1';
            }
            payload.api_key = key;
            const model = document.getElementById('openrouterModel')?.value.trim();
            if (model) payload.model = model;
        } else if (provider === 'ollama') {
            const model   = document.getElementById('ollamaModel')?.value.trim();
            const baseUrl = document.getElementById('ollamaBaseUrl')?.value.trim();
            if (model)   payload.model    = model;
            if (baseUrl) payload.base_url = baseUrl;
        } else if (provider === 'omniroute') {
            const key     = document.getElementById('omnirouteApiKey')?.value.trim() || '';
            const model   = document.getElementById('omnirouteModel')?.value.trim() || '';
            const baseUrl = document.getElementById('omnirouteBaseUrl')?.value.trim() || '';
            if (key)     payload.api_key  = key;
            if (model)   payload.model    = model;
            if (baseUrl) payload.base_url = baseUrl;
        } else if (provider === 'openai') {
            const keyEl = document.getElementById('openaiApiKey');
            const key   = keyEl ? keyEl.value.trim() : '';
            if (!key) {
                showError('🔑 API Key OpenAI / Celeris belum diisi! Silakan masukkan API Key Anda pada kolom di atas atau simpan permanen di Settings.');
                if (keyEl) {
                    keyEl.style.borderColor = '#ef4444';
                    keyEl.focus();
                }
                return;
            } else if (keyEl) {
                keyEl.style.borderColor = '#cbd5e1';
            }
            payload.api_key = key;
            const model   = document.getElementById('openaiModel')?.value.trim();
            const baseUrl = document.getElementById('openaiBaseUrl')?.value.trim();
            if (model)   payload.model    = model;
            if (baseUrl) payload.base_url = baseUrl;
        }

        setLoading(true);

        try {
            const res  = await fetch(PARSE_URL, {
                method:  'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept':       'application/json',
                },
                body: JSON.stringify(payload),
            });
            const json = await res.json();

            if (!res.ok || !json.success) {
                throw new Error(json.message || 'Gagal memproses data.');
            }

            parsedData = json.data;

            // 1. Matikan loader
            setLoading(false);

            // 2. Tampilkan animasi centang terlebih dahulu, baru munculkan datanya
            showSuccessCheckmark(() => {
                renderPreview(parsedData);
                previewSec.style.display = 'block';
                previewSec.style.animation = 'slideUpFade 0.4s ease forwards';
                previewSec.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            });

        } catch (err) {
            setLoading(false);
            showError(err.message);
        }
    });

    // ── Apply to Form ─────────────────────────────────────
    applyBtn.addEventListener('click', () => {
        if (!parsedData) return;

        // Fill report-level fields if present on the page
        const reportNameEl  = document.querySelector('[name="report_name"]');
        const testTypeEl    = document.querySelector('[name="test_type"]');
        const reportDateEl  = document.querySelector('[name="report_date"]');
        const descriptionEl = document.querySelector('[name="description"]');
        const projectEl     = document.querySelector('[name="project_id"]');
        const sprintEl      = document.getElementById('sprint');
        const cycleEl       = document.getElementById('cycle');

        if (reportNameEl)  reportNameEl.value  = parsedData.report_name  || '';
        if (testTypeEl)    testTypeEl.value    = parsedData.test_type    || 'functional';
        if (reportDateEl)  reportDateEl.value  = parsedData.report_date  || '';
        if (descriptionEl) descriptionEl.value = parsedData.description  || '';
        if (sprintEl && (parsedData.sprint || parsedData.sprint_name)) {
            sprintEl.value = parsedData.sprint || parsedData.sprint_name;
        }
        if (cycleEl && (parsedData.cycle || parsedData.cycle_name)) {
            cycleEl.value = parsedData.cycle || parsedData.cycle_name;
        }

        // Auto-select project if suggested_project_name matches any option
        if (projectEl && (parsedData.suggested_project_name || parsedData.report_name)) {
            const searchTerms = [
                parsedData.suggested_project_name,
                parsedData.report_name
            ].filter(Boolean).map(t => t.toLowerCase());

            for (const option of projectEl.options) {
                if (!option.value) continue;
                const optText = option.textContent.toLowerCase();
                const matched = searchTerms.some(term => optText.includes(term) || term.includes(optText.split(' ')[0]));
                if (matched) {
                    projectEl.value = option.value;
                    projectEl.dispatchEvent(new Event('change'));
                    break;
                }
            }
        }

        // Clear existing test cases container directly to avoid "minimal 1 test case" validation alert
        const container = document.getElementById('testCasesContainer');
        if (container) {
            container.innerHTML = '';
        }

        // Add each AI-generated test case
        const cases = parsedData.test_cases || [];
        const addFn = window.addTestCase || (typeof addTestCase === 'function' ? addTestCase : null);

        if (addFn) {
            if (cases.length > 0) {
                cases.forEach(tc => {
                    const rawStatus = String(tc.status || 'Pass').trim();
                    let normalizedStatus = 'Pass';
                    if (['pass', 'passed'].includes(rawStatus.toLowerCase())) normalizedStatus = 'Pass';
                    else if (['fail', 'failed'].includes(rawStatus.toLowerCase())) normalizedStatus = 'Fail';
                    else if (['blocked'].includes(rawStatus.toLowerCase())) normalizedStatus = 'Blocked';
                    else if (['not run', 'notrun', 'untested'].includes(rawStatus.toLowerCase())) normalizedStatus = 'Not Run';
                    else if (['n/a', 'na'].includes(rawStatus.toLowerCase())) normalizedStatus = 'N/A';

                    addFn({
                        fields: {
                            testCaseId:       tc.test_case_id     || tc.testCaseId       || '',
                            testTitle:        tc.testTitle        || tc.test_title       || '',
                            priority:         tc.priority         || 'Medium',
                            precondition:     tc.precondition     || '',
                            status:           normalizedStatus,
                            buckleupTicket:   tc.buckleup_ticket  || tc.buckleupTicket   || '',
                            bugTicket:        tc.bug_ticket       || tc.bugTicket        || '',
                            actualResult:     tc.actual_result    || tc.actualResult     || '',
                            notesQA:          tc.notes_qa         || tc.notesQA          || '',
                            notesProgrammer:  tc.notes_programmer || tc.notesProgrammer || '',
                            evidenceUrl:      tc.evidence_url     || tc.evidenceUrl     || '',
                            // Dynamic fields mapping
                            caseType:           tc.caseType           || tc.case_type           || 'Positive',
                            feature:            tc.feature            || '',
                            refRequirement:     tc.refRequirement     || tc.ref_requirement     || '',
                            moduleFeature:      tc.moduleFeature      || tc.module_feature      || '',
                            testData:           tc.testData           || tc.test_data           || '',
                            integrationPoint:   tc.integrationPoint   || tc.integration_point   || '',
                            dependencies:       tc.dependencies       || '',
                            relatedFeature:     tc.relatedFeature     || tc.related_feature     || '',
                            reasonLinked:       tc.reasonLinked       || tc.reason_linked       || '',
                            testTypePerf:       tc.testTypePerf       || tc.test_type_perf      || 'Load Test',
                            testParams:         tc.testParams         || tc.test_params         || '',
                            testTypeRel:        tc.testTypeRel        || tc.test_type_rel       || 'Failure Rate',
                            durationFailure:    tc.durationFailure    || tc.duration_failure    || '',
                            testTypeStress:     tc.testTypeStress     || tc.test_type_stress    || 'Load Stress',
                            stressParams:       tc.stressParams       || tc.stress_params       || '',
                            moduleFeatureNeg:   tc.moduleFeatureNeg   || tc.module_feature_neg   || '',
                            testDataNeg:        tc.testDataNeg        || tc.test_data_neg        || '',
                            validationType:     tc.validationType     || tc.validation_type     || 'Input Validation',
                            validationCriteria: tc.validationCriteria || tc.validation_criteria || '',
                            componentSubsystem: tc.componentSubsystem || tc.component_subsystem || '',
                            parameterMeasured:  tc.parameterMeasured  || tc.parameter_measured  || '',
                            unit:               tc.unit               || '',
                            passLimitSpec:      tc.passLimitSpec      || tc.pass_limit_spec     || '',
                            specSource:         tc.specSource         || tc.spec_source         || '',
                            measuredValue:      tc.measuredValue      || tc.measured_value      || '',
                            deviceTested:       tc.deviceTested       || tc.device_tested       || '',
                            environmentSpec:    tc.environmentSpec    || tc.environment_spec    || '',
                        },
                        steps: (tc.steps || []).map(s => ({
                            step:     s.step     || '',
                            expected: s.expected || '',
                        })),
                    });
                });
            } else {
                addFn();
            }
        }

        const updateNums = window.updateTestNumbers || (typeof updateTestNumbers === 'function' ? updateTestNumbers : null);
        if (updateNums) updateNums();

        const updateCount = window.updateTestCaseCount || (typeof updateTestCaseCount === 'function' ? updateTestCaseCount : null);
        if (updateCount) updateCount();

        closeModal();

        // Smooth scroll to test cases
        const targetScroll = document.getElementById('testCasesContainer') || document.querySelector('.test-case-row');
        if (targetScroll) {
            targetScroll.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        // Show a quick toast
        showToast(`✨ ${cases.length} test case berhasil diisi dari AI!`);
    });

    // ── Render Preview ────────────────────────────────────
    function renderPreview(data) {
        const typeLabel = {
            functional: 'Functional', integration: 'Integration',
            regression: 'Regression', performance: 'Performance',
            reliability: 'Reliability', stress: 'Stress', negative: 'Negative',
            validation: 'Validation', hardware: 'Hardware',
        };

        previewMeta.innerHTML = `
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                <div><span style="font-size:11px;color:#94a3b8;font-weight:600;text-transform:uppercase;">Nama Report</span>
                     <div style="font-size:13.5px;font-weight:700;color:#1e293b;margin-top:2px;">${esc(data.report_name)}</div></div>
                <div><span style="font-size:11px;color:#94a3b8;font-weight:600;text-transform:uppercase;">Tipe Testing</span>
                     <div style="font-size:13.5px;font-weight:700;color:#6366f1;margin-top:2px;">${typeLabel[data.test_type] || data.test_type}</div></div>
                <div><span style="font-size:11px;color:#94a3b8;font-weight:600;text-transform:uppercase;">Tanggal</span>
                     <div style="font-size:13.5px;font-weight:600;color:#334155;margin-top:2px;">${esc(data.report_date)}</div></div>
                <div><span style="font-size:11px;color:#94a3b8;font-weight:600;text-transform:uppercase;">Test Cases</span>
                     <div style="font-size:13.5px;font-weight:700;color:#10b981;margin-top:2px;">${data.test_cases?.length || 0} test case</div></div>
                ${data.suggested_project_name ? `
                <div style="grid-column: span 2;"><span style="font-size:11px;color:#94a3b8;font-weight:600;text-transform:uppercase;">Project Terdeteksi</span>
                     <div style="font-size:13px;font-weight:700;color:#0284c7;margin-top:2px;">🏷️ ${esc(data.suggested_project_name)} (Akan dicocokkan ke dropdown project)</div></div>` : ''}
            </div>
            ${data.description ? `<div style="margin-top:8px;padding-top:8px;border-top:1px solid #f1f5f9;font-size:13px;color:#64748b;">${esc(data.description)}</div>` : ''}
        `;

        previewCase.innerHTML = '';
        (data.test_cases || []).forEach((tc, i) => {
            const statusColor = { 
                Pass: '#10b981', Fail: '#ef4444', Blocked: '#f59e0b', 'Not Run': '#64748b', 'N/A': '#94a3b8',
                PASS: '#10b981', FAILED: '#ef4444', BLOCKED: '#f59e0b' 
            };
            const card = document.createElement('div');
            card.style.cssText = 'background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px 14px;';
            
            let badgesHtml = '';
            const tcId = tc.test_case_id || tc.testCaseId;
            if (tcId) badgesHtml += `<span style="display:inline-block;padding:2px 7px;background:#dbeafe;color:#1e40af;font-size:11px;font-weight:700;border-radius:6px;margin-right:6px;">🏷️ ${esc(tcId)}</span>`;
            if (tc.priority) badgesHtml += `<span style="display:inline-block;padding:2px 7px;background:#f1f5f9;color:#475569;font-size:11px;font-weight:600;border-radius:6px;margin-right:6px;">⚡ ${esc(tc.priority)}</span>`;
            const bTicket = tc.buckleup_ticket || tc.buckleupTicket;
            if (bTicket) badgesHtml += `<span style="display:inline-block;padding:2px 7px;background:#fef3c7;color:#92400e;font-size:11px;font-weight:600;border-radius:6px;margin-right:6px;">🎫 ${esc(bTicket)}</span>`;
            const bugTicket = tc.bug_ticket || tc.bugTicket;
            if (bugTicket) badgesHtml += `<span style="display:inline-block;padding:2px 7px;background:#fee2e2;color:#991b1b;font-size:11px;font-weight:600;border-radius:6px;margin-right:6px;">🐛 ${esc(bugTicket)}</span>`;

            let dynamicInfo = '';
            if (tc.integrationPoint) dynamicInfo += `<div style="font-size:11.5px;color:#4f46e5;font-weight:600;margin-top:4px;">🔌 ${esc(tc.integrationPoint)}</div>`;
            if (tc.moduleFeature || tc.moduleFeatureNeg) dynamicInfo += `<div style="font-size:11.5px;color:#4f46e5;font-weight:600;margin-top:4px;">📦 ${esc(tc.moduleFeature || tc.moduleFeatureNeg)}</div>`;
            if (tc.testParams || tc.stressParams) dynamicInfo += `<div style="font-size:11.5px;color:#4f46e5;font-weight:600;margin-top:4px;">⚡ ${esc(tc.testParams || tc.stressParams)}</div>`;
            if (tc.relatedFeature) dynamicInfo += `<div style="font-size:11.5px;color:#4f46e5;font-weight:600;margin-top:4px;">🔗 ${esc(tc.relatedFeature)}</div>`;
            if (tc.validationType) dynamicInfo += `<div style="font-size:11.5px;color:#4f46e5;font-weight:600;margin-top:4px;">🛡️ ${esc(tc.validationType)}</div>`;
            if (tc.validationCriteria) dynamicInfo += `<div style="font-size:11.5px;color:#64748b;margin-top:2px;">📜 ${esc(tc.validationCriteria)}</div>`;
            if (tc.deviceTested) dynamicInfo += `<div style="font-size:11.5px;color:#4f46e5;font-weight:600;margin-top:4px;">📻 ${esc(tc.deviceTested)}</div>`;
            if (tc.environmentSpec) dynamicInfo += `<div style="font-size:11.5px;color:#64748b;margin-top:2px;">🌐 ${esc(tc.environmentSpec)}</div>`;

            card.innerHTML = `
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;flex-wrap:wrap;gap:6px;">
                    <div style="font-weight:700;font-size:13.5px;color:#1e293b;">
                        <span style="color:#6366f1;font-size:12px;margin-right:6px;">#${i+1}</span>${esc(tc.testTitle || tc.test_title || '(No title)')}
                    </div>
                    <span style="background:${statusColor[tc.status] || '#64748b'}20;color:${statusColor[tc.status] || '#64748b'};
                                 font-size:11px;font-weight:700;padding:2px 10px;border-radius:8px;">${tc.status}</span>
                </div>
                ${badgesHtml ? `<div style="margin-bottom:6px;">${badgesHtml}</div>` : ''}
                ${dynamicInfo ? `<div style="margin-bottom:6px;">${dynamicInfo}</div>` : ''}
                ${tc.precondition ? `<div style="font-size:12px;color:#64748b;margin-bottom:6px;">📋 ${esc(tc.precondition)}</div>` : ''}
                <div style="font-size:12px;color:#475569;">
                    ${(tc.steps || []).map((s,j) => `
                        <div style="display:flex;gap:8px;margin-top:4px;">
                            <span style="color:#94a3b8;flex-shrink:0;font-weight:600;">${j+1}.</span>
                            <div><div>${esc(s.step)}</div>
                                 <div style="color:#10b981;font-size:11.5px;">→ ${esc(s.expected)}</div></div>
                        </div>`).join('')}
                </div>
                ${tc.actual_result ? `<div style="margin-top:6px;font-size:12px;color:#64748b;border-top:1px solid #f1f5f9;padding-top:6px;"><strong>Aktual:</strong> ${esc(tc.actual_result)}</div>` : ''}
                ${tc.notes_qa ? `<div style="margin-top:4px;font-size:11.5px;color:#b45309;">💬 <strong>Notes QA:</strong> ${esc(tc.notes_qa)}</div>` : ''}
                ${tc.notes_programmer ? `<div style="margin-top:4px;font-size:11.5px;color:#0369a1;">💻 <strong>Notes Dev:</strong> ${esc(tc.notes_programmer)}</div>` : ''}
            `;
            previewCase.appendChild(card);
        });
    }

    // ── Helpers ───────────────────────────────────────────
    let loadingInterval = null;

    function setLoading(on) {
        generateBtn.disabled = on;
        const btnIcon = document.getElementById('aiBtnIcon');
        const btnText = document.getElementById('aiBtnText');

        if (on) {
            if (btnIcon) btnIcon.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i>';
            if (btnText) btnText.textContent = 'Sedang Menganalisis...';
            generateBtn.style.opacity = '0.85';
            generateBtn.style.background = 'linear-gradient(135deg, #4f46e5, #7c3aed)';

            if (loadingCard) {
                loadingCard.style.display = 'block';
                loadingCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
            if (successCard) successCard.style.display = 'none';
            if (previewSec)  previewSec.style.display  = 'none';

            const loadingMsgs = [
                'Membaca catatan mentah & mengekstrak konteks...',
                'Mendeteksi modul, skenario & test cases...',
                'Menyusun langkah pengujian & expected result...',
                'Menyelaraskan struktur field dengan formulir...'
            ];
            let msgIdx = 0;
            const subTitleEl = document.getElementById('aiLoadingSubtitle');
            if (subTitleEl) subTitleEl.textContent = loadingMsgs[0];

            clearInterval(loadingInterval);
            loadingInterval = setInterval(() => {
                msgIdx = (msgIdx + 1) % loadingMsgs.length;
                if (subTitleEl) {
                    subTitleEl.style.opacity = '0';
                    setTimeout(() => {
                        subTitleEl.textContent = loadingMsgs[msgIdx];
                        subTitleEl.style.opacity = '1';
                    }, 200);
                }
            }, 2200);
        } else {
            clearInterval(loadingInterval);
            if (loadingCard) loadingCard.style.display = 'none';
            generateBtn.disabled = false;
            generateBtn.style.opacity = '1';
            generateBtn.style.background = 'linear-gradient(135deg, #6366f1, #8b5cf6)';
            if (btnIcon) btnIcon.textContent = '✨';
            if (btnText) btnText.textContent = 'Generate dengan AI';
        }
    }

    function showSuccessCheckmark(onComplete) {
        if (successCard) {
            successCard.style.display = 'block';
            successCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        const btnIcon = document.getElementById('aiBtnIcon');
        const btnText = document.getElementById('aiBtnText');
        if (btnIcon) btnIcon.innerHTML = '<i class="fas fa-check"></i>';
        if (btnText) btnText.textContent = 'Selesai!';
        generateBtn.style.background = 'linear-gradient(135deg, #10b981, #059669)';

        setTimeout(() => {
            if (successCard) {
                successCard.style.display = 'none';
            }
            if (btnIcon) btnIcon.textContent = '✨';
            if (btnText) btnText.textContent = 'Generate Ulang dengan AI';
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
