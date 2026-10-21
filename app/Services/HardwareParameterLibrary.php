<?php

namespace App\Services;

class HardwareParameterLibrary
{
    /**
     * Standard Hardware Parameters Library per QA-TPL-001 Master v0.2
     */
    public static function all(): array
    {
        return [
            // 1. Daya & Baterai
            [
                'kategori'      => 'Daya & Baterai',
                'parameter'     => 'Tegangan sebelum/sesudah misi',
                'satuan'        => 'V',
                'cara_ukur'     => 'Telemetri / multimeter',
                'berlaku_untuk' => 'Drone',
                'batas_lulus'   => 'Tegangan setelah misi >= batas minimum spec',
            ],
            [
                'kategori'      => 'Daya & Baterai',
                'parameter'     => 'Persentase baterai sebelum/sesudah',
                'satuan'        => '%',
                'cara_ukur'     => 'Telemetri',
                'berlaku_untuk' => 'Drone',
                'batas_lulus'   => 'Baterai tersisa >= 20% saat landing',
            ],
            [
                'kategori'      => 'Daya & Baterai',
                'parameter'     => 'Suhu baterai',
                'satuan'        => '°C',
                'cara_ukur'     => 'Telemetri',
                'berlaku_untuk' => 'Drone',
                'batas_lulus'   => 'Suhu baterai <= 50 °C',
            ],
            [
                'kategori'      => 'Daya & Baterai',
                'parameter'     => 'Waktu charging sampai penuh',
                'satuan'        => 'menit',
                'cara_ukur'     => 'Stopwatch / log docking',
                'berlaku_untuk' => 'Drone, Docking',
                'batas_lulus'   => '<= 45 menit dari 20% ke 100%',
            ],
            [
                'kategori'      => 'Daya & Baterai',
                'parameter'     => 'Keberhasilan charge sequence otomatis',
                'satuan'        => 'siklus',
                'cara_ukur'     => 'Observasi per pendaratan',
                'berlaku_untuk' => 'Docking',
                'batas_lulus'   => '100% siklus berhasil tanpa intervensi manual',
            ],

            // 2. Thermal
            [
                'kategori'      => 'Thermal',
                'parameter'     => 'Suhu IMU',
                'satuan'        => '°C',
                'cara_ukur'     => 'Telemetri',
                'berlaku_untuk' => 'Drone',
                'batas_lulus'   => 'Suhu IMU <= 60 °C selama operasi',
            ],
            [
                'kategori'      => 'Thermal',
                'parameter'     => 'Suhu dalam docking',
                'satuan'        => '°C',
                'cara_ukur'     => 'Sensor docking',
                'berlaku_untuk' => 'Docking',
                'batas_lulus'   => 'Suhu interior <= 40 °C (AC/Fan aktif)',
            ],
            [
                'kategori'      => 'Thermal',
                'parameter'     => 'Suhu ambient',
                'satuan'        => '°C',
                'cara_ukur'     => 'Termometer / data cuaca',
                'berlaku_untuk' => 'Semua',
                'batas_lulus'   => 'Dalam rentang operasional -10 °C s.d. 45 °C',
            ],

            // 3. Penerbangan & Navigasi
            [
                'kategori'      => 'Penerbangan & Navigasi',
                'parameter'     => 'Akurasi altitude takeoff',
                'satuan'        => 'm',
                'cara_ukur'     => 'Telemetri vs setting',
                'berlaku_untuk' => 'Drone',
                'batas_lulus'   => 'Deviasi <= 0.5 m',
            ],
            [
                'kategori'      => 'Penerbangan & Navigasi',
                'parameter'     => 'Stabilitas hover (drift)',
                'satuan'        => 'm',
                'cara_ukur'     => 'Telemetri posisi',
                'berlaku_untuk' => 'Drone',
                'batas_lulus'   => 'Drift horizontal <= 0.3 m, vertikal <= 0.2 m',
            ],
            [
                'kategori'      => 'Penerbangan & Navigasi',
                'parameter'     => 'Akurasi posisi waypoint',
                'satuan'        => 'm',
                'cara_ukur'     => 'Telemetri vs koordinat WP',
                'berlaku_untuk' => 'Drone',
                'batas_lulus'   => 'Radius akurasi <= 1.0 m',
            ],
            [
                'kategori'      => 'Penerbangan & Navigasi',
                'parameter'     => 'Presisi landing (jarak dari tengah pad)',
                'satuan'        => 'cm',
                'cara_ukur'     => 'Ukur fisik / kamera dock',
                'berlaku_untuk' => 'Drone, Docking',
                'batas_lulus'   => '<= 10 cm dari titik tengah landing pad',
            ],
            [
                'kategori'      => 'Penerbangan & Navigasi',
                'parameter'     => 'Durasi terbang',
                'satuan'        => 'menit',
                'cara_ukur'     => 'Log misi',
                'berlaku_untuk' => 'Drone',
                'batas_lulus'   => '>= 30 menit per misi normal',
            ],
            [
                'kategori'      => 'Penerbangan & Navigasi',
                'parameter'     => 'Jumlah satelit GPS / HDOP',
                'satuan'        => 'unit / -',
                'cara_ukur'     => 'Telemetri',
                'berlaku_untuk' => 'Drone',
                'batas_lulus'   => 'Satelit >= 14, HDOP <= 1.2',
            ],

            // 4. Komunikasi
            [
                'kategori'      => 'Komunikasi',
                'parameter'     => 'Kekuatan sinyal (RSSI)',
                'satuan'        => 'dBm',
                'cara_ukur'     => 'Telemetri',
                'berlaku_untuk' => 'Drone, Docking',
                'batas_lulus'   => 'RSSI >= -75 dBm pada jarak maksimum',
            ],
            [
                'kategori'      => 'Komunikasi',
                'parameter'     => 'Latency kontrol / data',
                'satuan'        => 'ms',
                'cara_ukur'     => 'Log sistem',
                'berlaku_untuk' => 'Semua',
                'batas_lulus'   => '<= 150 ms',
            ],
            [
                'kategori'      => 'Komunikasi',
                'parameter'     => 'Waktu reconnect setelah putus',
                'satuan'        => 'detik',
                'cara_ukur'     => 'Stopwatch / log',
                'berlaku_untuk' => 'Semua',
                'batas_lulus'   => '<= 5 detik setelah sinyal pulih',
            ],

            // 5. Kamera & Media
            [
                'kategori'      => 'Kamera & Media',
                'parameter'     => 'Latency live stream',
                'satuan'        => 'detik',
                'cara_ukur'     => 'Bandingkan jam nyata vs stream',
                'berlaku_untuk' => 'Drone, Docking',
                'batas_lulus'   => '<= 1.0 detik (sub-second delay)',
            ],
            [
                'kategori'      => 'Kamera & Media',
                'parameter'     => 'Resolusi / fps stream',
                'satuan'        => 'px / fps',
                'cara_ukur'     => 'Info stream',
                'berlaku_untuk' => 'Drone',
                'batas_lulus'   => '1080p @ 30 fps stabil',
            ],
            [
                'kategori'      => 'Kamera & Media',
                'parameter'     => 'Ukuran & waktu unduh media',
                'satuan'        => 'MB / detik',
                'cara_ukur'     => 'Log unduhan',
                'berlaku_untuk' => 'Drone',
                'batas_lulus'   => 'Transfer rate >= 15 MB/detik',
            ],

            // 6. Mekanik & Docking
            [
                'kategori'      => 'Mekanik & Docking',
                'parameter'     => 'Waktu buka / tutup pintu',
                'satuan'        => 'detik',
                'cara_ukur'     => 'Stopwatch',
                'berlaku_untuk' => 'Docking',
                'batas_lulus'   => '<= 8 detik per siklus',
            ],
            [
                'kategori'      => 'Mekanik & Docking',
                'parameter'     => 'Waktu lifter naik / turun',
                'satuan'        => 'detik',
                'cara_ukur'     => 'Stopwatch',
                'berlaku_untuk' => 'Docking',
                'batas_lulus'   => '<= 5 detik per siklus',
            ],
            [
                'kategori'      => 'Mekanik & Docking',
                'parameter'     => 'Keberhasilan kontak charging',
                'satuan'        => 'siklus',
                'cara_ukur'     => 'Observasi',
                'berlaku_untuk' => 'Docking',
                'batas_lulus'   => '100% kontak terhubung rapat',
            ],

            // 7. Anti-drone
            [
                'kategori'      => 'Anti-drone',
                'parameter'     => 'Jarak deteksi',
                'satuan'        => 'm',
                'cara_ukur'     => 'Uji lapangan dengan target',
                'berlaku_untuk' => 'Anti-drone',
                'batas_lulus'   => '>= 3000 m untuk micro/mini drone',
            ],
            [
                'kategori'      => 'Anti-drone',
                'parameter'     => 'Waktu deteksi',
                'satuan'        => 'detik',
                'cara_ukur'     => 'Log sistem',
                'berlaku_untuk' => 'Anti-drone',
                'batas_lulus'   => '<= 3 detik sejak target masuk jangkauan',
            ],
            [
                'kategori'      => 'Anti-drone',
                'parameter'     => 'Akurasi identifikasi target',
                'satuan'        => '%',
                'cara_ukur'     => 'Jumlah benar / total uji',
                'berlaku_untuk' => 'Anti-drone',
                'batas_lulus'   => '>= 95% benar',
            ],
            [
                'kategori'      => 'Anti-drone',
                'parameter'     => 'Tingkat false positive',
                'satuan'        => '%',
                'cara_ukur'     => 'Deteksi salah / total uji',
                'berlaku_untuk' => 'Anti-drone',
                'batas_lulus'   => '<= 2% false alarm',
            ],

            // 8. Lingkungan
            [
                'kategori'      => 'Lingkungan',
                'parameter'     => 'Kecepatan angin saat uji',
                'satuan'        => 'm/s',
                'cara_ukur'     => 'Anemometer / data cuaca',
                'berlaku_untuk' => 'Semua (outdoor)',
                'batas_lulus'   => 'Resistansi angin hingga 12 m/s',
            ],
            [
                'kategori'      => 'Lingkungan',
                'parameter'     => 'Kelembapan / kondisi hujan',
                'satuan'        => '% / -',
                'cara_ukur'     => 'Data cuaca',
                'berlaku_untuk' => 'Semua (outdoor)',
                'batas_lulus'   => 'Rating IP55 (tahan hujan ringan/debu)',
            ],

            // 9. Reliability
            [
                'kategori'      => 'Reliability',
                'parameter'     => 'Jumlah siklus berhasil / total siklus',
                'satuan'        => 'siklus',
                'cara_ukur'     => 'Rekap per sesi uji',
                'berlaku_untuk' => 'Semua',
                'batas_lulus'   => '>= 95% siklus sukses',
            ],
            [
                'kategori'      => 'Reliability',
                'parameter'     => 'Durasi operasi kontinu tanpa gagal',
                'satuan'        => 'jam',
                'cara_ukur'     => 'Log',
                'berlaku_untuk' => 'Semua',
                'batas_lulus'   => '>= 24 jam nonstop',
            ],
            [
                'kategori'      => 'Reliability',
                'parameter'     => 'Jumlah kegagalan per sesi',
                'satuan'        => 'kali',
                'cara_ukur'     => 'Rekap defect',
                'berlaku_untuk' => 'Semua',
                'batas_lulus'   => '0 fatal defect',
            ],
        ];
    }
}
