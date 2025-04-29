<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;
use Illuminate\Support\Facades\DB;

return new class extends SettingsMigration {
    public function up(): void
    {
        $now = now();

        $settings = [
            // General Info
            ['name' => 'name', 'payload' => 'Oh Ed'],
            ['name' => 'description', 'payload' => 'Oh Ed is an IT firm...'],
            ['name' => 'icon', 'payload' => '/app-settings/app-icon.avif'],
            ['name' => 'logo', 'payload' => '/app-settings/app-logo.avif'],
            ['name' => 'website', 'payload' => 'https://edeesonopina.vercel.app'],

            // Navbar
            ['name' => 'navbar_bg_color', 'payload' => '#fff71a'], // Not active
            ['name' => 'navbar_text_color', 'payload' => '#212121'], // Not active
            ['name' => 'navbar_hover_bg_color', 'payload' => '#fff71a'], // Not active
            ['name' => 'navbar_hover_text_color', 'payload' => '#212121'], // Not active
            ['name' => 'navbar_active_bg_color', 'payload' => '#fff71a'],
            ['name' => 'navbar_active_text_color', 'payload' => '#212121'],

            // Sidebar
            ['name' => 'sidebar_bg_color', 'payload' => '#3355db'], // Not active
            ['name' => 'sidebar_text_color', 'payload' => '#212121'], // Not active
            ['name' => 'sidebar_hover_bg_color', 'payload' => '#3355db'], // Not active
            ['name' => 'sidebar_hover_text_color', 'payload' => '#212121'], // Not active
            ['name' => 'sidebar_active_bg_color', 'payload' => '#3355db'],
            ['name' => 'sidebar_active_text_color', 'payload' => '#ffffff'],

            // Button
            ['name' => 'button_primary_bg_color', 'payload' => '#3355db'],
            ['name' => 'button_primary_text_color', 'payload' => '#ffffff'],

            // Input
            ['name' => 'input_active_bg_color', 'payload' => '#3355db'],

            // Theme Colors
            ['name' => 'primary_color', 'payload' => '#fff71a'],
            ['name' => 'secondary_color', 'payload' => '#6B7280'],
            ['name' => 'success_color', 'payload' => '#10B981'],
            ['name' => 'danger_color', 'payload' => '#EF4444'],
            ['name' => 'warning_color', 'payload' => '#F59E0B'],
            ['name' => 'info_color', 'payload' => '#0EA5E9'],

            // PUSHER
            ['name' => 'pusher_app_id', 'payload' => '1916347'],
            ['name' => 'pusher_app_key', 'payload' => '41ca495b13c5e75c1f91'],
            ['name' => 'pusher_app_secret', 'payload' => '6242f3214cf1f830b548'],
            ['name' => 'pusher_app_cluster', 'payload' => 'ap1'],

            // GOOGLE
            ['name' => 'google_client_id', 'payload' => ''],
            ['name' => 'google_client_secret', 'payload' => ''],
            ['name' => 'google_redirect_uri', 'payload' => 'http://127.0.0.1:8000/auth/google/callback'],

            // STRIPE
            ['name' => 'stripe_key', 'payload' => 'pk_test_...'],
            ['name' => 'stripe_secret', 'payload' => 'sk_test_...'],
            ['name' => 'stripe_webhook_secret', 'payload' => ''],

            // OPENAI
            ['name' => 'openai_api_url', 'payload' => 'https://api.openai.com/v1'],
            ['name' => 'openai_api_key', 'payload' => 'sk-proj-...'],

            // CLAUDE (add your own keys)
            ['name' => 'claude_api_url', 'payload' => null],
            ['name' => 'claude_api_key', 'payload' => null],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->insert([
                'group' => 'app',
                'name' => $setting['name'],
                'payload' => json_encode($setting['payload']),
                'locked' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};
