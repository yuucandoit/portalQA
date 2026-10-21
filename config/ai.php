<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    | Supported: "gemini", "openrouter", "ollama"
    */
    'default_provider' => env('AI_DEFAULT_PROVIDER', 'gemini'),

    /*
    |--------------------------------------------------------------------------
    | Gemini (Google AI Studio)
    |--------------------------------------------------------------------------
    | Get your key at: https://aistudio.google.com/app/apikey
    */
    'gemini' => [
        'api_key'  => env('GEMINI_API_KEY', ''),
        'model'    => env('GEMINI_MODEL', 'gemini-1.5-flash'),
        'base_url' => 'https://generativelanguage.googleapis.com/v1beta/models',
    ],

    /*
    |--------------------------------------------------------------------------
    | OpenRouter (OpenAI-compatible, multi-model)
    |--------------------------------------------------------------------------
    | Get your key at: https://openrouter.ai/keys
    */
    'openrouter' => [
        'api_key'  => env('OPENROUTER_API_KEY', ''),
        'model'    => env('OPENROUTER_MODEL', 'meta-llama/llama-3.1-8b-instruct:free'),
        'base_url' => 'https://openrouter.ai/api/v1',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ollama (local, no API key required)
    |--------------------------------------------------------------------------
    | Install: https://ollama.com — then run: ollama pull llama3.2
    */
    'ollama' => [
        'base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
        'model'    => env('OLLAMA_MODEL', 'llama3.2'),
    ],

    /*
    |--------------------------------------------------------------------------
    | OmniRoute (OpenAI-compatible router: http://202.155.143.157:20128)
    |--------------------------------------------------------------------------
    */
    'omniroute' => [
        'api_key'  => env('OMNIROUTE_API_KEY', ''),
        'base_url' => env('OMNIROUTE_BASE_URL', 'http://202.155.143.157:20128/v1'),
        'model'    => env('OMNIROUTE_MODEL', 'auto'),
    ],

    /*
    |--------------------------------------------------------------------------
    | OpenAI / Celeris / Custom OpenAI-compatible Provider
    |--------------------------------------------------------------------------
    */
    'openai' => [
        'api_key'  => env('OPENAI_API_KEY', ''),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'model'    => env('OPENAI_MODEL', 'gpt-4o-mini'),
    ],

];

