<?php

namespace Database\Seeders;

use App\Models\AttributeDefinition;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ProductVariantAttribute;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoProductSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Brands
        $brands = [
            'Apple' => Brand::firstOrCreate(['slug' => 'apple'], ['name' => 'Apple', 'is_active' => true]),
            'Sony' => Brand::firstOrCreate(['slug' => 'sony'], ['name' => 'Sony', 'is_active' => true]),
            'Samsung' => Brand::firstOrCreate(['slug' => 'samsung'], ['name' => 'Samsung', 'is_active' => true]),
            'D Studio' => Brand::firstOrCreate(['slug' => 'd-studio'], ['name' => 'D Studio Paris', 'is_active' => true]),
            'Honda' => Brand::firstOrCreate(['slug' => 'honda'], ['name' => 'Honda Motor', 'is_active' => true]),
            'Yamaha' => Brand::firstOrCreate(['slug' => 'yamaha'], ['name' => 'Yamaha Motors', 'is_active' => true]),
            'Razer' => Brand::firstOrCreate(['slug' => 'razer'], ['name' => 'Razer', 'is_active' => true]),
        ];

        // 2. Fetch Categories & Attributes
        $categories = Category::all()->keyBy('slug');
        $attributes = AttributeDefinition::with('options')->get()->keyBy('code');

        $productsData = [
            // Product 1: Smartphone Flagship
            [
                'category' => 'smartphones',
                'brand' => 'Apple',
                'name' => [
                    'en' => 'Horizon Ultra Titanium 5G',
                    'km' => 'ទូរស័ព្ទ Horizon Ultra Titanium 5G',
                ],
                'slug' => 'horizon-ultra-titanium-5g',
                'short_description' => [
                    'en' => 'Aerospace-grade titanium design with Super Retina XDR Pro display and next-gen silicon.',
                    'km' => 'រចនាឡើងពីទីតានីញ៉ូមកម្រិតខ្ពស់ អេក្រង់ Super Retina XDR Pro និងបន្ទះឈីបជំនាន់ថ្មី។',
                ],
                'description' => [
                    'en' => '<p>The Horizon Ultra Titanium sets a new standard for performance, engineering, and mobile photography. Featuring an advanced 48MP main sensor, 5x telephoto optical zoom, and all-day battery efficiency.</p>',
                    'km' => '<p>Horizon Ultra Titanium បង្កើតស្តង់ដារថ្មីសម្រាប់ប្រសិទ្ធភាពការងារ និងការថតរូបប្រកបដោយគុណភាពខ្ពស់។ បំពាក់ដោយសេនស័រកាមេរ៉ាទំហំ 48MP និងកញ្ចក់ពង្រីក 5x Optical Zoom។</p>',
                ],
                'status' => 'active',
                'is_featured' => true,
                'images' => [
                    'https://images.unsplash.com/photo-1592750475338-74b7b21085ab?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=800&q=80',
                ],
                'variants' => [
                    [
                        'sku' => 'HZN-ULT-BLK-256',
                        'price' => 1199.0000,
                        'compare_at_price' => 1299.0000,
                        'is_default' => true,
                        'stock' => 18,
                        'attributes' => ['color' => 'black', 'storage' => '256gb'],
                    ],
                    [
                        'sku' => 'HZN-ULT-SLV-256',
                        'price' => 1199.0000,
                        'compare_at_price' => 1299.0000,
                        'is_default' => false,
                        'stock' => 15,
                        'attributes' => ['color' => 'silver', 'storage' => '256gb'],
                    ],
                    [
                        'sku' => 'HZN-ULT-GLD-512',
                        'price' => 1399.0000,
                        'compare_at_price' => 1499.0000,
                        'is_default' => false,
                        'stock' => 10,
                        'attributes' => ['color' => 'gold', 'storage' => '512gb'],
                    ],
                ],
            ],

            // Product 2: Computers
            [
                'category' => 'computers',
                'brand' => 'Apple',
                'name' => [
                    'en' => 'Zenith Pro 16-Inch Studio Laptop',
                    'km' => 'កុំព្យូទ័រយួរដៃ Zenith Pro 16 អ៊ីង',
                ],
                'slug' => 'zenith-pro-16-inch-studio-laptop',
                'short_description' => [
                    'en' => 'Boundless processing power for creative pros, developers, and computational workloads.',
                    'km' => 'កម្លាំងម៉ាស៊ីនខ្លាំងគ្មានដែនកំណត់ សម្រាប់អ្នកជំនាញឌីហ្សាញ អ្នកសរសេរកូដ និងការងារធ្ងន់ៗ។',
                ],
                'description' => [
                    'en' => '<p>Engineered with liquid retina XDR, active thermal dynamics, and exceptional battery life up to 22 hours.</p>',
                    'km' => '<p>រចនាឡើងជាមួយអេក្រង់ Liquid Retina XDR ប្រព័ន្ធត្រជាក់ឆ្លាតវៃ និងថាមពលថ្មប្រើប្រាស់បានយូររហូតដល់ ២២ ម៉ោង។</p>',
                ],
                'status' => 'active',
                'is_featured' => true,
                'images' => [
                    'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1611186871348-b1ce696e52c9?auto=format&fit=crop&w=800&q=80',
                ],
                'variants' => [
                    [
                        'sku' => 'ZNT-PRO-16-M3-32',
                        'price' => 2499.0000,
                        'compare_at_price' => 2699.0000,
                        'is_default' => true,
                        'stock' => 8,
                        'attributes' => ['color' => 'space_gray', 'ram' => '32gb', 'storage' => '512gb'],
                    ],
                    [
                        'sku' => 'ZNT-PRO-16-M4-64',
                        'price' => 3299.0000,
                        'compare_at_price' => null,
                        'is_default' => false,
                        'stock' => 5,
                        'attributes' => ['color' => 'silver', 'ram' => '64gb', 'storage' => '1tb'],
                    ],
                ],
            ],

            // Product 3: Electronics / Headphones
            [
                'category' => 'electronics',
                'brand' => 'Sony',
                'name' => [
                    'en' => 'Acoustic Horizon ANC Headphones',
                    'km' => 'កាសស្ដាប់ Acoustic Horizon ANC កាត់បន្ថយសំឡេងរំខាន',
                ],
                'slug' => 'acoustic-horizon-anc-headphones',
                'short_description' => [
                    'en' => 'Class-leading active noise cancellation with lossless studio-grade acoustics.',
                    'km' => 'បច្ចេកវិទ្យាកាត់បន្ថយសំឡេងរំខានកម្រិតកំពូល ជាមួយសម្លេងច្បាស់ពិរោះដូចក្នុងស្ទូឌីយោ។',
                ],
                'description' => [
                    'en' => '<p>Crafted with ultra-soft memory foam earcups, intuitive capacitive touch controls, and high-resolution spatial audio.</p>',
                    'km' => '<p>ស្រោមកាសធ្វើពីអេប៉ុងទន់ពិសេស មិនឈឺត្រចៀក និងមានប្រព័ន្ធបញ្ជាតាមរយៈការប៉ះយ៉ាងរលូន។</p>',
                ],
                'status' => 'active',
                'is_featured' => true,
                'images' => [
                    'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80',
                ],
                'variants' => [
                    [
                        'sku' => 'ACH-ANC-BLK',
                        'price' => 349.0000,
                        'compare_at_price' => 399.0000,
                        'is_default' => true,
                        'stock' => 25,
                        'attributes' => ['color' => 'black'],
                    ],
                    [
                        'sku' => 'ACH-ANC-SLV',
                        'price' => 349.0000,
                        'compare_at_price' => 399.0000,
                        'is_default' => false,
                        'stock' => 20,
                        'attributes' => ['color' => 'silver'],
                    ],
                ],
            ],

            // Product 4: Fashion & Clothing
            [
                'category' => 'fashion-clothing',
                'brand' => 'D Studio',
                'name' => [
                    'en' => 'Minimalist Structured Wool Blazer',
                    'km' => 'អាវធំរោមចៀមម៉ូដទាន់សម័យ Minimalist',
                ],
                'slug' => 'minimalist-structured-wool-blazer',
                'short_description' => [
                    'en' => 'Tailored from virgin double-faced wool with a relaxed silhouette and clean horn buttons.',
                    'km' => 'កាត់ដេរយ៉ាងផ្ចិតផ្ចង់ពីរោមចៀមសុទ្ធ រូបរាងទាន់សម័យ និងផាសុកភាពខ្ពស់។',
                ],
                'description' => [
                    'en' => '<p>An effortless wardrobe cornerstone designed for both formal gatherings and contemporary evening wear.</p>',
                    'km' => '<p>សាកសមឥតខ្ចោះសម្រាប់ទាំងការងារផ្លូវការ និងការដើរកម្សាន្តពេលល្ងាច។</p>',
                ],
                'status' => 'active',
                'is_featured' => true,
                'images' => [
                    'https://images.unsplash.com/photo-1591047139829-d91aecb6caea?auto=format&fit=crop&w=800&q=80',
                ],
                'variants' => [
                    [
                        'sku' => 'DS-BLZ-BLK-M',
                        'price' => 280.0000,
                        'compare_at_price' => 320.0000,
                        'is_default' => true,
                        'stock' => 12,
                        'attributes' => ['color' => 'black', 'size' => 'm'],
                    ],
                    [
                        'sku' => 'DS-BLZ-BLK-L',
                        'price' => 280.0000,
                        'compare_at_price' => 320.0000,
                        'is_default' => false,
                        'stock' => 14,
                        'attributes' => ['color' => 'black', 'size' => 'l'],
                    ],
                    [
                        'sku' => 'DS-BLZ-NVY-M',
                        'price' => 280.0000,
                        'compare_at_price' => 320.0000,
                        'is_default' => false,
                        'stock' => 10,
                        'attributes' => ['color' => 'navy', 'size' => 'm'],
                    ],
                ],
            ],

            // Product 5: Shoes & Bags
            [
                'category' => 'shoes-bags',
                'brand' => 'D Studio',
                'name' => [
                    'en' => 'Monochrome Italian Leather Tote Bag',
                    'km' => 'កាបូបស្បែកអ៊ីតាលី Monochrome Tote',
                ],
                'slug' => 'monochrome-italian-leather-tote-bag',
                'short_description' => [
                    'en' => 'Full-grain vegetable-tanned leather tote with reinforced handles and laptop compartment.',
                    'km' => 'ផលិតពីស្បែកគោពិត 100% ធន់រឹងមាំ និងមានថតដាក់កុំព្យូទ័រយួរដៃយ៉ាងមានសុវត្ថិភាព។',
                ],
                'description' => [
                    'en' => '<p>Constructed in Florence using traditional artisanal methods. Features a dedicated 16-inch padded laptop pocket and magnetic closure.</p>',
                    'km' => '<p>ច្នៃម៉ូដដោយជាងជំនាញជនជាតិអ៊ីតាលី រូបរាងប្រណិត ងាយស្រួលដាក់សម្ភារៈប្រចាំថ្ងៃ។</p>',
                ],
                'status' => 'active',
                'is_featured' => true,
                'images' => [
                    'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?auto=format&fit=crop&w=800&q=80',
                ],
                'variants' => [
                    [
                        'sku' => 'DS-TOT-BLK',
                        'price' => 220.0000,
                        'compare_at_price' => 260.0000,
                        'is_default' => true,
                        'stock' => 15,
                        'attributes' => ['color' => 'black'],
                    ],
                    [
                        'sku' => 'DS-TOT-WHT',
                        'price' => 220.0000,
                        'compare_at_price' => 260.0000,
                        'is_default' => false,
                        'stock' => 10,
                        'attributes' => ['color' => 'white'],
                    ],
                ],
            ],

            // Product 6: Cars & Motorcycles (Physical stock items)
            [
                'category' => 'cars-motorcycles',
                'brand' => 'Honda',
                'name' => [
                    'en' => 'Honda Urban Sport 150cc Motorcycle (Physical Stock)',
                    'km' => 'ម៉ូតូ Honda Urban Sport 150cc (មានស្តុកស្រាប់)',
                ],
                'slug' => 'honda-urban-sport-150cc-motorcycle',
                'short_description' => [
                    'en' => 'In-stock physical unit with official import tax documentation and showroom delivery.',
                    'km' => 'ម៉ូតូថ្មីមានស្តុកស្រាប់ មានពន្ធនាំចូលត្រឹមត្រូវ និងដឹកជញ្ជូនដល់គេហដ្ឋាន។',
                ],
                'description' => [
                    'en' => '<p>Physical stock motorcycle ready for immediate courier dispatch in Phnom Penh. Fuel-injected 150cc engine, front ABS braking system, and full factory warranty.</p>',
                    'km' => '<p>ម៉ូតូស្តុកថ្មី រួចរាល់សម្រាប់ការដឹកជញ្ជូនភ្លាមៗ។ ម៉ាស៊ីនប៊ិច 150cc សន្សំសំចៃសាំង និងប្រព័ន្ធហ្វ្រាំង ABS សុវត្ថិភាពខ្ពស់។</p>',
                ],
                'status' => 'active',
                'is_featured' => true,
                'images' => [
                    'https://images.unsplash.com/photo-1558981403-c5f9899a28bc?auto=format&fit=crop&w=800&q=80',
                ],
                'variants' => [
                    [
                        'sku' => 'HND-USP-150-BLK-2025',
                        'price' => 3150.0000,
                        'compare_at_price' => 3300.0000,
                        'is_default' => true,
                        'stock' => 4,
                        'attributes' => ['color' => 'black', 'engine_cc' => '150cc', 'vehicle_year' => '2025'],
                    ],
                    [
                        'sku' => 'HND-USP-150-SLV-2025',
                        'price' => 3150.0000,
                        'compare_at_price' => 3300.0000,
                        'is_default' => false,
                        'stock' => 3,
                        'attributes' => ['color' => 'silver', 'engine_cc' => '150cc', 'vehicle_year' => '2025'],
                    ],
                ],
            ],

            // Product 7: Gaming
            [
                'category' => 'gaming',
                'brand' => 'Razer',
                'name' => [
                    'en' => 'Vortex Pro Wireless Gaming Controller',
                    'km' => 'ដៃហ្គេមឥតខ្សែ Vortex Pro Wireless',
                ],
                'slug' => 'vortex-pro-wireless-gaming-controller',
                'short_description' => [
                    'en' => 'Hall-effect magnetic triggers, mechanical switches, and ultra-low latency 2.4GHz wireless.',
                    'km' => 'ប្រព័ន្ធ Trigger មេដែក Hall-effect ស្វិតធន់ និងបច្ចេកវិទ្យាឥតខ្សែ latency ទាបបំផុត។',
                ],
                'description' => [
                    'en' => '<p>Designed for competitive esports with remappable rear paddles, swappable thumbsticks, and custom profile switching.</p>',
                    'km' => '<p>រចនាឡើងសម្រាប់ការប្រកួតហ្គេមកម្រិតខ្ពស់ អាចកំណត់ប៊ូតុងខាងក្រោយតាមតម្រូវការបានយ៉ាងងាយ។</p>',
                ],
                'status' => 'active',
                'is_featured' => true,
                'images' => [
                    'https://images.unsplash.com/photo-1600080972464-8e5f35f63d08?auto=format&fit=crop&w=800&q=80',
                ],
                'variants' => [
                    [
                        'sku' => 'VTX-CTR-BLK',
                        'price' => 129.0000,
                        'compare_at_price' => 149.0000,
                        'is_default' => true,
                        'stock' => 30,
                        'attributes' => ['color' => 'black'],
                    ],
                    [
                        'sku' => 'VTX-CTR-WHT',
                        'price' => 129.0000,
                        'compare_at_price' => 149.0000,
                        'is_default' => false,
                        'stock' => 20,
                        'attributes' => ['color' => 'white'],
                    ],
                ],
            ],
        ];

        foreach ($productsData as $prod) {
            $cat = $categories[$prod['category']] ?? null;
            $brand = $brands[$prod['brand']] ?? null;

            if (!$cat) {
                continue;
            }

            $product = Product::firstOrCreate(
                ['slug' => $prod['slug']],
                [
                    'category_id' => $cat->id,
                    'brand_id' => $brand?->id,
                    'name' => $prod['name'],
                    'short_description' => $prod['short_description'],
                    'description' => $prod['description'],
                    'status' => $prod['status'],
                    'is_featured' => $prod['is_featured'],
                    'warranty_information' => '1 Year Official Warranty',
                ]
            );

            // Images
            foreach ($prod['images'] as $idx => $imgUrl) {
                ProductImage::firstOrCreate(
                    [
                        'product_id' => $product->id,
                        'image_url' => $imgUrl,
                    ],
                    [
                        'display_order' => $idx,
                        'is_primary' => $idx === 0,
                    ]
                );
            }

            // Variants & Inventory
            foreach ($prod['variants'] as $varData) {
                $variant = ProductVariant::firstOrCreate(
                    ['sku' => $varData['sku']],
                    [
                        'product_id' => $product->id,
                        'barcode' => '885' . rand(100000000, 999999999),
                        'price' => $varData['price'],
                        'compare_at_price' => $varData['compare_at_price'],
                        'is_default' => $varData['is_default'],
                        'is_active' => true,
                        'low_stock_threshold' => 5,
                    ]
                );

                // Variant Attributes
                foreach ($varData['attributes'] as $attrCode => $optVal) {
                    $attrDef = $attributes[$attrCode] ?? null;
                    if ($attrDef) {
                        $opt = $attrDef->options->where('value', $optVal)->first();
                        ProductVariantAttribute::firstOrCreate(
                            [
                                'product_variant_id' => $variant->id,
                                'attribute_definition_id' => $attrDef->id,
                            ],
                            [
                                'attribute_option_id' => $opt?->id,
                                'text_value' => $optVal,
                            ]
                        );
                    }
                }

                // Inventory & Initial Movement
                $inv = Inventory::firstOrCreate(
                    ['product_variant_id' => $variant->id],
                    [
                        'on_hand_quantity' => $varData['stock'],
                        'reserved_quantity' => 0,
                    ]
                );

                InventoryMovement::firstOrCreate(
                    [
                        'product_variant_id' => $variant->id,
                        'reason' => 'initial_stock',
                    ],
                    [
                        'quantity_change' => $varData['stock'],
                        'resulting_on_hand' => $varData['stock'],
                        'resulting_reserved' => 0,
                        'operation_reference' => 'INITIAL_SEED_' . $variant->sku,
                        'notes' => 'Initial warehouse seed inventory',
                    ]
                );
            }
        }
    }
}
