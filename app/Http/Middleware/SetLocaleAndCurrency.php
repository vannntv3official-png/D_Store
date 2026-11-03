<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleAndCurrency
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Primary locale is 'km', secondary is 'en'
        $locale = $request->session()->get('locale', config('app.locale', 'km'));
        if (!in_array($locale, ['km', 'en'], true)) {
            $locale = 'km';
        }
        app()->setLocale($locale);

        // Currencies: USD (base accounting) or KHR (display/settlement option)
        $currency = $request->session()->get('currency', 'USD');
        if (!in_array($currency, ['USD', 'KHR'], true)) {
            $currency = 'USD';
        }
        $request->session()->put('currency', $currency);

        return $next($request);
    }
}
