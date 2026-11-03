<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'store_name', 'value' => 'D STORE', 'group' => 'general', 'type' => 'string', 'is_public' => true],
            ['key' => 'store_slogan', 'value' => json_encode(['en' => 'Discover More. Shop Better.', 'km' => 'ស្វែងរកកាន់តែច្រើន។ ទិញទំនិញកាន់តែប្រសើរ។']), 'group' => 'general', 'type' => 'json', 'is_public' => true],
            ['key' => 'base_currency', 'value' => 'USD', 'group' => 'localization', 'type' => 'string', 'is_public' => true],
            ['key' => 'display_currency', 'value' => 'KHR', 'group' => 'localization', 'type' => 'string', 'is_public' => true],
            ['key' => 'usd_khr_rate', 'value' => '4100', 'group' => 'localization', 'type' => 'number', 'is_public' => true],
            ['key' => 'store_timezone', 'value' => 'Asia/Phnom_Penh', 'group' => 'localization', 'type' => 'string', 'is_public' => true],
            ['key' => 'contact_email', 'value' => 'contact@dstore.com.kh', 'group' => 'contact', 'type' => 'string', 'is_public' => true],
            ['key' => 'contact_phone', 'value' => '+855 23 888 999', 'group' => 'contact', 'type' => 'string', 'is_public' => true],
            ['key' => 'contact_address', 'value' => 'Preah Monivong Blvd, Sangkat Boeung Keng Kang 1, Phnom Penh, Cambodia', 'group' => 'contact', 'type' => 'string', 'is_public' => true],

            // Loyalty settings
            ['key' => 'loyalty_registration_points', 'value' => '50', 'group' => 'loyalty', 'type' => 'number', 'is_public' => true],
            ['key' => 'loyalty_first_order_points', 'value' => '100', 'group' => 'loyalty', 'type' => 'number', 'is_public' => true],
            ['key' => 'loyalty_spend_point_ratio', 'value' => '1', 'group' => 'loyalty', 'type' => 'number', 'is_public' => true],
            ['key' => 'loyalty_review_points', 'value' => '10', 'group' => 'loyalty', 'type' => 'number', 'is_public' => true],
            ['key' => 'loyalty_lot_expiry_months', 'value' => '12', 'group' => 'loyalty', 'type' => 'number', 'is_public' => true],

            // Checkout & Reservations
            ['key' => 'reservation_ttl_minutes', 'value' => '30', 'group' => 'checkout', 'type' => 'number', 'is_public' => false],
            ['key' => 'cod_confirmation_deadline_hours', 'value' => '24', 'group' => 'checkout', 'type' => 'number', 'is_public' => false],

            // Payment Gateways Enabled
            ['key' => 'gateway_cod_enabled', 'value' => '1', 'group' => 'payment', 'type' => 'boolean', 'is_public' => true],
            ['key' => 'gateway_bank_transfer_enabled', 'value' => '1', 'group' => 'payment', 'type' => 'boolean', 'is_public' => true],
            ['key' => 'gateway_khqr_sandbox_enabled', 'value' => '1', 'group' => 'payment', 'type' => 'boolean', 'is_public' => true],
            ['key' => 'gateway_aba_payway_sandbox_enabled', 'value' => '1', 'group' => 'payment', 'type' => 'boolean', 'is_public' => true],

            // Bank instructions
            ['key' => 'bank_transfer_instructions', 'value' => json_encode([
                'bank_name' => 'ABA Bank',
                'account_name' => 'D STORE CO., LTD',
                'account_number_usd' => '001 888 777',
                'account_number_khr' => '001 888 778',
                'note' => 'Please include your Order Number as payment reference.',
            ]), 'group' => 'payment', 'type' => 'json', 'is_public' => true],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'group' => $setting['group'],
                    'type' => $setting['type'],
                    'is_public' => $setting['is_public'],
                ]
            );
        }
    }
}
