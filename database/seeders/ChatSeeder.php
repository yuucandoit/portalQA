<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\ChatChannel;
use App\Models\ChatChannelMember;
use App\Models\ChatMessage;
use App\Models\ChatReaction;
use Illuminate\Support\Str;

class ChatSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        if ($users->isEmpty()) {
            return;
        }

        $admin = $users->first();

        // 1. Channel #general
        $general = ChatChannel::firstOrCreate(
            ['slug' => 'general'],
            [
                'name' => 'general',
                'type' => 'public_channel',
                'topic' => 'Diskusi umum dan koordinasi harian tim QA',
                'description' => 'Ruang obrolan santai dan koordinasi umum seluruh tim QA Portal.',
                'created_by' => $admin->id,
            ]
        );

        // 2. Channel #qa-announcements (Broadcast)
        $announcements = ChatChannel::firstOrCreate(
            ['slug' => 'qa-announcements'],
            [
                'name' => 'qa-announcements',
                'type' => 'broadcast',
                'topic' => 'Pengumuman resmi rilis & update QA Portal',
                'description' => 'Channel broadcast pengumuman resmi rilis, maintenance, dan standar QA.',
                'created_by' => $admin->id,
            ]
        );

        // 3. Channel #bug-reports
        $bugReports = ChatChannel::firstOrCreate(
            ['slug' => 'bug-reports'],
            [
                'name' => 'bug-reports',
                'type' => 'public_channel',
                'topic' => 'Diskusi penemuan bug & triage defect kritis',
                'description' => 'Koordinasi cepat untuk bug blocker/critical yang butuh reproduksi dan eskalasi.',
                'created_by' => $admin->id,
            ]
        );

        // 4. Channel #sprint-room
        $sprintRoom = ChatChannel::firstOrCreate(
            ['slug' => 'sprint-room'],
            [
                'name' => 'sprint-room',
                'type' => 'public_channel',
                'topic' => 'Koordinasi eksekusi test cases sprint berjalan',
                'description' => 'Ruang koordinasi pembagian test case dan review hasil pengujian sprint.',
                'created_by' => $admin->id,
            ]
        );

        $allChannels = [$general, $announcements, $bugReports, $sprintRoom];

        // Enroll all existing users into the public channels
        foreach ($allChannels as $channel) {
            foreach ($users as $user) {
                ChatChannelMember::firstOrCreate(
                    [
                        'channel_id' => $channel->id,
                        'user_id' => $user->id,
                    ],
                    [
                        'role' => ($user->id === $admin->id) ? 'owner' : 'member',
                    ]
                );
            }
        }

        // Direct Messages between user 1 and other users
        if ($users->count() > 1) {
            $user1 = $users[0];
            for ($i = 1; $i < $users->count(); $i++) {
                $otherUser = $users[$i];
                $dmSlug = 'dm-' . min($user1->id, $otherUser->id) . '-' . max($user1->id, $otherUser->id);

                $dm = ChatChannel::firstOrCreate(
                    ['slug' => $dmSlug],
                    [
                        'name' => $otherUser->name,
                        'type' => 'direct',
                        'topic' => 'Pesan langsung dengan ' . $otherUser->name,
                        'description' => 'Obrolan pribadi langsung 1-on-1.',
                        'created_by' => $user1->id,
                    ]
                );

                ChatChannelMember::firstOrCreate(['channel_id' => $dm->id, 'user_id' => $user1->id], ['role' => 'member']);
                ChatChannelMember::firstOrCreate(['channel_id' => $dm->id, 'user_id' => $otherUser->id], ['role' => 'member']);

                // Seed a starter message if empty
                if ($dm->allMessages()->count() === 0) {
                    ChatMessage::create([
                        'channel_id' => $dm->id,
                        'user_id' => $otherUser->id,
                        'message' => "Halo {$user1->name}! Ada update terkait modul pengujian hari ini?",
                        'message_type' => 'text',
                        'created_at' => now()->subMinutes(15),
                    ]);
                }
            }
        }

        // Seed sample rich messages in #general if empty
        if ($general->allMessages()->count() === 0 && $users->count() >= 2) {
            $u1 = $users[0];
            $u2 = $users[1];
            $u3 = $users[2] ?? $u1;

            $msg1 = ChatMessage::create([
                'channel_id' => $general->id,
                'user_id' => $u1->id,
                'message' => "Selamat pagi rekan-rekan QA! 🚀\nSistem Real-Time Team Chat WebSocket QA Portal sudah aktif. Silakan gunakan untuk koordinasi pengujian dan bertukar berkas.",
                'message_type' => 'text',
                'is_pinned' => true,
                'pinned_at' => now(),
                'pinned_by' => $u1->id,
                'created_at' => now()->subHours(2),
            ]);

            ChatReaction::create([
                'message_id' => $msg1->id,
                'user_id' => $u2->id,
                'emoji' => '🎉',
            ]);
            ChatReaction::create([
                'message_id' => $msg1->id,
                'user_id' => $u3->id,
                'emoji' => '🔥',
            ]);

            $msg2 = ChatMessage::create([
                'channel_id' => $general->id,
                'user_id' => $u2->id,
                'message' => "Mantap Mas Chandra! Format teks *bold*, _italic_, ~strikethrough~, dan code block `assertStatus(200)` berfungsi responsif.",
                'message_type' => 'text',
                'created_at' => now()->subHours(1)->addMinutes(10),
            ]);

            // Thread reply on msg2
            ChatMessage::create([
                'channel_id' => $general->id,
                'user_id' => $u1->id,
                'parent_id' => $msg2->id,
                'message' => 'Betul, threading percakapan bercabang seperti di Slack juga sudah terintegrasi.',
                'message_type' => 'text',
                'created_at' => now()->subHours(1)->addMinutes(12),
            ]);

            ChatMessage::create([
                'channel_id' => $general->id,
                'user_id' => $u3->id,
                'message' => "Siap, untuk pengujian Sprint ini saya handle modul Payment Gateway ya. Nanti hasil tes saya infokan di sini.",
                'message_type' => 'text',
                'created_at' => now()->subMinutes(20),
            ]);
        }

        // Seed announcement
        if ($announcements->allMessages()->count() === 0) {
            ChatMessage::create([
                'channel_id' => $announcements->id,
                'user_id' => $admin->id,
                'message' => "📢 **PENGUMUMAN QA PORTAL v2.5**\n\nStandar Pelaporan QA-TPL-001 dan Quality Gate QA-STD-001 kini terhubung langsung dengan sistem Team Chat Real-time. Pastikan seluruh defect status Fail memiliki tiket Bug ID sebelum rilis ditandatangani.\n\n_Quality is not an act, it is a habit._",
                'message_type' => 'text',
                'is_pinned' => true,
                'pinned_at' => now(),
                'pinned_by' => $admin->id,
                'created_at' => now()->subHours(5),
            ]);
        }
    }
}

