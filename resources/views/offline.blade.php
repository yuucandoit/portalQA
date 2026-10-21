<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#1e293b">
    <title>Koneksi Terputus - QA Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .offline-card {
            background: rgba(30, 41, 59, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 40px 32px;
            max-width: 480px;
            width: 100%;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.4s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .offline-icon-wrap {
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            border-radius: 50%;
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #f87171;
            font-size: 32px;
            position: relative;
        }
        .offline-icon-wrap::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 2px solid rgba(239, 68, 68, 0.4);
            animation: pulseRing 2s infinite;
        }
        @keyframes pulseRing {
            0% { transform: scale(1); opacity: 0.8; }
            100% { transform: scale(1.3); opacity: 0; }
        }
        .offline-title {
            font-size: 24px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 12px;
            letter-spacing: -0.5px;
        }
        .offline-desc {
            font-size: 14px;
            color: #94a3b8;
            line-height: 1.6;
            margin-bottom: 28px;
        }
        .offline-tips {
            background: rgba(15, 23, 42, 0.6);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 28px;
            text-align: left;
            font-size: 13px;
            color: #cbd5e1;
        }
        .offline-tips h4 {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #38bdf8;
            margin-bottom: 8px;
            font-weight: 700;
        }
        .offline-tips ul {
            list-style: none;
            padding: 0;
        }
        .offline-tips li {
            padding: 4px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .offline-tips li i {
            color: #64748b;
            font-size: 11px;
        }
        .btn-retry {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 14px 20px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #ffffff;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 10px 20px -5px rgba(37, 99, 235, 0.4);
            text-decoration: none;
        }
        .btn-retry:hover {
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
            transform: translateY(-1px);
        }
        .btn-retry:active {
            transform: translateY(1px);
        }
        .btn-retry.loading i {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .network-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 18px;
        }
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #ef4444;
        }
    </style>
</head>
<body>
    <div class="offline-card">
        <div class="offline-icon-wrap">
            <i class="fas fa-wifi-slash"></i>
        </div>

        <div class="network-status-badge" id="netStatusBadge">
            <span class="status-dot" id="statusDot"></span>
            <span id="statusText">Mode Offline</span>
        </div>

        <h1 class="offline-title">Koneksi Internet Terputus</h1>
        <p class="offline-desc">
            QA Portal tidak dapat terhubung ke server saat ini. Halaman akan otomatis dimuat ulang begitu perangkat Anda terhubung kembali.
        </p>

        <div class="offline-tips">
            <h4><i class="fas fa-info-circle mr-1"></i> Apa yang terjadi?</h4>
            <ul>
                <li><i class="fas fa-check"></i> Halaman yang pernah Anda buka tetap tersimpan di cache.</li>
                <li><i class="fas fa-check"></i> Chat dan perubahan data akan sinkron otomatis setelah online.</li>
                <li><i class="fas fa-check"></i> Periksa koneksi Wi-Fi atau data seluler perangkat Anda.</li>
            </ul>
        </div>

        <button type="button" class="btn-retry" id="retryBtn" onclick="handleRetry()">
            <i class="fas fa-sync-alt" id="retryIcon"></i>
            <span id="retryText">Coba Muat Ulang</span>
        </button>
    </div>

    <script>
        function updateOnlineStatus() {
            const badge = document.getElementById('netStatusBadge');
            const dot = document.getElementById('statusDot');
            const text = document.getElementById('statusText');
            if (navigator.onLine) {
                dot.style.background = '#22c55e';
                badge.style.background = 'rgba(34, 197, 94, 0.15)';
                badge.style.color = '#86efac';
                text.textContent = 'Koneksi Pulih! Memuat...';
                setTimeout(() => window.location.reload(), 800);
            } else {
                dot.style.background = '#ef4444';
                badge.style.background = 'rgba(239, 68, 68, 0.15)';
                badge.style.color = '#fca5a5';
                text.textContent = 'Mode Offline';
            }
        }

        window.addEventListener('online', updateOnlineStatus);
        window.addEventListener('offline', updateOnlineStatus);

        function handleRetry() {
            const btn = document.getElementById('retryBtn');
            const icon = document.getElementById('retryIcon');
            const text = document.getElementById('retryText');
            btn.classList.add('loading');
            text.textContent = 'Memeriksa koneksi...';

            fetch('/offline', { method: 'HEAD', cache: 'no-store' })
                .then(() => {
                    window.location.reload();
                })
                .catch(() => {
                    setTimeout(() => {
                        btn.classList.remove('loading');
                        text.textContent = 'Masih Offline (Coba Lagi)';
                    }, 1000);
                });
        }
    </script>
</body>
</html>

