<?php

namespace Database\Seeders;

use App\Models\SystemConfig;
use Illuminate\Database\Seeder;

class SystemConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $configs = [
            // General settings
            ['key' => 'site_name', 'value' => 'MuscleHub', 'group' => 'general'],
            ['key' => 'site_description', 'value' => 'Laravel Muscle Hub Application', 'group' => 'general'],
            ['key' => 'site_logo', 'value' => 'logo.png', 'group' => 'general'],
            ['key' => 'site_favicon', 'value' => 'favicon.ico', 'group' => 'general'],
            ['key' => 'site_email', 'value' => 'contact@musclehub.com', 'group' => 'general'],
            ['key' => 'maintenance_mode', 'value' => 'off', 'group' => 'general'],
            // Email settings
            ['key' => 'mail_from_address', 'value' => 'info@musclehub.com', 'group' => 'email'],
            ['key' => 'mail_from_name', 'value' => 'MuscleHub', 'group' => 'email'],
            // Social media links
            ['key' => 'facebook_url', 'value' => 'https://facebook.com/musclehub', 'group' => 'social'],
            ['key' => 'twitter_url', 'value' => 'https://twitter.com/musclehub', 'group' => 'social'],
            ['key' => 'instagram_url', 'value' => 'https://instagram.com/musclehub', 'group' => 'social'],
            // Support information
            ['key' => 'support_phone', 'value' => '+1234567890', 'group' => 'support'],
            ['key' => 'support_telegram', 'value' => 'https://t.me/musclehub_support', 'group' => 'support'],
            // Dashboard and UI settings
            ['key' => 'active_dashboard', 'value' => 'blade', 'group' => 'ui'],
            ['key' => 'home_banner_text', 'value' => 'Welcome to MuscleHub - Your Fitness Partner', 'group' => 'ui'],
            ['key' => 'custom_css', 'value' => '', 'group' => 'ui'],
            ['key' => 'custom_js', 'value' => '', 'group' => 'ui'],
            // Analytics and tracking
            ['key' => 'analytics_enabled', 'value' => 'false', 'group' => 'analytics'],
            ['key' => 'google_analytics_id', 'value' => '', 'group' => 'analytics'],
            // API and integrations
            ['key' => 'notification_api_token', 'value' => '', 'group' => 'api'],
            ['key' => 'firebase_server_key', 'value' => '', 'group' => 'api'],
            ['key' => 'pusher_app_id', 'value' => '', 'group' => 'api'],
            ['key' => 'pusher_app_key', 'value' => '', 'group' => 'api'],
            ['key' => 'pusher_app_secret', 'value' => '', 'group' => 'api'],
            // Security settings
            ['key' => 'min_password_length', 'value' => '8', 'group' => 'security'],
            ['key' => 'require_password_confirmation', 'value' => 'true', 'group' => 'security'],
            ['key' => 'two_factor_enabled', 'value' => 'false', 'group' => 'security'],
            // E-commerce settings
            ['key' => 'allow_guest_checkout', 'value' => 'false', 'group' => 'ecommerce'],
            ['key' => 'default_currency', 'value' => 'USD', 'group' => 'ecommerce'],
            ['key' => 'tax_rate', 'value' => '0', 'group' => 'ecommerce'],
            ['key' => 'shipping_fee', 'value' => '0', 'group' => 'ecommerce'],
        ];

        foreach ($configs as $config) {
            SystemConfig::updateOrCreate(
                ['key' => $config['key']],
                ['value' => $config['value'], 'group' => $config['group']]
            );
        }
    }
}
