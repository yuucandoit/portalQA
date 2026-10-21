/**
 * QA Portal - PWA Client Helper
 * Service Worker registration, install prompt handling, and iOS install guide
 */

(function () {
    'use strict';

    let deferredInstallPrompt = null;
    const isIos = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

    // Apply class to body if running as installed standalone PWA
    if (isStandalone) {
        document.documentElement.classList.add('pwa-standalone');
        document.body && document.body.classList.add('pwa-standalone');
    }

    // 1. Register Service Worker
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js', { scope: '/' })
                .then((registration) => {
                    // Check for updates
                    registration.addEventListener('updatefound', () => {
                        const installingWorker = registration.installing;
                        if (installingWorker) {
                            installingWorker.addEventListener('statechange', () => {
                                if (installingWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                    showUpdateNotification(installingWorker);
                                }
                            });
                        }
                    });
                })
                .catch((err) => {
                    console.warn('[PWA] ServiceWorker registration failed:', err);
                });
        });

        // Auto reload when controller changes after update
        let refreshing = false;
        navigator.serviceWorker.addEventListener('controllerchange', () => {
            if (!refreshing) {
                refreshing = true;
                window.location.reload();
            }
        });
    }

    // 2. Capture 'beforeinstallprompt' (Desktop Chrome/Edge, Android)
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredInstallPrompt = e;
        updateInstallButtonsVisibility(true);
    });

    // 3. App Installed Event
    window.addEventListener('appinstalled', () => {
        console.log('[PWA] QA Portal successfully installed');
        deferredInstallPrompt = null;
        updateInstallButtonsVisibility(false);
        showToast('QA Portal berhasil diinstal ke perangkat Anda!', 'success');
    });

    // 4. Expose Global Trigger Function
    window.triggerPwaInstall = function () {
        if (deferredInstallPrompt) {
            deferredInstallPrompt.prompt();
            deferredInstallPrompt.userChoice.then((choiceResult) => {
                if (choiceResult.outcome === 'accepted') {
                    console.log('[PWA] User accepted install prompt');
                } else {
                    console.log('[PWA] User dismissed install prompt');
                }
                deferredInstallPrompt = null;
                updateInstallButtonsVisibility(false);
            });
            return;
        }

        // If iOS, show instructional modal
        if (isIos && !isStandalone) {
            showIosInstallModal();
            return;
        }

        // If already standalone
        if (isStandalone) {
            showToast('Aplikasi sudah terinstal di perangkat Anda.', 'info');
            return;
        }

        // Fallback info for desktop browsers that don't emit beforeinstallprompt immediately
        showToast('Gunakan menu browser (titik tiga atau ikon install di address bar) untuk memasang QA Portal.', 'info');
    };

    function updateInstallButtonsVisibility(visible) {
        const installBtns = document.querySelectorAll('.pwa-install-trigger');
        installBtns.forEach(btn => {
            if (isStandalone) {
                btn.style.display = 'none';
            } else if (visible || isIos) {
                btn.style.display = 'inline-flex';
            }
        });
    }

    // 5. iOS Install Instruction Modal
    function showIosInstallModal() {
        let modal = document.getElementById('pwaIosModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'pwaIosModal';
            modal.className = 'pwa-ios-modal-backdrop';
            modal.innerHTML = `
                <div class="pwa-ios-modal-card">
                    <div class="pwa-ios-modal-header">
                        <div class="pwa-ios-modal-icon">
                            <img src="/icons/icon-192x192.png" alt="QA Portal" style="width: 48px; height: 48px; border-radius: 12px;" />
                        </div>
                        <div>
                            <h3>Pasang QA Portal di iPhone/iPad</h3>
                            <p>Akses cepat seperti aplikasi bawaan iOS</p>
                        </div>
                        <button type="button" class="btn-close-modal" onclick="window.closePwaIosModal()">&times;</button>
                    </div>
                    <div class="pwa-ios-steps">
                        <div class="pwa-step">
                            <span class="step-num">1</span>
                            <span>Ketuk tombol <strong>Bagikan (Share)</strong> <i class="fas fa-arrow-up-from-bracket text-blue-500"></i> di bar bawah Safari.</span>
                        </div>
                        <div class="pwa-step">
                            <span class="step-num">2</span>
                            <span>Gulir ke bawah dan pilih <strong>"Tambah ke Layar Utama" (Add to Home Screen)</strong> <i class="far fa-plus-square text-blue-500"></i>.</span>
                        </div>
                        <div class="pwa-step">
                            <span class="step-num">3</span>
                            <span>Ketuk <strong>"Tambah" (Add)</strong> di sudut kanan atas. Selesai!</span>
                        </div>
                    </div>
                    <button type="button" class="btn-understand" onclick="window.closePwaIosModal()">Saya Mengerti</button>
                </div>
            `;
            document.body.appendChild(modal);
        }
        modal.style.display = 'flex';
    }

    window.closePwaIosModal = function () {
        const modal = document.getElementById('pwaIosModal');
        if (modal) modal.style.display = 'none';
    };

    // 6. Update notification banner
    function showUpdateNotification(worker) {
        const banner = document.createElement('div');
        banner.className = 'pwa-update-banner';
        banner.innerHTML = `
            <span>Pembaruan versi baru QA Portal tersedia!</span>
            <button type="button" class="btn-update-now" id="btnPwaUpdateNow">Perbarui Sekarang</button>
        `;
        document.body.appendChild(banner);

        document.getElementById('btnPwaUpdateNow').addEventListener('click', () => {
            worker.postMessage({ type: 'SKIP_WAITING' });
            banner.remove();
        });
    }

    // 7. Toast notification helper
    function showToast(message, type) {
        let toastContainer = document.getElementById('pwaToastContainer');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'pwaToastContainer';
            toastContainer.className = 'pwa-toast-container';
            document.body.appendChild(toastContainer);
        }

        const toast = document.createElement('div');
        toast.className = `pwa-toast pwa-toast-${type || 'info'}`;
        const icon = type === 'success' ? 'fa-check-circle' : 'fa-info-circle';
        toast.innerHTML = `<i class="fas ${icon}"></i> <span>${message}</span>`;
        toastContainer.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(10px)';
            setTimeout(() => toast.remove(), 300);
        }, 3500);
    }

    // Initialize check on DOM ready
    document.addEventListener('DOMContentLoaded', () => {
        if (!isStandalone) {
            updateInstallButtonsVisibility(isIos || !!deferredInstallPrompt);
        }
    });

})();

