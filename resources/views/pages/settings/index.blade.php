@extends('layouts.app')

@section('title', config('app.name', 'QA Portal') . ' - Settings')

@section('page_title', 'Settings & System Setup')
@section('page_subtitle', 'Kelola profil akun Anda dan konfigurasi AI API Keys')

@section('content')
    <!-- Success Alert -->
    @if (session('success'))
        <div style="margin-bottom: 20px; background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; padding: 14px 20px; border-radius: 12px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 2px 8px rgba(22,163,74,0.08);">
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-check-circle" style="font-size: 18px;"></i>
                <span style="font-weight: 600; font-size: 14px;">{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" style="background: none; border: none; color: #16a34a; cursor: pointer; font-size: 18px;">&times;</button>
        </div>
    @endif

    <!-- Validation Errors -->
    @if ($errors->any())
        <div style="margin-bottom: 20px; background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; padding: 14px 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(220,38,38,0.08);">
            <div style="font-weight: 700; margin-bottom: 6px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Terdapat kesalahan saat menyimpan:</span>
            </div>
            <ul style="margin: 0; padding-left: 24px; font-size: 13.5px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Settings Wrapper Card -->
    <div class="content-card" style="border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05); background: white;">
        
        <!-- Tab Navigation Header -->
        @php
            $activeTab = session('tab', request('tab', 'profile'));
        @endphp
        <div style="display: flex; border-bottom: 1px solid #e2e8f0; background: #f8fafc; padding: 6px 12px 0 12px; gap: 8px; flex-wrap: wrap;">
            <button type="button" class="tab-btn {{ $activeTab === 'profile' ? 'active' : '' }}" onclick="switchTab('profile')">
                <i class="fas fa-user-circle" style="font-size: 16px;"></i>
                <span>Edit Profile</span>
            </button>
            <button type="button" class="tab-btn {{ $activeTab === 'apikeys' ? 'active' : '' }}" onclick="switchTab('apikeys')">
                <i class="fas fa-robot" style="font-size: 16px;"></i>
                <span>Setup AI & API Keys</span>
                @if(($user->gemini_api_key || $user->openrouter_api_key || $user->openai_api_key || $user->omniroute_api_key))
                    <span style="background: #dcfce7; color: #15803d; font-size: 10.5px; font-weight: 700; padding: 2px 7px; border-radius: 10px; margin-left: 4px;">Configured</span>
                @endif
            </button>
        </div>

        <div style="padding: 28px;">

            <!-- ─── TAB 1: EDIT PROFILE ─────────────────────────────────────────── -->
            <div id="tab-profile" class="tab-content" style="display: {{ $activeTab === 'profile' ? 'block' : 'none' }};">
                <form action="{{ route('settings.profile.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div style="display: grid; grid-template-columns: 240px 1fr; gap: 32px; align-items: start;">
                        
                        <!-- Avatar / Summary Card -->
                        <div style="background: linear-gradient(135deg, #1e293b, #0f172a); color: white; padding: 24px; border-radius: 16px; text-align: center; box-shadow: 0 10px 25px rgba(15,23,42,0.15);">
                            @php
                                $name = $user->name ?? 'QA User';
                                $words = explode(' ', trim($name));
                                $initials = count($words) >= 2 
                                    ? strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1))
                                    : strtoupper(substr($name, 0, 2));
                            @endphp
                            <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: 800; margin: 0 auto 16px auto; box-shadow: 0 6px 16px rgba(102,126,234,0.4); border: 3px solid rgba(255,255,255,0.2);">
                                {{ $initials }}
                            </div>
                            <h4 style="font-weight: 700; font-size: 16px; margin: 0; color: white;">{{ $user->name }}</h4>
                            <p style="font-size: 12.5px; color: #94a3b8; margin: 4px 0 12px 0;">{{ $user->email }}</p>

                            <div style="display: inline-flex; align-items: center; gap: 6px; background: rgba(34,197,94,0.15); border: 1px solid rgba(34,197,94,0.3); color: #4ade80; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                <i class="fas fa-circle" style="font-size: 8px;"></i>
                                {{ $user->status ?? 'Active Account' }}
                            </div>
                        </div>

                        <!-- Form Inputs -->
                        <div>
                            <h3 style="font-size: 17px; font-weight: 700; color: #1e293b; margin: 0 0 20px 0; display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-id-card text-blue-600"></i> Informasi Profil Akun
                            </h3>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                                <div class="form-group">
                                    <label class="form-label" style="font-weight: 600; color: #475569; font-size: 13px; display: block; margin-bottom: 6px;">
                                        Nama Lengkap <span style="color:#ef4444;">*</span>
                                    </label>
                                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                                           style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 14px; outline: none; transition: border-color 0.2s;" />
                                </div>

                                <div class="form-group">
                                    <label class="form-label" style="font-weight: 600; color: #475569; font-size: 13px; display: block; margin-bottom: 6px;">
                                        Alamat Email <span style="color:#ef4444;">*</span>
                                    </label>
                                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                                           style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 14px; outline: none; transition: border-color 0.2s;" />
                                </div>
                            </div>

                            <!-- Password Section Divider -->
                            <div style="border-top: 1px solid #f1f5f9; padding-top: 20px; margin-top: 24px;">
                                <h4 style="font-size: 15px; font-weight: 700; color: #334155; margin: 0 0 4px 0;">Ubah Password</h4>
                                <p style="font-size: 12.5px; color: #94a3b8; margin-0 0 16px 0;">Kosongkan jika Anda tidak ingin mengubah password akun.</p>

                                <div style="display: grid; grid-template-columns: 1fr; gap: 14px; max-width: 500px;">
                                    <div>
                                        <label style="font-weight: 600; color: #475569; font-size: 13px; display: block; margin-bottom: 6px;">Password Saat Ini</label>
                                        <div style="position: relative;">
                                            <input type="password" id="current_password" name="current_password" placeholder="••••••••"
                                                   style="width: 100%; padding: 10px 40px 10px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 14px; outline: none;" />
                                            <i class="fas fa-eye toggle-pass" onclick="togglePasswordVisibility('current_password', this)"
                                               style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; cursor: pointer;"></i>
                                        </div>
                                    </div>

                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                                        <div>
                                            <label style="font-weight: 600; color: #475569; font-size: 13px; display: block; margin-bottom: 6px;">Password Baru</label>
                                            <div style="position: relative;">
                                                <input type="password" id="new_password" name="new_password" placeholder="Minimal 6 karakter"
                                                       style="width: 100%; padding: 10px 40px 10px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 14px; outline: none;" />
                                                <i class="fas fa-eye toggle-pass" onclick="togglePasswordVisibility('new_password', this)"
                                                   style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; cursor: pointer;"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <label style="font-weight: 600; color: #475569; font-size: 13px; display: block; margin-bottom: 6px;">Konfirmasi Password Baru</label>
                                            <div style="position: relative;">
                                                <input type="password" id="new_password_confirmation" name="new_password_confirmation" placeholder="Ulangi password baru"
                                                       style="width: 100%; padding: 10px 40px 10px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 14px; outline: none;" />
                                                <i class="fas fa-eye toggle-pass" onclick="togglePasswordVisibility('new_password_confirmation', this)"
                                                   style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; cursor: pointer;"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div style="margin-top: 28px;">
                                <button type="submit" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; padding: 11px 24px; border-radius: 10px; font-size: 14px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 14px rgba(102,126,234,0.35); display: inline-flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-save"></i> Simpan Perubahan Profil
                                </button>
                            </div>
                        </div>

                    </div>
                </form>
            </div>

            <!-- ─── TAB 2: SETUP AI & API KEYS ──────────────────────────────────── -->
            <div id="tab-apikeys" class="tab-content" style="display: {{ $activeTab === 'apikeys' ? 'block' : 'none' }};">
                <form action="{{ route('settings.api-keys.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div style="max-width: 800px;">
                        
                        <div style="margin-bottom: 24px;">
                            <h3 style="font-size: 17px; font-weight: 700; color: #1e293b; margin: 0 0 4px 0; display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-key text-purple-600"></i> Setup AI Provider & API Keys
                            </h3>
                            <p style="font-size: 13px; color: #64748b; margin: 0;">
                                Simpan API Key pribadi Anda untuk fitur **✨ AI Auto-Fill Mode**. Key tersimpan aman di akun Anda dan tidak perlu diinput berulang kali.
                            </p>
                        </div>

                        <!-- Default AI Provider Card Selection -->
                        <div style="margin-bottom: 28px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px;">
                            <label style="font-size: 13px; font-weight: 700; color: #334155; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 12px;">
                                Default AI Provider Utamamu
                            </label>
                            
                            @php
                                $currentDefault = old('default_ai_provider', $user->getResolvedDefaultAiProvider());
                            @endphp

                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px;">
                                <!-- Gemini Radio Card -->
                                <label class="provider-radio-card {{ $currentDefault === 'gemini' ? 'selected' : '' }}" onclick="selectDefaultProvider('gemini')">
                                    <input type="radio" name="default_ai_provider" value="gemini" {{ $currentDefault === 'gemini' ? 'checked' : '' }} style="display:none;">
                                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                                        <span style="font-size: 22px;">🤖</span>
                                        @if($aiStatus['gemini']['configured'])
                                            <span class="badge-status badge-success" style="font-size: 10px;">Ready</span>
                                        @else
                                            <span class="badge-status badge-warning" style="font-size: 10px;">No Key</span>
                                        @endif
                                    </div>
                                    <div style="font-weight: 700; font-size: 13.5px; color: #1e293b;">Google Gemini</div>
                                    <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;">gemini-1.5-flash</div>
                                </label>

                                <!-- OpenRouter Radio Card -->
                                <label class="provider-radio-card {{ $currentDefault === 'openrouter' ? 'selected' : '' }}" onclick="selectDefaultProvider('openrouter')">
                                    <input type="radio" name="default_ai_provider" value="openrouter" {{ $currentDefault === 'openrouter' ? 'checked' : '' }} style="display:none;">
                                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                                        <span style="font-size: 22px;">🌐</span>
                                        @if($aiStatus['openrouter']['configured'])
                                            <span class="badge-status badge-success" style="font-size: 10px;">Ready</span>
                                        @else
                                            <span class="badge-status badge-warning" style="font-size: 10px;">No Key</span>
                                        @endif
                                    </div>
                                    <div style="font-weight: 700; font-size: 13.5px; color: #1e293b;">OpenRouter</div>
                                    <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;">llama-3.1-8b-free</div>
                                </label>

                                <!-- Ollama Radio Card -->
                                <label class="provider-radio-card {{ $currentDefault === 'ollama' ? 'selected' : '' }}" onclick="selectDefaultProvider('ollama')">
                                    <input type="radio" name="default_ai_provider" value="ollama" {{ $currentDefault === 'ollama' ? 'checked' : '' }} style="display:none;">
                                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                                        <span style="font-size: 22px;">💻</span>
                                        <span class="badge-status badge-info" style="font-size: 10px;">Local</span>
                                    </div>
                                    <div style="font-weight: 700; font-size: 13.5px; color: #1e293b;">Ollama Lokal</div>
                                    <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;">100% Offline</div>
                                </label>

                                <!-- OmniRoute Radio Card -->
                                <label class="provider-radio-card {{ $currentDefault === 'omniroute' ? 'selected' : '' }}" onclick="selectDefaultProvider('omniroute')">
                                    <input type="radio" name="default_ai_provider" value="omniroute" {{ $currentDefault === 'omniroute' ? 'checked' : '' }} style="display:none;">
                                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                                        <span style="font-size: 22px;">🔀</span>
                                        <span class="badge-status badge-info" style="font-size: 10px;">Router</span>
                                    </div>
                                    <div style="font-weight: 700; font-size: 13.5px; color: #1e293b;">OmniRoute Server</div>
                                    <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;">auto/best-coding</div>
                                </label>

                                <!-- OpenAI / Celeris Radio Card -->
                                <label class="provider-radio-card {{ $currentDefault === 'openai' ? 'selected' : '' }}" onclick="selectDefaultProvider('openai')">
                                    <input type="radio" name="default_ai_provider" value="openai" {{ $currentDefault === 'openai' ? 'checked' : '' }} style="display:none;">
                                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                                        <span style="font-size: 22px;">⚡</span>
                                        @if($aiStatus['openai']['configured'])
                                            <span class="badge-status badge-success" style="font-size: 10px;">Ready</span>
                                        @else
                                            <span class="badge-status badge-warning" style="font-size: 10px;">No Key</span>
                                        @endif
                                    </div>
                                    <div style="font-weight: 700; font-size: 13.5px; color: #1e293b;">OpenAI / Celeris</div>
                                    <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;">{{ $user->getResolvedOpenAiModel() }}</div>
                                </label>
                            </div>
                        </div>

                        <!-- Provider Key Inputs -->
                        <div style="display: flex; flex-direction: column; gap: 24px;">
                            
                            <!-- 1. Google Gemini -->
                            <div style="border: 1px solid #e2e8f0; border-radius: 14px; padding: 20px; background: white;">
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="width: 36px; height: 36px; background: #eef2ff; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px;">🤖</div>
                                        <div>
                                            <h4 style="font-size: 15px; font-weight: 700; margin: 0; color: #1e293b;">Google Gemini API Key</h4>
                                            <p style="font-size: 12px; color: #64748b; margin: 0;">Gratis & rekomendasi terbaik untuk analisis akurat</p>
                                        </div>
                                    </div>
                                    @if($aiStatus['gemini']['configured'])
                                        <span style="background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 700;">
                                            <i class="fas fa-check-circle mr-1"></i> Configured
                                        </span>
                                    @else
                                        <span style="background: #fef3c7; color: #92400e; padding: 4px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 600;">
                                            Belum diisi
                                        </span>
                                    @endif
                                </div>

                                <div style="position: relative; margin-bottom: 8px;">
                                    <input type="password" id="gemini_api_key" name="gemini_api_key"
                                           value="{{ old('gemini_api_key', $user->gemini_api_key) }}"
                                           placeholder="Masukkan Gemini API Key (contoh: AIzaSy...)"
                                           style="width: 100%; padding: 11px 40px 11px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none; font-family: monospace;" />
                                    <i class="fas fa-eye toggle-pass" onclick="togglePasswordVisibility('gemini_api_key', this)"
                                       style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; cursor: pointer;"></i>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <a href="https://aistudio.google.com/app/apikey" target="_blank" style="color: #6366f1; font-size: 12px; text-decoration: none; font-weight: 600;">
                                        <i class="fas fa-external-link-alt mr-1"></i> Dapatkan Google AI Studio API Key secara gratis →
                                    </a>
                                    @if($user->gemini_api_key)
                                        <span style="font-size: 11.5px; color: #64748b; font-family: monospace;">Tersimpan: {{ $aiStatus['gemini']['key_masked'] }}</span>
                                    @endif
                                </div>
                            </div>

                            <!-- 2. OpenRouter -->
                            <div style="border: 1px solid #e2e8f0; border-radius: 14px; padding: 20px; background: white;">
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="width: 36px; height: 36px; background: #f8fafc; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px;">🌐</div>
                                        <div>
                                            <h4 style="font-size: 15px; font-weight: 700; margin: 0; color: #1e293b;">OpenRouter API Key</h4>
                                            <p style="font-size: 12px; color: #64748b; margin: 0;">Multi-model AI (Llama 3, Claude, Mistral, dll)</p>
                                        </div>
                                    </div>
                                    @if($aiStatus['openrouter']['configured'])
                                        <span style="background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 700;">
                                            <i class="fas fa-check-circle mr-1"></i> Configured
                                        </span>
                                    @else
                                        <span style="background: #f8fafc; color: #64748b; border: 1px solid #cbd5e1; padding: 4px 10px; border-radius: 20px; font-size: 11.5px;">
                                            Opsional
                                        </span>
                                    @endif
                                </div>

                                <div style="position: relative; margin-bottom: 8px;">
                                    <input type="password" id="openrouter_api_key" name="openrouter_api_key"
                                           value="{{ old('openrouter_api_key', $user->openrouter_api_key) }}"
                                           placeholder="Masukkan OpenRouter API Key (contoh: sk-or-v1-...)"
                                           style="width: 100%; padding: 11px 40px 11px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none; font-family: monospace;" />
                                    <i class="fas fa-eye toggle-pass" onclick="togglePasswordVisibility('openrouter_api_key', this)"
                                       style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; cursor: pointer;"></i>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                                    <a href="https://openrouter.ai/keys" target="_blank" style="color: #6366f1; font-size: 12px; text-decoration: none; font-weight: 600;">
                                        <i class="fas fa-external-link-alt mr-1"></i> Buat API Key di OpenRouter →
                                    </a>
                                    @if($user->openrouter_api_key)
                                        <span style="font-size: 11.5px; color: #64748b; font-family: monospace;">Tersimpan: {{ $aiStatus['openrouter']['key_masked'] }}</span>
                                    @endif
                                </div>

                                <div style="margin-top: 12px; border-top: 1px solid #f1f5f9; padding-top: 12px;">
                                    <label style="font-weight: 600; color: #475569; font-size: 12.5px; display: block; margin-bottom: 6px;">OpenRouter Default Model Name</label>
                                    <input type="text" name="openrouter_model" value="{{ old('openrouter_model', $user->getResolvedOpenRouterModel()) }}"
                                           placeholder="meta-llama/llama-3.1-8b-instruct:free"
                                           style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none;" />
                                    <span style="font-size: 11.5px; color: #94a3b8; display: block; margin-top: 4px;">
                                        Model rekomendasi: <code style="background:#f1f5f9; padding:2px 6px; border-radius:4px;">google/gemini-2.0-flash-exp:free</code>, <code style="background:#f1f5f9; padding:2px 6px; border-radius:4px;">meta-llama/llama-3.1-8b-instruct:free</code>, <code style="background:#f1f5f9; padding:2px 6px; border-radius:4px;">deepseek/deepseek-r1:free</code>
                                    </span>
                                </div>
                            </div>

                            <!-- 3. Ollama (Local AI) -->
                            <div style="border: 1px solid #e2e8f0; border-radius: 14px; padding: 20px; background: white;">
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="width: 36px; height: 36px; background: #f1f5f9; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px;">💻</div>
                                        <div>
                                            <h4 style="font-size: 15px; font-weight: 700; margin: 0; color: #1e293b;">Ollama Server Config (Lokal/Offline)</h4>
                                            <p style="font-size: 12px; color: #64748b; margin: 0;">Tidak membutuhkan internet / API key. Berjalan di komputer lokal.</p>
                                        </div>
                                    </div>
                                    <span style="background: #e0f2fe; color: #0369a1; padding: 4px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 600;">
                                        Offline Mode
                                    </span>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                                    <div>
                                        <label style="font-weight: 600; color: #475569; font-size: 12.5px; display: block; margin-bottom: 6px;">Ollama Base URL</label>
                                        <input type="text" name="ollama_base_url" value="{{ old('ollama_base_url', $user->getResolvedOllamaBaseUrl()) }}"
                                               placeholder="http://localhost:11434"
                                               style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none;" />
                                    </div>
                                    <div>
                                        <label style="font-weight: 600; color: #475569; font-size: 12.5px; display: block; margin-bottom: 6px;">Model Name</label>
                                        <input type="text" name="ollama_model" value="{{ old('ollama_model', $user->getResolvedOllamaModel()) }}"
                                               placeholder="llama3.2"
                                               style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none;" />
                                    </div>
                                </div>
                                <p style="font-size: 11.5px; color: #94a3b8; margin: 8px 0 0 0;">
                                    Pastikan service Ollama berjalan secara lokal dengan perintah: <code style="background:#f1f5f9; padding:2px 6px; border-radius:4px;">ollama run llama3.2</code>
                                </p>
                            </div>

                            <!-- 4. OmniRoute Server -->
                            <div style="border: 1px solid #e2e8f0; border-radius: 14px; padding: 20px; background: white;">
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="width: 36px; height: 36px; background: #fae8ff; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px;">🔀</div>
                                        <div>
                                            <h4 style="font-size: 15px; font-weight: 700; margin: 0; color: #1e293b;">OmniRoute Server Config (http://202.155.143.157:20128)</h4>
                                            <p style="font-size: 12px; color: #64748b; margin: 0;">Multi-model AI router server (mendukung auto/best-coding, auto/best-reasoning, dll)</p>
                                        </div>
                                    </div>
                                    <span style="background: #f5d0fe; color: #86198f; padding: 4px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 600;">
                                        Active Server
                                    </span>
                                </div>

                                <div style="margin-bottom: 14px; position: relative;">
                                    <label style="font-weight: 600; color: #475569; font-size: 12.5px; display: block; margin-bottom: 6px;">OmniRoute API Key (Opsional)</label>
                                    <input type="password" id="omniroute_api_key" name="omniroute_api_key"
                                           value="{{ old('omniroute_api_key', $user->omniroute_api_key) }}"
                                           placeholder="Kosongkan jika server tidak memerlukan API key"
                                           style="width: 100%; padding: 10px 40px 10px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none; font-family: monospace;" />
                                    <i class="fas fa-eye toggle-pass" onclick="togglePasswordVisibility('omniroute_api_key', this)"
                                       style="position: absolute; right: 14px; top: 34px; color: #94a3b8; cursor: pointer;"></i>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                                    <div>
                                        <label style="font-weight: 600; color: #475569; font-size: 12.5px; display: block; margin-bottom: 6px;">Base URL</label>
                                        <input type="text" name="omniroute_base_url" value="{{ old('omniroute_base_url', $user->getResolvedOmnirouteBaseUrl()) }}"
                                               placeholder="http://202.155.143.157:20128/v1"
                                               style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none;" />
                                    </div>
                                    <div>
                                        <label style="font-weight: 600; color: #475569; font-size: 12.5px; display: block; margin-bottom: 6px;">Model Name</label>
                                        <input type="text" name="omniroute_model" value="{{ old('omniroute_model', $user->getResolvedOmnirouteModel()) }}"
                                               placeholder="auto/best-coding"
                                               style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none;" />
                                    </div>
                                </div>
                            </div>

                            <!-- 5. OpenAI / Celeris / Custom Endpoint -->
                            <div style="border: 1px solid #e2e8f0; border-radius: 14px; padding: 20px; background: white;">
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="width: 36px; height: 36px; background: #ecfdf5; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px;">⚡</div>
                                        <div>
                                            <h4 style="font-size: 15px; font-weight: 700; margin: 0; color: #1e293b;">OpenAI / Celeris / Custom API Config</h4>
                                            <p style="font-size: 12px; color: #64748b; margin: 0;">Mendukung OpenAI resmi, proxy Celeris (ck_...), vLLM, dan endpoint OpenAI-compatible lainnya</p>
                                        </div>
                                    </div>
                                    @if($aiStatus['openai']['configured'])
                                        <span style="background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 700;">
                                            <i class="fas fa-check-circle mr-1"></i> Configured
                                        </span>
                                    @else
                                        <span style="background: #f8fafc; color: #64748b; border: 1px solid #cbd5e1; padding: 4px 10px; border-radius: 20px; font-size: 11.5px;">
                                            Opsional
                                        </span>
                                    @endif
                                </div>

                                <div style="margin-bottom: 14px; position: relative;">
                                    <label style="font-weight: 600; color: #475569; font-size: 12.5px; display: block; margin-bottom: 6px;">
                                        API Key (e.g. ck_... atau sk-...)
                                    </label>
                                    <input type="password" id="openai_api_key" name="openai_api_key"
                                           value="{{ old('openai_api_key', $user->openai_api_key) }}"
                                           placeholder="Masukkan API Key (contoh: ck_REPLACE_WITH_YOUR_KEY atau sk-...)"
                                           style="width: 100%; padding: 10px 40px 10px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none; font-family: monospace;" />
                                    <i class="fas fa-eye toggle-pass" onclick="togglePasswordVisibility('openai_api_key', this)"
                                       style="position: absolute; right: 14px; top: 34px; color: #94a3b8; cursor: pointer;"></i>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                                    <div>
                                        <label style="font-weight: 600; color: #475569; font-size: 12.5px; display: block; margin-bottom: 6px;">Base URL</label>
                                        <input type="text" name="openai_base_url" value="{{ old('openai_base_url', $user->getResolvedOpenAiBaseUrl()) }}"
                                               placeholder="https://api.openai.com/v1"
                                               style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none;" />
                                        <span style="font-size: 11px; color: #94a3b8; display: block; margin-top: 4px;">Default: https://api.openai.com/v1 atau URL custom Celeris</span>
                                    </div>
                                    <div>
                                        <label style="font-weight: 600; color: #475569; font-size: 12.5px; display: block; margin-bottom: 6px;">Model Name</label>
                                        <input type="text" name="openai_model" value="{{ old('openai_model', $user->getResolvedOpenAiModel()) }}"
                                               placeholder="gpt-4o-mini"
                                               style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; outline: none;" />
                                        <span style="font-size: 11px; color: #94a3b8; display: block; margin-top: 4px;">Contoh: gpt-4o-mini, gpt-4o, atau nama model custom Celeris</span>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- Submit Button -->
                        <div style="margin-top: 28px;">
                            <button type="submit" style="background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; border: none; padding: 12px 28px; border-radius: 10px; font-size: 14px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 14px rgba(99,102,241,0.35); display: inline-flex; align-items: center; gap: 8px;">
                                <i class="fas fa-save"></i> Simpan Pengaturan AI & API Keys
                            </button>
                        </div>

                    </div>
                </form>
            </div>

        </div>
    </div>

    <!-- Additional Styles & JS for Tabs -->
    <style>
        .tab-btn {
            background: transparent;
            border: none;
            padding: 12px 20px;
            font-size: 14px;
            font-weight: 600;
            color: #64748b;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }
        .tab-btn:hover {
            color: #1e293b;
        }
        .tab-btn.active {
            color: #6366f1;
            border-bottom-color: #6366f1;
            background: white;
            border-radius: 10px 10px 0 0;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.03);
        }
        .provider-radio-card {
            border: 2px solid #e2e8f0;
            background: white;
            border-radius: 12px;
            padding: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: block;
        }
        .provider-radio-card:hover {
            border-color: #cbd5e1;
            transform: translateY(-1px);
        }
        .provider-radio-card.selected {
            border-color: #6366f1;
            background: #eef2ff;
            box-shadow: 0 4px 12px rgba(99,102,241,0.12);
        }
    </style>

    <script>
        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));

            const targetContent = document.getElementById('tab-' + tabName);
            if (targetContent) targetContent.style.display = 'block';

            // Find matching tab button
            const buttons = document.querySelectorAll('.tab-btn');
            if (tabName === 'profile') buttons[0].classList.add('active');
            if (tabName === 'apikeys') buttons[1].classList.add('active');
        }

        function selectDefaultProvider(provider) {
            document.querySelectorAll('.provider-radio-card').forEach(card => card.classList.remove('selected'));
            const radio = document.querySelector(`input[name="default_ai_provider"][value="${provider}"]`);
            if (radio) {
                radio.checked = true;
                radio.closest('.provider-radio-card').classList.add('selected');
            }
        }

        function togglePasswordVisibility(fieldId, iconEl) {
            const input = document.getElementById(fieldId);
            if (!input) return;

            if (input.type === 'password') {
                input.type = 'text';
                iconEl.classList.remove('fa-eye');
                iconEl.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                iconEl.classList.remove('fa-eye-slash');
                iconEl.classList.add('fa-eye');
            }
        }
    </script>
@endsection
