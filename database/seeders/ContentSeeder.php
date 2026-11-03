<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\ContentPage;
use App\Models\Coupon;
use App\Models\Reward;
use Illuminate\Database\Seeder;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Content Pages
        $pages = [
            'about' => [
                'title' => [
                    'en' => 'About D STORE',
                    'km' => 'អំពី ឌី ស្ត័រ (D STORE)',
                ],
                'body' => [
                    'en' => "<h2>Discover More. Shop Better.</h2><p>D STORE is Cambodia's premier destination for curated luxury fashion, high-performance electronics, and modern lifestyle essentials. Engineered with an unwavering commitment to authenticity, aesthetic restraint, and seamless digital service, D STORE brings world-class commerce directly to Phnom Penh and every province across the Kingdom.</p><p>Every product is backed by authentic origin guarantees, professional customer service, and flexible payment options including Bakong KHQR, ABA PayWay, and reliable Cash on Delivery.</p>",
                    'km' => "<h2>ស្វែងរកកាន់តែច្រើន។ ទិញទំនិញកាន់តែប្រសើរ។</h2><p>ឌី ស្ត័រ (D STORE) គឺជាគោលដៅទិញទំនិញអនឡាញឈានមុខគេនៅកម្ពុជា សម្រាប់សម្លៀកបំពាក់ប្រណិត គ្រឿងអេឡិចត្រូនិចទំនើប និងសម្ភារៈរស់នៅទាន់សម័យ។ យើងប្តេជ្ញាផ្តល់នូវផលិតផលសុទ្ធ ១០០% សេវាកម្មរហ័សទាន់ចិត្ត និងបទពិសោធន៍ទិញទំនិញប្រកបដោយទំនុកចិត្តខ្ពស់ទូទាំង ២៥ រាជធានី-ខេត្ត។</p><p>រាល់ការបញ្ជាទិញទទួលបានការធានាត្រឹមត្រូវ សេវាកម្មបំរើអតិថិជនរួសរាយរាក់ទាក់ និងជម្រើសទូទាត់ប្រាក់ងាយស្រួលតាមរយៈ បាគង KHQR, ABA PayWay និងសេវាទូទាត់ប្រាក់ពេលទទួលទំនិញ (COD)។</p>",
                ],
            ],
            'faq' => [
                'title' => [
                    'en' => 'Frequently Asked Questions',
                    'km' => 'សំណួរដែលសួរញឹកញាប់ (FAQ)',
                ],
                'body' => [
                    'en' => "<h3>How fast is delivery?</h3><p>Deliveries within Phnom Penh typically arrive within 1 to 2 business days. Deliveries to other provinces take between 2 to 3 business days via our verified courier partners.</p><h3>What payment methods are supported?</h3><p>We accept Bakong KHQR, ABA Bank Transfer, ABA PayWay, and Cash on Delivery (COD) for eligible items.</p><h3>Are all products original?</h3><p>Yes, all items sold on D STORE are 100% genuine and sourced directly from licensed distributors and brand partners.</p>",
                    'km' => "<h3>តើសេវាដឹកជញ្ជូនចំណាយពេលប៉ុន្មាន?</h3><p>ការដឹកជញ្ជូនក្នុងរាជធានីភ្នំពេញ ចំណាយពេល ១ ទៅ ២ ថ្ងៃនៃថ្ងៃធ្វើការ។ សម្រាប់ការដឹកជញ្ជូនទៅកាន់បណ្តាខេត្ត ចំណាយពេល ២ ទៅ ៣ ថ្ងៃតាមរយៈក្រុមហ៊ុនដៃគូដឹកជញ្ជូនផ្លូវការ។</p><h3>តើមានជម្រើសទូទាត់ប្រាក់អ្វីខ្លះ?</h3><p>លោកអ្នកអាចទូទាត់ប្រាក់តាមរយៈ បាគង KHQR, ផ្ទេរប្រាក់តាមធនាគារ ABA, ABA PayWay និងទូទាត់ពេលទទួលទំនិញ (COD)។</p><h3>តើទំនិញទាំងអស់ជាផលិតផលសុទ្ធដែរឬទេ?</h3><p>ពិតប្រាកដណាស់ ទំនិញទាំងអស់នៅលើ D STORE គឺសុទ្ធ ១០០% ដែលនាំចូលដោយផ្ទាល់ពីតំណាងចែកចាយស្របច្បាប់។</p>",
                ],
            ],
            'terms' => [
                'title' => [
                    'en' => 'Terms & Conditions',
                    'km' => 'លក្ខខណ្ឌនៃការប្រើប្រាស់',
                ],
                'body' => [
                    'en' => "<p>Welcome to D STORE. By accessing or using our website and services, you agree to be bound by these terms. All prices are listed in USD with convenient KHR conversion at current store exchange rates. Orders placed are subject to stock confirmation and payment authorization.</p>",
                    'km' => "<p>សូមស្វាគមន៍មកកាន់ D STORE។ តាមរយៈការចូលប្រើប្រាស់គេហទំព័រនេះ លោកអ្នកយល់ព្រមអនុវត្តតាមលក្ខខណ្ឌទាំងអស់នេះ។ តម្លៃទំនិញទាំងអស់គិតជាប្រាក់ដុល្លារអាមេរិក (USD) និងមានការគណនាបម្លែងជាប្រាក់រៀល (KHR) តាមអត្រាប្តូរប្រាក់ជាក់ស្តែង។</p>",
                ],
            ],
            'privacy' => [
                'title' => [
                    'en' => 'Privacy Policy',
                    'km' => 'គោលការណ៍ឯកជនភាព',
                ],
                'body' => [
                    'en' => "<p>At D STORE, your privacy and data security are our top priorities. We collect only necessary contact and delivery details to fulfill your orders and enhance your personalized shopping experience. We never sell or share your personal information with third parties without authorization.</p>",
                    'km' => "<p>នៅ D STORE សុវត្ថិភាព និងភាពឯកជននៃព័ត៌មានរបស់លោកអ្នកគឺជាអាទិភាពចម្បងរបស់យើង។ យើងប្រមូលតែព័ត៌មានចាំបាច់សម្រាប់ការដឹកជញ្ជូន និងការទូទាត់ប្រាក់ប៉ុណ្ណោះ ហើយមិនចែករំលែកទៅកាន់ភាគីទីបីដោយគ្មានការអនុញ្ញាតឡើយ។</p>",
                ],
            ],
        ];

        foreach ($pages as $slug => $data) {
            ContentPage::updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $data['title'],
                    'body' => $data['body'],
                    'is_active' => true,
                ]
            );
        }

        // 2. Hero Banners
        Banner::firstOrCreate(
            ['image_url' => '/images/banners/hero_pro_tech.jpg'],
            [
                'title' => [
                    'en' => 'The Next Era of Personal Technology',
                    'km' => 'យុគសម័យថ្មីនៃបច្ចេកវិទ្យាផ្ទាល់ខ្លួន',
                ],
                'subtitle' => [
                    'en' => 'Precision crafted flagship devices and luxury accessories designed for perfection.',
                    'km' => 'ឧបករណ៍ទំនើបចុងក្រោយបង្អស់ និងគ្រឿងបន្លាស់ប្រណិតដែលរចនាឡើងយ៉ាងឥតខ្ចោះ។',
                ],
                'link_url' => '/shop?category=smartphones',
                'button_text' => [
                    'en' => 'Explore Flagships',
                    'km' => 'ស្វែងយល់បន្ថែម',
                ],
                'display_order' => 1,
                'is_active' => true,
            ]
        );

        Banner::firstOrCreate(
            ['image_url' => '/images/banners/hero_minimal_fashion.jpg'],
            [
                'title' => [
                    'en' => 'Monochrome Minimalism & Timeless Tailoring',
                    'km' => 'ម៉ូដសម្លៀកបំពាក់ទាន់សម័យ និងប្រណិតភាព',
                ],
                'subtitle' => [
                    'en' => 'Curated wardrobe essentials crafted in pure cotton, silk, and structured wool.',
                    'km' => 'ការច្នៃម៉ូដដ៏ប្រណិតពីសាច់ក្រណាត់កប្បាស និងរោមចៀមគុណភាពខ្ពស់។',
                ],
                'link_url' => '/shop?category=fashion-clothing',
                'button_text' => [
                    'en' => 'Shop Collection',
                    'km' => 'ទិញការប្រមូលផ្ដុំ',
                ],
                'display_order' => 2,
                'is_active' => true,
            ]
        );

        // 3. Coupons
        Coupon::firstOrCreate(
            ['code' => 'WELCOME10'],
            [
                'name' => [
                    'en' => '10% Welcome Discount',
                    'km' => 'បញ្ចុះតម្លៃ ១០% ស្វាគមន៍សមាជិកថ្មី',
                ],
                'type' => 'percentage',
                'value' => 10.0000,
                'max_discount_amount' => 20.0000,
                'min_spend_amount' => 50.0000,
                'usage_limit_total' => 1000,
                'usage_limit_per_user' => 1,
                'is_active' => true,
            ]
        );

        Coupon::firstOrCreate(
            ['code' => 'DSTORE5'],
            [
                'name' => [
                    'en' => '$5 Off Any Order Over $30',
                    'km' => 'បញ្ចុះតម្លៃ $៥ រាល់ការបញ្ជាទិញចាប់ពី $៣០',
                ],
                'type' => 'fixed',
                'value' => 5.0000,
                'max_discount_amount' => 5.0000,
                'min_spend_amount' => 30.0000,
                'usage_limit_total' => 500,
                'usage_limit_per_user' => 2,
                'is_active' => true,
            ]
        );

        // 4. Loyalty Rewards
        Reward::firstOrCreate(
            ['name->en' => '$5 Store Voucher'],
            [
                'name' => [
                    'en' => '$5 Store Voucher',
                    'km' => 'ប័ណ្ណទូទាត់ប្រាក់ $៥',
                ],
                'description' => [
                    'en' => 'Redeem 100 points for a $5 discount on your next purchase.',
                    'km' => 'ប្តូរពិន្ទុ ១០០ ដើម្បីទទួលបានការបញ្ចុះតម្លៃ $៥ សម្រាប់ការទិញលើកក្រោយ។',
                ],
                'points_cost' => 100,
                'discount_type' => 'fixed',
                'discount_value' => 5.0000,
                'min_spend_amount' => 25.0000,
                'is_active' => true,
            ]
        );

        Reward::firstOrCreate(
            ['name->en' => '$15 Premium Voucher'],
            [
                'name' => [
                    'en' => '$15 Premium Voucher',
                    'km' => 'ប័ណ្ណទូទាត់ប្រាក់ $១៥ ប្រណិត',
                ],
                'description' => [
                    'en' => 'Redeem 250 points for a $15 discount on orders over $60.',
                    'km' => 'ប្តូរពិន្ទុ ២៥០ ដើម្បីទទួលបានការបញ្ចុះតម្លៃ $១៥ សម្រាប់ការបញ្ជាទិញលើសពី $៦០។',
                ],
                'points_cost' => 250,
                'discount_type' => 'fixed',
                'discount_value' => 15.0000,
                'min_spend_amount' => 60.0000,
                'is_active' => true,
            ]
        );
    }
}
