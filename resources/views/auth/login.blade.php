<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'QA Portal') }} - {{ __('Login') }}</title>
    
    <!-- PWA Head Elements -->
    @include('layouts.partials.pwa-head')

    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <!-- Local Compiled Tailwind & App Assets via Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .mb-4 { margin-bottom: 1rem; }
        .mr-1 { margin-right: 0.25rem; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f0f2f5;
            padding: 20px;
        }

        .login-wrapper {
            width: 100%;
            max-width: 1100px;
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.12);
            overflow: hidden;
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 620px;
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Left Side - Image/Branding */
        .login-image {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
            min-height: 400px;
        }

        .login-image::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
            animation: pulse 8s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.2); opacity: 1; }
        }

        .image-content {
            position: relative;
            z-index: 1;
            color: white;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .image-content .brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .image-content .brand-icon {
            width: 52px;
            height: 52px;
            background: rgba(255,255,255,0.2);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        .image-content .brand-text h1 {
            font-size: 26px;
            font-weight: 700;
            margin: 0;
            letter-spacing: -0.5px;
        }

        .image-content .brand-text p {
            font-size: 13px;
            opacity: 0.85;
            margin: 0;
        }

        .image-content .illustration {
            text-align: center;
            margin: 40px 0;
        }

        .image-content .illustration i {
            font-size: 110px;
            opacity: 0.35;
            color: white;
            animation: float 6s ease-in-out infinite;
            filter: drop-shadow(0 10px 15px rgba(0,0,0,0.1));
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
        }

        .image-content .feature-list {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .image-content .feature-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            opacity: 0.95;
            font-weight: 500;
        }

        .image-content .feature-item i {
            width: 26px;
            height: 26px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }

        /* Right Side - Login Form */
        .login-form {
            padding: 50px 45px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: white;
        }

        .login-form .form-header {
            margin-bottom: 30px;
        }

        .login-form .form-header h2 {
            font-size: 28px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .login-form .form-header p {
            color: #6b7280;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i.input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 15px;
            transition: color 0.3s ease;
        }

        .input-wrapper input {
            width: 100%;
            padding: 12px 15px 12px 42px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: #f9fafb;
            color: #1f2937;
        }

        .input-wrapper input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.12);
        }

        .input-wrapper input:focus + .input-icon,
        .input-wrapper input:focus ~ .input-icon {
            color: #667eea;
        }

        .input-wrapper input.error,
        .input-wrapper input.is-invalid {
            border-color: #ef4444;
            background: #fef2f2;
        }

        .input-wrapper input.error:focus,
        .input-wrapper input.is-invalid:focus {
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.12);
        }

        .input-wrapper .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            font-size: 15px;
            transition: color 0.3s ease;
            padding: 5px;
        }

        .input-wrapper .toggle-password:hover {
            color: #4b5563;
        }

        .error-message {
            color: #ef4444;
            font-size: 12px;
            margin-top: 6px;
            display: none;
            align-items: center;
            gap: 5px;
            font-weight: 500;
        }

        .error-message.show {
            display: flex;
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            font-size: 13px;
        }

        .form-options label {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #4b5563;
            cursor: pointer;
            user-select: none;
        }

        .form-options label input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #667eea;
            cursor: pointer;
            border-radius: 4px;
        }

        .form-options a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .form-options a:hover {
            color: #5a67d8;
            text-decoration: underline;
        }

        .btn-login {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.25);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login:disabled {
            opacity: 0.75;
            cursor: not-allowed;
            transform: none;
        }

        .btn-login .btn-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-login .spinner {
            display: none;
            width: 18px;
            height: 18px;
            border: 2.5px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        .btn-login.loading .spinner {
            display: inline-block;
        }

        .btn-login.loading .btn-text {
            display: inline-block;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: none;
            align-items: center;
            gap: 10px;
            font-size: 13.5px;
            animation: slideDown 0.3s ease-out;
            font-weight: 500;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert.show {
            display: flex;
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }

        .alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #16a34a;
        }

        .footer-links {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }

        .footer-links p {
            color: #6b7280;
            font-size: 13px;
        }

        .footer-links a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: #5a67d8;
            text-decoration: underline;
        }

        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 22px;
            border-radius: 12px;
            color: white;
            font-size: 14px;
            font-weight: 500;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            transform: translateX(120%);
            transition: transform 0.4s ease;
            z-index: 9999;
            max-width: 400px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .toast.show {
            transform: translateX(0);
        }

        .toast-success { background: #10b981; }
        .toast-error { background: #ef4444; }
        .toast-info { background: #3b82f6; }

        /* Responsive */
        @media (max-width: 1024px) {
            .login-wrapper {
                grid-template-columns: 1fr 1fr;
                min-height: auto;
            }

            .login-image {
                padding: 40px 30px;
            }

            .login-form {
                padding: 40px 35px;
            }

            .image-content .feature-list {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .login-wrapper {
                grid-template-columns: 1fr;
                border-radius: 20px;
                min-height: auto;
            }

            .login-image {
                padding: 30px 25px;
                min-height: 220px;
            }

            .image-content .brand {
                margin-bottom: 15px;
            }

            .image-content .brand-text h1 {
                font-size: 22px;
            }

            .image-content .illustration {
                margin: 20px 0;
            }

            .image-content .illustration i {
                font-size: 65px;
            }

            .image-content .feature-list {
                display: none;
            }

            .login-form {
                padding: 30px 25px;
            }

            .login-form .form-header h2 {
                font-size: 24px;
            }

            .form-options {
                flex-direction: row;
                gap: 10px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 12px;
            }

            .login-image {
                padding: 20px;
                min-height: 180px;
            }

            .image-content .brand-icon {
                width: 40px;
                height: 40px;
                font-size: 18px;
            }

            .image-content .brand-text h1 {
                font-size: 20px;
            }

            .image-content .brand-text p {
                font-size: 12px;
            }

            .image-content .illustration i {
                font-size: 45px;
            }

            .login-form {
                padding: 25px 20px;
            }

            .login-form .form-header h2 {
                font-size: 22px;
            }

            .login-form .form-header p {
                font-size: 13px;
            }

            .input-wrapper input {
                font-size: 13.5px;
                padding: 10px 12px 10px 38px;
            }

            .form-options {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .btn-login {
                padding: 12px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>

    <!-- Toast Notification -->
    <div id="toast" class="toast">
        <i class="fas fa-info-circle"></i>
        <span id="toastMessage"></span>
    </div>

    <div class="login-wrapper">
        <!-- Left Side - Image/Branding -->
        <div class="login-image">
            <div class="image-content">
                <div class="brand">
                    <div class="brand-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="brand-text">
                        <h1>{{ config('app.name', 'QA Portal') }}</h1>
                        <p>Quality Assurance System</p>
                    </div>
                </div>

                <div class="illustration">
                     <i class="fas fa-shield-alt"></i>
                </div>

                <div class="feature-list">
                    <div class="feature-item">
                        <i class="fas fa-check"></i>
                        <span>Test Management</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check"></i>
                        <span>Multiple Test Types</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check"></i>
                        <span>Real-time Reports</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check"></i>
                        <span>Team Collaboration</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side - Login Form -->
        <div class="login-form">
            <div class="form-header">
                <h2>Welcome Back! 👋</h2>
                <p>Sign in to access your QA dashboard</p>
            </div>

            <!-- Laravel Session Status / Success Alert -->
            @if (session('status'))
                <div class="alert alert-success show mb-4">
                    <i class="fas fa-check-circle"></i>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            <!-- Laravel Session Error Alert -->
            @if (session('error'))
                <div class="alert alert-error show mb-4">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <!-- Top Validation Summary Error Alert -->
            @if ($errors->any())
                <div class="alert alert-error show mb-4">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>{{ __('Login gagal. Silakan periksa kembali email dan password Anda.') }}</span>
                </div>
            @endif

            <!-- Dynamic JS Alert Box -->
            <div id="alert" class="alert mb-4">
                <i class="fas fa-exclamation-circle"></i>
                <span id="alertMessage"></span>
            </div>

            <!-- Form Connected to Laravel Authentication -->
            <form id="loginForm" method="POST" action="{{ route('login') }}" autocomplete="off">
                @csrf

                <!-- Email Input -->
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope mr-1"></i> {{ __('Email Address') }}
                    </label>
                    <div class="input-wrapper">
                        <input 
                            type="email" 
                            id="email" 
                            name="email"
                            placeholder="you@example.com" 
                            value="{{ old('email') }}"
                            class="@error('email') error is-invalid @enderror"
                            required
                            autofocus
                        />
                        <i class="fas fa-envelope input-icon"></i>
                    </div>
                    @error('email')
                        <div class="error-message show" id="emailError">
                            <i class="fas fa-exclamation-circle"></i>
                            <span>{{ $message }}</span>
                        </div>
                    @else
                        <div class="error-message" id="emailError">
                            <i class="fas fa-exclamation-circle"></i>
                            <span>Please enter a valid email</span>
                        </div>
                    @enderror
                </div>

                <!-- Password Input -->
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock mr-1"></i> {{ __('Password') }}
                    </label>
                    <div class="input-wrapper">
                        <input 
                            type="password" 
                            id="password" 
                            name="password"
                            placeholder="Enter your password" 
                            class="@error('password') error is-invalid @enderror"
                            required
                        />
                        <i class="fas fa-lock input-icon"></i>
                        <button type="button" class="toggle-password" id="togglePassword" title="Toggle password visibility">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    @error('password')
                        <div class="error-message show" id="passwordError">
                            <i class="fas fa-exclamation-circle"></i>
                            <span>{{ $message }}</span>
                        </div>
                    @else
                        <div class="error-message" id="passwordError">
                            <i class="fas fa-exclamation-circle"></i>
                            <span>Password is required</span>
                        </div>
                    @enderror
                </div>

                <!-- Form Options -->
                <div class="form-options">
                    <label for="remember">
                        <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }} />
                        <span>{{ __('Remember Me') }}</span>
                    </label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}">
                            {{ __('Forgot password?') }}
                        </a>
                    @endif
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-login" id="loginBtn">
                    <span class="btn-content">
                        <span class="spinner"></span>
                        <span class="btn-text">
                            <i class="fas fa-sign-in-alt"></i> {{ __('Sign In') }}
                        </span>
                    </span>
                </button>
            </form>

            {{-- <div class="footer-links">
                @if (Route::has('register'))
                    <p>
                        Don't have an account? 
                        <a href="{{ route('register') }}">
                            Create Account
                        </a>
                    </p>
                @endif
            </div> --}}
        </div>
    </div>

    <script>
        (function() {
            'use strict';

            const loginForm = document.getElementById('loginForm');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const togglePasswordBtn = document.getElementById('togglePassword');
            const loginBtn = document.getElementById('loginBtn');
            const alertDiv = document.getElementById('alert');
            const alertMessage = document.getElementById('alertMessage');
            const emailError = document.getElementById('emailError');
            const passwordError = document.getElementById('passwordError');

            // Toggle password visibility
            if (togglePasswordBtn && passwordInput) {
                togglePasswordBtn.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    const icon = this.querySelector('i');
                    if (icon) {
                        icon.classList.toggle('fa-eye');
                        icon.classList.toggle('fa-eye-slash');
                    }
                });
            }

            // Real-time validation helpers
            function isValidEmail(email) {
                return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
            }

            function hideAlert() {
                if (alertDiv) {
                    alertDiv.classList.remove('show', 'alert-error', 'alert-success');
                }
            }

            // Input event listeners for clean UX
            if (emailInput) {
                emailInput.addEventListener('input', function() {
                    if (this.value && !isValidEmail(this.value)) {
                        this.classList.add('error');
                        if (emailError) emailError.classList.add('show');
                    } else {
                        this.classList.remove('error');
                        if (emailError && !this.classList.contains('is-invalid')) {
                            emailError.classList.remove('show');
                        }
                    }
                    hideAlert();
                });
            }

            if (passwordInput) {
                passwordInput.addEventListener('input', function() {
                    if (!this.value) {
                        this.classList.add('error');
                        if (passwordError) passwordError.classList.add('show');
                    } else {
                        this.classList.remove('error');
                        if (passwordError && !this.classList.contains('is-invalid')) {
                            passwordError.classList.remove('show');
                        }
                    }
                    hideAlert();
                });
            }

            // Toast helper function
            window.showToast = function(type, message) {
                const toast = document.getElementById('toast');
                const toastMessage = document.getElementById('toastMessage');
                if (!toast || !toastMessage) return;

                toast.className = 'toast';
                toast.classList.add(`toast-${type}`);
                toastMessage.textContent = message;
                
                const icon = toast.querySelector('i');
                if (icon) {
                    if (type === 'success') {
                        icon.className = 'fas fa-check-circle';
                    } else if (type === 'error') {
                        icon.className = 'fas fa-exclamation-circle';
                    } else {
                        icon.className = 'fas fa-info-circle';
                    }
                }
                
                toast.classList.add('show');
                
                setTimeout(() => {
                    toast.classList.remove('show');
                }, 3500);
            };

            // Form Submit handling connected to Laravel POST
            if (loginForm) {
                loginForm.addEventListener('submit', function(e) {
                    const email = emailInput ? emailInput.value.trim() : '';
                    const password = passwordInput ? passwordInput.value.trim() : '';
                    let isValid = true;

                    if (!email || !isValidEmail(email)) {
                        if (emailInput) emailInput.classList.add('error');
                        if (emailError) emailError.classList.add('show');
                        isValid = false;
                    }

                    if (!password) {
                        if (passwordInput) passwordInput.classList.add('error');
                        if (passwordError) passwordError.classList.show ? passwordError.classList.add('show') : null;
                        isValid = false;
                    }

                    if (!isValid) {
                        e.preventDefault();
                        if (alertMessage && alertDiv) {
                            alertMessage.textContent = 'Harap isi email dan password dengan benar.';
                            alertDiv.className = 'alert alert-error show mb-4';
                        }
                        return;
                    }

                    // Show loading state while submitting to Laravel
                    if (loginBtn) {
                        loginBtn.classList.add('loading');
                        const btnText = loginBtn.querySelector('.btn-text');
                        if (btnText) {
                            btnText.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Signing In...';
                        }
                    }
                });
            }

            // Keydown animation trigger on failure if server errors exist
            @if ($errors->any())
                const wrapper = document.querySelector('.login-wrapper');
                if (wrapper) {
                    wrapper.style.animation = 'shake 0.5s ease';
                }
            @endif
        })();
    </script>

    <style>
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(-10px); }
            40% { transform: translateX(10px); }
            60% { transform: translateX(-10px); }
            80% { transform: translateX(10px); }
        }
    </style>
    <script src="{{ asset('js/pwa.js') }}"></script>
</body>
</html>
