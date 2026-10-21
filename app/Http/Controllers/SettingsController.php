<?php

namespace App\Http\Controllers;

use App\Services\AiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    /**
     * Show Settings page.
     */
    public function index()
    {
        $user = Auth::user();
        $aiStatus = AiService::getProvidersStatus();

        return view('pages.settings.index', compact('user', 'aiStatus'));
    }

    /**
     * Update user profile (Name, Email, Password).
     */
    public function updateProfile(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'current_password' => 'nullable|required_with:new_password|string',
            'new_password'     => 'nullable|string|min:6|confirmed',
        ], [
            'name.required' => 'Nama tidak boleh kosong.',
            'email.required' => 'Email tidak boleh kosong.',
            'email.unique' => 'Email sudah digunakan oleh akun lain.',
            'current_password.required_with' => 'Password saat ini wajib diisi untuk mengubah password.',
            'new_password.min' => 'Password baru minimal 6 karakter.',
            'new_password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        // If changing password, verify current password
        if ($request->filled('new_password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['current_password' => 'Password saat ini salah.']);
            }
            $user->password = Hash::make($request->new_password);
        }

        $user->name  = $request->name;
        $user->email = $request->email;
        $user->save();

        return redirect()->route('settings.index')
            ->with('tab', 'profile')
            ->with('success', 'Profil berhasil diperbarui!');
    }

    /**
     * Update user AI & API Key settings.
     */
    public function updateApiKeys(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $request->validate([
            'default_ai_provider' => 'required|in:gemini,openrouter,ollama,omniroute,openai',
            'gemini_api_key'      => 'nullable|string|max:500',
            'openrouter_api_key'  => 'nullable|string|max:500',
            'openrouter_model'    => 'nullable|string|max:255',
            'omniroute_api_key'   => 'nullable|string|max:500',
            'ollama_base_url'     => 'nullable|url|max:255',
            'ollama_model'        => 'nullable|string|max:100',
            'omniroute_base_url'  => 'nullable|url|max:255',
            'omniroute_model'     => 'nullable|string|max:100',
            'openai_api_key'      => 'nullable|string|max:500',
            'openai_base_url'     => 'nullable|url|max:255',
            'openai_model'        => 'nullable|string|max:100',
        ], [
            'default_ai_provider.required' => 'Default AI Provider harus dipilih.',
            'ollama_base_url.url'          => 'Ollama Base URL harus berupa format URL valid (misal: http://localhost:11434).',
            'omniroute_base_url.url'       => 'OmniRoute Base URL harus berupa format URL valid (misal: http://202.155.143.157:20128/v1).',
            'openai_base_url.url'          => 'OpenAI / Celeris Base URL harus berupa format URL valid (misal: https://api.openai.com/v1).',
        ]);

        $user->default_ai_provider = $request->default_ai_provider;

        // Only update key if supplied or explicitly cleared
        if ($request->has('gemini_api_key')) {
            $user->gemini_api_key = trim($request->gemini_api_key);
        }
        if ($request->has('openrouter_api_key')) {
            $user->openrouter_api_key = trim($request->openrouter_api_key);
        }
        if ($request->has('omniroute_api_key')) {
            $user->omniroute_api_key = trim($request->omniroute_api_key);
        }
        if ($request->has('openai_api_key')) {
            $user->openai_api_key = trim($request->openai_api_key);
        }

        $user->openrouter_model   = trim($request->openrouter_model ?: 'meta-llama/llama-3.1-8b-instruct:free');
        $user->ollama_base_url    = trim($request->ollama_base_url ?: 'http://localhost:11434');
        $user->ollama_model       = trim($request->ollama_model ?: 'llama3.2');
        $user->omniroute_base_url = trim($request->omniroute_base_url ?: 'http://202.155.143.157:20128/v1');
        $user->omniroute_model    = trim($request->omniroute_model ?: 'auto/best-coding');
        $user->openai_base_url    = trim($request->openai_base_url ?: 'https://api.openai.com/v1');
        $user->openai_model       = trim($request->openai_model ?: 'gpt-4o-mini');

        $user->save();

        return redirect()->route('settings.index')
            ->with('tab', 'apikeys')
            ->with('success', 'Pengaturan AI & API Keys berhasil disimpan!');
    }
}
