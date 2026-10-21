<?php

namespace Database\Seeders;

use App\Models\Projects;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $projects = [
            [
                'name' => 'Core Payment Gateway',
                'version' => 'v2.1.0',
                'description' => 'Sistem pemrosesan transaksi pembayaran inti dan integrasi bank.',
            ],
            [
                'name' => 'E-Commerce Mobile App',
                'version' => 'v3.0.2',
                'description' => 'Aplikasi belanja online berbasis iOS dan Android.',
            ],
            [
                'name' => 'Inventory Management System',
                'version' => 'v1.5.0',
                'description' => 'Manajemen stok barang, gudang, dan logistik.',
            ],
            [
                'name' => 'Customer Portal Web',
                'version' => 'v4.0.1',
                'description' => 'Portal mandiri pelanggan untuk tiket dukungan dan akun.',
            ],
            [
                'name' => 'Notification & Messaging Engine',
                'version' => 'v1.2.0',
                'description' => 'Layanan pengiriman email, push notification, dan SMS blast.',
            ],
            [
                'name' => 'QA Report Analytics Dashboard',
                'version' => 'v1.0.0',
                'description' => 'Platform analisis dan laporan pengujian otomatis.',
            ],
        ];

        foreach ($projects as $projectData) {
            Projects::updateOrCreate(
                ['name' => $projectData['name']],
                $projectData
            );
        }
    }
}
