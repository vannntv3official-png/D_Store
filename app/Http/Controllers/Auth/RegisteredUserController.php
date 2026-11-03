<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Services\Loyalty\LoyaltyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Customer/Auth/Register');
    }

    public function store(Request $request, CartService $cartService, LoyaltyService $loyaltyService): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:32', 'unique:users,phone'],
            'email' => ['nullable', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'password' => Hash::make($validated['password']),
            'is_active' => true,
        ]);

        // Award welcome loyalty points
        try {
            $loyaltyService->getOrCreateAccount($user);
            $loyaltyService->awardRegistrationBonus($user);
        } catch (\Throwable $e) {
            // Log but don't halt user registration
        }

        Auth::login($user);

        // Merge guest cart if token exists
        $cartToken = $request->cookie('dstore_cart_token');
        if ($cartToken) {
            $cartService->mergeGuestCart($user, $cartToken);
        }

        return redirect()->intended(route('home'))->with('success', 'Welcome to D STORE! Your account has been created.');
    }
}
