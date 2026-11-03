<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleCurrencyController extends Controller
{
    public function setLocale(Request $request, string $locale): RedirectResponse
    {
        if (in_array($locale, ['km', 'en'], true)) {
            $request->session()->put('locale', $locale);
        }

        return redirect()->back();
    }

    public function setCurrency(Request $request, string $currency): RedirectResponse
    {
        if (in_array($currency, ['USD', 'KHR'], true)) {
            $request->session()->put('currency', $currency);
        }

        return redirect()->back();
    }
}
