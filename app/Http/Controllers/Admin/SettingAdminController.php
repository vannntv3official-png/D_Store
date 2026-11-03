<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingAdminController extends Controller
{
    public function index(): Response
    {
        $settings = Setting::all()->pluck('value', 'key')->toArray();

        return Inertia::render('Admin/Settings/Index', [
            'settings' => [
                'store_name' => $settings['store_name'] ?? 'D STORE',
                'store_phone' => $settings['store_phone'] ?? '+855 23 888 999',
                'store_email' => $settings['store_email'] ?? 'contact@dstore.com.kh',
                'store_address_en' => $settings['store_address_en'] ?? 'Norodom Blvd, Phnom Penh, Cambodia',
                'store_address_km' => $settings['store_address_km'] ?? 'មហាវិថីព្រះនរោត្តម រាជធានីភ្នំពេញ ព្រះរាជាណាចក្រកម្ពុជា',
                'exchange_rate_khr_per_usd' => (int) ($settings['exchange_rate_khr_per_usd'] ?? 4100),
                'bank_name' => $settings['bank_name'] ?? 'ABA Bank Cambodia',
                'bank_account_name' => $settings['bank_account_name'] ?? 'D STORE CO., LTD.',
                'bank_account_usd' => $settings['bank_account_usd'] ?? '001 234 567',
                'bank_account_khr' => $settings['bank_account_khr'] ?? '001 234 568',
                'payment_cod_enabled' => filter_var($settings['payment_cod_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'payment_bank_enabled' => filter_var($settings['payment_bank_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'payment_khqr_enabled' => filter_var($settings['payment_khqr_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'payment_payway_enabled' => filter_var($settings['payment_payway_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_name' => ['required', 'string', 'max:255'],
            'store_phone' => ['required', 'string', 'max:64'],
            'store_email' => ['required', 'email', 'max:255'],
            'store_address_en' => ['required', 'string', 'max:500'],
            'store_address_km' => ['required', 'string', 'max:500'],
            'exchange_rate_khr_per_usd' => ['required', 'integer', 'min:3000', 'max:6000'],
            'bank_name' => ['required', 'string', 'max:255'],
            'bank_account_name' => ['required', 'string', 'max:255'],
            'bank_account_usd' => ['required', 'string', 'max:64'],
            'bank_account_khr' => ['required', 'string', 'max:64'],
            'payment_cod_enabled' => ['boolean'],
            'payment_bank_enabled' => ['boolean'],
            'payment_khqr_enabled' => ['boolean'],
            'payment_payway_enabled' => ['boolean'],
        ]);

        foreach ($validated as $key => $val) {
            Setting::set($key, $val);
        }

        return redirect()->back()->with('success', 'Store settings updated successfully.');
    }
}
