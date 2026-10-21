# QA Portal - Quality Assurance Management System

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-13.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 13" />
  <img src="https://img.shields.io/badge/PHP-8.4-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.4" />
  <img src="https://img.shields.io/badge/Node.js-20.x-339933?style=for-the-badge&logo=node.js&logoColor=white" alt="Node.js 20" />
  <img src="https://img.shields.io/badge/Docker-Ready-2496ED?style=for-the-badge&logo=docker&logoColor=white" alt="Docker" />
  <img src="https://img.shields.io/badge/PWA-Enabled-5A0FC8?style=for-the-badge&logo=pwa&logoColor=white" alt="PWA" />
  <img src="https://img.shields.io/badge/WebSocket-Real--Time-010101?style=for-the-badge&logo=socketdotio&logoColor=white" alt="WebSocket" />
</p>

**QA Portal** adalah platform kolaborasi dan manajemen pengujian perangkat lunak (*Quality Assurance*) modern yang dirancang untuk mempercepat alur kerja pengujian tim, pelacakan bug terstandarisasi, komunikasi real-time, serta pelaporan berbasis kecerdasan buatan (AI).

---

## 🚀 Fitur Unggulan

### 1. 📋 Test Case & Test Report Management
* **Hierarki Pengujian Terstruktur:** Kelola proyek, test report, test suite, dan detail test case dengan langkah pengujian (*test steps*), data uji (*test data*), dan hasil yang diharapkan (*expected result*).
* **Eksekusi Pengujian & Bulk Actions:** Eksekusi status massal (Pass, Fail, Blocked, Skipped) dengan satu klik.
* **Import & Export:** Template CSV impor cepat dan ekspor laporan lengkap ke format Excel (*Spreadsheet*) & CSV.
* **AI Review & Auto-Generate:** Buat dan perbaiki skenario test case secara otomatis menggunakan integrasi AI.

### 2. 🐛 Bug Tracking System (QA-TPL-001)
* **Pelaporan Standar Industri:** Pelacakan bug terperinci dengan klasifikasi severity (Critical, Major, Minor, Trivial) dan priority (P1 - P4).
* **Quick-Bug Modal:** Input bug secara instan dari halaman mana saja tanpa berpindah konteks.
* **Siklus Hidup Bug Lengkap:** Alur kerja status *Open*, *In Progress*, *Resolved*, *Reopen*, *Ready to Retest*, hingga *Closed*.
* **Bukti Pengujian:** Lampirkan tangkapan layar, log error, dan langkah-langkah reproduksi bug.

### 3. 💬 Real-Time Team Collaboration & Chat
* **WebSocket Engine Cepat:** Ditenagai server Node.js mandiri (`chat-server.cjs`) dengan koneksi persisten tanpa jeda (*zero polling*).
* **Multi-Channel & Direct Messages:** Dukungan channel publik, grup privat, dan percakapan langsung 1-on-1.
* **Fitur Kolaborasi Lengkap:** Slack-style thread replies, pin pesan penting, reaksi emoji, pengiriman dokumen/gambar, dan perekam voice note langsung di browser.
* **Global Push Notifier:** Notifikasi desktop dan pembaruan badge unread otomatis di seluruh halaman portal.

### 4. 🤖 AI Daily Standup & Assistant
* **Otomasi Daily Standup:** Rangkum aktivitas pengujian, test case yang diselesaikan, dan temuan bug harian secara otomatis menjadi format standup siap kirim.
* **Multi-Provider AI:** Konfigurasi fleksibel mendukung **Google Gemini**, **OpenAI**, **OpenRouter**, **OmniRoute**, dan **Ollama** lokal.

### 5. 👥 User & Access Management (RBAC)
* **Kontrol Akses Berbasis Peran:** Pemisahan hak akses antara **Administrator** dan **QA Member**.
* **Panel Manajemen Pengguna:** Khusus admin untuk menambah anggota tim baru, mengubah role admin/member, mengaktifkan/menonaktifkan akun, dan reset password.
* **Keamanan Akun:** Proteksi pencegahan penguncian mandiri (*self-demotion & self-deletion guard*).

### 6. 📱 Progressive Web App (PWA)
* **Instalasi Mandiri:** Dapat diinstal langsung di perangkat Mobile (Android/iOS) dan Desktop (macOS/Windows) layaknya aplikasi native.
* **Offline Fallback:** Tampilan ramah pengguna saat koneksi internet terputus.
* **Mobile-First Bottom Navigation:** Bilah navigasi bawah yang responsif untuk kenyamanan penggunaan di smartphone.

---

## 🛠️ Arsitektur & Teknologi

* **Backend Framework:** [Laravel 13](https://laravel.com/)
* **Bahasa Pemrograman:** [PHP 8.4-FPM](https://www.php.net/)
* **Real-Time Engine:** [Node.js 20](https://nodejs.org/) + `ws` library
* **Frontend:** Blade Templating, [Tailwind CSS](https://tailwindcss.com/), [Vite 8](https://vitejs.dev/)
* **Basis Data:** SQLite (Default, Zero-Config) / MySQL / PostgreSQL
* **Containerization:** Docker Multi-Stage Build + Supervisord (PHP-FPM, Nginx, Node.js Chat Server, Laravel Worker)

---

## ⚡ Panduan Instalasi Lokal

### Prasyarat
* PHP 8.4 atau lebih baru (dengan ekstensi `pdo_sqlite`, `mbstring`, `gd`, `zip`, `intl`, `bcmath`)
* Composer 2+
* Node.js 20+ & npm

### Langkah Instalasi

1. **Clone repositori:**
   ```bash
   git clone https://github.com/yuucandoit/portalQA.git
   cd portalQA
   ```

2. **Install dependensi PHP & Node.js:**
   ```bash
   composer install
   npm install
   ```

3. **Setup environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Migrasi database dan seed akun default:**
   ```bash
   touch database/database.sqlite
   php artisan migrate --seed
   ```

5. **Build asset frontend:**
   ```bash
   npm run build
   ```

6. **Jalankan aplikasi (2 Terminal):**
   * **Terminal 1 (Laravel Server):**
     ```bash
     php artisan serve --port=8000
     ```
   * **Terminal 2 (WebSocket Chat Server):**
     ```bash
     npm run chat
     # atau: node chat-server.cjs
     ```

Buka browser Anda di `http://127.0.0.1:8000`.

---

## 🐳 Deployment Menggunakan Docker (Ubuntu Server)

QA Portal telah dilengkapi dengan konfigurasi container produksi yang mengemas **PHP 8.4-FPM, Nginx internal, WebSocket Server, dan Laravel Worker** ke dalam satu container yang diawasi oleh **Supervisor**.

### 1. Struktur Port & Volume
* **Port Web Host:** `9012` (di-forward ke port internal 80)
* **WebSocket:** Menggunakan SSL reverse proxy Nginx di endpoint `/ws` (`wss://domainanda.com/ws`)
* **Volume Persisten:** `qaportal_data` (Database SQLite), `qaportal_storage` (File upload & media), `qaportal_logs` (Log sistem)

### 2. Jalankan Container
```bash
cd /var/www/html/portalQA

# Bangun dan jalankan container di latar belakang
docker compose up -d --build

# Inisialisasi migrasi & data awal jika pertama kali setup
docker compose exec app php artisan migrate:fresh --seed
```

### 3. Konfigurasi Host Nginx Reverse Proxy (SSL)
Salin template konfigurasi dari [`host-nginx-qaportal.conf`](host-nginx-qaportal.conf) ke konfigurasi Nginx server Ubuntu Anda:

```nginx
server {
    server_name poqa.rexensoft.com; # Ganti dengan domain Anda

    client_max_body_size 64M;

    # 1. Forward Web Traffic ke Docker Port 9012
    location / {
        proxy_pass http://127.0.0.1:9012;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # 2. Forward WebSocket (Team Chat & Live Notifications)
    location /ws {
        proxy_pass http://127.0.0.1:9012/ws;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 86400s;
        proxy_send_timeout 86400s;
        proxy_buffering off;
    }
}
```

Reload Nginx:
```bash
sudo nginx -t && sudo systemctl reload nginx
```

---

## 🔑 Akun Default (Seeded)

Setelah menjalankan `php artisan db:seed` atau `migrate:fresh --seed`, akun berikut siap digunakan:

| Nama Pengguna | Alamat Email | Password | Role Akses |
|---|---|---|---|
| **Wahyu Sanjaya** | `wahyu@gmail.com` | `password` | 🛡️ **Administrator** |
| **Chandra Maulana** | `chandra@gmail.com` | `password` | 👤 QA Member |
| **Syahril Prasetyo** | `syahril@gmail.com` | `password` | 👤 QA Member |
| **Abdul Alem** | `alem@gmail.com` | `password` | 👤 QA Member |
| **Luthfi Rachman** | `luthfi@gmail.com` | `password` | 👤 QA Member |

> ⚠️ **Catatan Keamanan:** Segera ubah password akun-akun di atas pada menu **Settings** setelah melakukan deployment di lingkungan produksi.

---

## 📋 Perintah Pemeliharaan Rutin

* **Melihat log container:**
  ```bash
  docker compose logs -f
  ```
* **Membersihkan dan mengoptimalkan cache:**
  ```bash
  docker compose exec app php artisan optimize:clear
  docker compose exec app php artisan optimize
  ```
* **Memperbarui aplikasi dari git:**
  ```bash
  git pull origin main
  docker compose down
  docker compose up -d --build
  docker compose exec app php artisan migrate --force
  ```

---

## 🏷️ Versioning

Proyek ini menggunakan standar [Semantic Versioning (SemVer)](https://semver.org/).
* **`v1.0.0`** *(21 Oktober 2026)*: Rilis stabil perdana. Mendukung manajemen pengujian, pelacak bug, WebSocket team chat, manajemen pengguna & role admin, PWA mobile/desktop, serta containerization Docker produksi.

---

## 📄 Lisensi

QA Portal didistribusikan di bawah lisensi terbuka [MIT License](LICENSE).
