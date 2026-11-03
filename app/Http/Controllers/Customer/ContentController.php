<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\ContentPage;
use App\Models\NewsletterSubscriber;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContentController extends Controller
{
    public function page(string $slug): Response
    {
        $page = ContentPage::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return Inertia::render('Customer/Content/Page', [
            'page' => [
                'title_en' => $page->title['en'] ?? (is_array($page->title) ? reset($page->title) : (string) $page->title),
                'title_km' => $page->title['km'] ?? $page->title['en'] ?? '',
                'content_en' => $page->body['en'] ?? (is_array($page->body) ? reset($page->body) : (string) $page->body),
                'content_km' => $page->body['km'] ?? $page->body['en'] ?? '',
                'updated_at' => $page->updated_at->format('M d, Y'),
            ],
        ]);
    }

    public function contact(): Response
    {
        return Inertia::render('Customer/Content/Contact', [
            'storeInfo' => [
                'name' => Setting::get('store_name', 'D STORE'),
                'phone' => Setting::get('store_phone', '+855 23 888 999'),
                'email' => Setting::get('store_email', 'contact@dstore.com.kh'),
                'address_en' => Setting::get('store_address_en', 'Norodom Blvd, Phnom Penh, Cambodia'),
                'address_km' => Setting::get('store_address_km', 'មហាវិថីព្រះនរោត្តម រាជធានីភ្នំពេញ ព្រះរាជាណាចក្រកម្ពុជា'),
            ],
        ]);
    }

    public function submitContact(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:32'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:3000'],
        ]);

        ContactMessage::create([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'],
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'ip_address' => $request->ip(),
            'status' => 'new',
        ]);

        return redirect()->back()->with('success', 'Thank you for contacting D STORE. Our concierge team will respond shortly.');
    }

    public function subscribeNewsletter(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        NewsletterSubscriber::firstOrCreate(
            ['email' => strtolower(trim($validated['email']))],
            ['subscribed_at' => now(), 'is_active' => true]
        );

        return redirect()->back()->with('success', 'Thank you for subscribing to D STORE updates.');
    }

    public function sitemap(): \Illuminate\Http\Response
    {
        $baseUrl = url('/');
        $categories = \App\Models\Category::where('is_active', true)->get(['slug', 'updated_at']);
        $products = \App\Models\Product::where('status', 'active')->get(['slug', 'updated_at']);
        $pages = ContentPage::where('is_active', true)->get(['slug', 'updated_at']);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $xml .= "<url><loc>{$baseUrl}</loc><changefreq>daily</changefreq><priority>1.0</priority></url>";
        $xml .= "<url><loc>{$baseUrl}/shop</loc><changefreq>daily</changefreq><priority>0.9</priority></url>";
        $xml .= "<url><loc>{$baseUrl}/contact</loc><changefreq>monthly</changefreq><priority>0.5</priority></url>";

        foreach ($categories as $cat) {
            $lastmod = $cat->updated_at->toAtomString();
            $xml .= "<url><loc>{$baseUrl}/shop?category={$cat->slug}</loc><lastmod>{$lastmod}</lastmod><changefreq>weekly</changefreq><priority>0.8</priority></url>";
        }

        foreach ($products as $prod) {
            $lastmod = $prod->updated_at->toAtomString();
            $xml .= "<url><loc>{$baseUrl}/product/{$prod->slug}</loc><lastmod>{$lastmod}</lastmod><changefreq>daily</changefreq><priority>0.8</priority></url>";
        }

        foreach ($pages as $p) {
            $lastmod = $p->updated_at->toAtomString();
            $xml .= "<url><loc>{$baseUrl}/page/{$p->slug}</loc><lastmod>{$lastmod}</lastmod><changefreq>monthly</changefreq><priority>0.6</priority></url>";
        }

        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
