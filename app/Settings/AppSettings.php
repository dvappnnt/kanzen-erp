<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class AppSettings extends Settings
{
    public string $name;
    public string $description;
    public string $icon;
    public string $logo;
    public string $website;

    // Navigation Colors
    public string $navbar_bg_color;
    public string $navbar_text_color;
    public string $navbar_hover_bg_color;
    public string $navbar_hover_text_color;
    public string $navbar_active_bg_color;
    public string $navbar_active_text_color;

    // Sidebar Colors
    public string $sidebar_bg_color;
    public string $sidebar_text_color;
    public string $sidebar_hover_bg_color;
    public string $sidebar_hover_text_color;
    public string $sidebar_active_bg_color;
    public string $sidebar_active_text_color;

    // Button and Input Colors
    public string $button_primary_bg_color;
    public string $button_primary_text_color;
    public string $input_active_bg_color;

    // Theme Colors
    public string $primary_color;
    public string $secondary_color;
    public string $success_color;
    public string $danger_color;
    public string $warning_color;
    public string $info_color;

    // Pusher Configuration
    public ?string $pusher_app_id;
    public ?string $pusher_app_key;
    public ?string $pusher_app_secret;
    public ?string $pusher_app_cluster;

    // Google OAuth Configuration
    public ?string $google_client_id;
    public ?string $google_client_secret;
    public ?string $google_redirect_uri;

    // Stripe Configuration
    public ?string $stripe_key;
    public ?string $stripe_secret;
    public ?string $stripe_webhook_secret;

    // AI Configuration
    public ?string $openai_api_url;
    public ?string $openai_api_key;
    public ?string $claude_api_url;
    public ?string $claude_api_key;

    public static function group(): string
    {
        return 'app';
    }

    public function toArray(): array
    {
        return collect(parent::toArray())
            ->map(fn ($value) => $value === 'null' || $value === '' ? null : $value)
            ->toArray();
    }
}
