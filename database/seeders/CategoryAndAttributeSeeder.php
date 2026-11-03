<?php

namespace Database\Seeders;

use App\Models\AttributeDefinition;
use App\Models\AttributeOption;
use App\Models\Category;
use Illuminate\Database\Seeder;

class CategoryAndAttributeSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Attribute Definitions & Options
        $attributesData = [
            'color' => [
                'name' => ['en' => 'Color', 'km' => 'ពណ៌'],
                'type' => 'select',
                'is_filterable' => true,
                'is_variant_defining' => true,
                'options' => [
                    'black' => ['en' => 'Midnight Black', 'km' => 'ខ្មៅអធ្រាត្រ'],
                    'white' => ['en' => 'Starlight White', 'km' => 'សភ្លឺ'],
                    'space_gray' => ['en' => 'Space Gray', 'km' => 'ប្រផេះអវកាស'],
                    'silver' => ['en' => 'Titanium Silver', 'km' => 'ប្រាក់ទីតានីញ៉ូម'],
                    'gold' => ['en' => 'Desert Gold', 'km' => 'មាសវាលខ្សាច់'],
                    'navy' => ['en' => 'Deep Navy', 'km' => 'ខៀវចាស់'],
                ],
            ],
            'size' => [
                'name' => ['en' => 'Size', 'km' => 'ទំហំ'],
                'type' => 'select',
                'is_filterable' => true,
                'is_variant_defining' => true,
                'options' => [
                    'xs' => ['en' => 'XS', 'km' => 'XS'],
                    's' => ['en' => 'S', 'km' => 'S'],
                    'm' => ['en' => 'M', 'km' => 'M'],
                    'l' => ['en' => 'L', 'km' => 'L'],
                    'xl' => ['en' => 'XL', 'km' => 'XL'],
                    'xxl' => ['en' => 'XXL', 'km' => 'XXL'],
                ],
            ],
            'shoe_size' => [
                'name' => ['en' => 'Shoe Size (EU)', 'km' => 'ទំហំស្បែកជើង (EU)'],
                'type' => 'select',
                'is_filterable' => true,
                'is_variant_defining' => true,
                'options' => [
                    '39' => ['en' => '39 EU', 'km' => '39 EU'],
                    '40' => ['en' => '40 EU', 'km' => '40 EU'],
                    '41' => ['en' => '41 EU', 'km' => '41 EU'],
                    '42' => ['en' => '42 EU', 'km' => '42 EU'],
                    '43' => ['en' => '43 EU', 'km' => '43 EU'],
                    '44' => ['en' => '44 EU', 'km' => '44 EU'],
                ],
            ],
            'storage' => [
                'name' => ['en' => 'Storage Capacity', 'km' => 'ទំហំផ្ទុក'],
                'type' => 'select',
                'is_filterable' => true,
                'is_variant_defining' => true,
                'options' => [
                    '128gb' => ['en' => '128 GB', 'km' => '128 GB'],
                    '256gb' => ['en' => '256 GB', 'km' => '256 GB'],
                    '512gb' => ['en' => '512 GB', 'km' => '512 GB'],
                    '1tb' => ['en' => '1 TB', 'km' => '1 TB'],
                ],
            ],
            'ram' => [
                'name' => ['en' => 'Memory (RAM)', 'km' => 'អង្គចងចាំ (RAM)'],
                'type' => 'select',
                'is_filterable' => true,
                'is_variant_defining' => true,
                'options' => [
                    '8gb' => ['en' => '8 GB', 'km' => '8 GB'],
                    '16gb' => ['en' => '16 GB', 'km' => '16 GB'],
                    '32gb' => ['en' => '32 GB', 'km' => '32 GB'],
                    '64gb' => ['en' => '64 GB', 'km' => '64 GB'],
                ],
            ],
            'cpu' => [
                'name' => ['en' => 'Processor (CPU)', 'km' => 'ប្រព័ន្ធដំណើរការ (CPU)'],
                'type' => 'select',
                'is_filterable' => true,
                'is_variant_defining' => false,
                'options' => [
                    'm3' => ['en' => 'Apple M3 Pro', 'km' => 'Apple M3 Pro'],
                    'm4' => ['en' => 'Apple M4 Max', 'km' => 'Apple M4 Max'],
                    'core_i7' => ['en' => 'Intel Core i7 14th Gen', 'km' => 'Intel Core i7 14th Gen'],
                    'core_i9' => ['en' => 'Intel Core i9 14th Gen', 'km' => 'Intel Core i9 14th Gen'],
                ],
            ],
            'engine_cc' => [
                'name' => ['en' => 'Engine Displacement', 'km' => 'កម្លាំងម៉ាស៊ីន (CC)'],
                'type' => 'select',
                'is_filterable' => true,
                'is_variant_defining' => true,
                'options' => [
                    '125cc' => ['en' => '125 CC', 'km' => '125 CC'],
                    '150cc' => ['en' => '150 CC', 'km' => '150 CC'],
                    '300cc' => ['en' => '300 CC', 'km' => '300 CC'],
                    '500cc' => ['en' => '500 CC', 'km' => '500 CC'],
                ],
            ],
            'vehicle_year' => [
                'name' => ['en' => 'Model Year', 'km' => 'ឆ្នាំផលិត'],
                'type' => 'select',
                'is_filterable' => true,
                'is_variant_defining' => true,
                'options' => [
                    '2024' => ['en' => '2024', 'km' => '2024'],
                    '2025' => ['en' => '2025', 'km' => '2025'],
                    '2026' => ['en' => '2026', 'km' => '2026'],
                ],
            ],
            'warranty' => [
                'name' => ['en' => 'Warranty Coverage', 'km' => 'ការធានា'],
                'type' => 'select',
                'is_filterable' => true,
                'is_variant_defining' => false,
                'options' => [
                    '6m' => ['en' => '6 Months Official Warranty', 'km' => 'ធានាផ្លូវការ ៦ ខែ'],
                    '1y' => ['en' => '1 Year Official Warranty', 'km' => 'ធានាផ្លូវការ ១ ឆ្នាំ'],
                    '2y' => ['en' => '2 Years Official Warranty', 'km' => 'ធានាផ្លូវការ ២ ឆ្នាំ'],
                ],
            ],
        ];

        $createdAttributes = [];
        foreach ($attributesData as $code => $data) {
            $attr = AttributeDefinition::firstOrCreate(
                ['code' => $code],
                [
                    'name' => $data['name'],
                    'type' => $data['type'],
                    'is_filterable' => $data['is_filterable'],
                    'is_variant_defining' => $data['is_variant_defining'],
                ]
            );

            foreach ($data['options'] as $val => $lbl) {
                AttributeOption::firstOrCreate(
                    [
                        'attribute_definition_id' => $attr->id,
                        'value' => $val,
                    ],
                    [
                        'label' => $lbl,
                    ]
                );
            }

            $createdAttributes[$code] = $attr;
        }

        // 2. The 11 Required Seed Categories
        $categoriesData = [
            'fashion-clothing' => [
                'name' => ['en' => 'Fashion & Clothing', 'km' => 'សម្លៀកបំពាក់ & ម៉ូដ'],
                'desc' => ['en' => 'Luxury apparel, designer jackets, shirts, and timeless wardrobe staples.', 'km' => 'សម្លៀកបំពាក់ប្រណិត អាវធំ និងម៉ូដទាន់សម័យបំផុត។'],
                'image' => '/images/categories/fashion.jpg',
                'order' => 1,
                'featured' => true,
                'attributes' => ['color', 'size'],
            ],
            'smartphones' => [
                'name' => ['en' => 'Smartphones', 'km' => 'ទូរស័ព្ទដៃ'],
                'desc' => ['en' => 'Flagship smartphones with industry-leading cameras, displays, and processors.', 'km' => 'ទូរស័ព្ទទំនើបចុងក្រោយបង្អស់ ជាមួយកាមេរ៉ា និងអេក្រង់ច្បាស់កម្រិតខ្ពស់។'],
                'image' => '/images/categories/smartphones.jpg',
                'order' => 2,
                'featured' => true,
                'attributes' => ['color', 'storage', 'warranty'],
            ],
            'computers' => [
                'name' => ['en' => 'Computers', 'km' => 'កុំព្យូទ័រ'],
                'desc' => ['en' => 'High-performance laptops, desktop workstations, and creative powerhouses.', 'km' => 'កុំព្យូទ័រយួរដៃ និងកុំព្យូទ័រលើតុសម្រាប់បំពេញការងារ និងការកម្សាន្ត។'],
                'image' => '/images/categories/computers.jpg',
                'order' => 3,
                'featured' => true,
                'attributes' => ['color', 'ram', 'storage', 'cpu', 'warranty'],
            ],
            'electronics' => [
                'name' => ['en' => 'Electronics', 'km' => 'គ្រឿងអេឡិចត្រូនិច'],
                'desc' => ['en' => 'Smart audio systems, high-fidelity headphones, and personal technology.', 'km' => 'ឧបករណ៍បំពងសំឡេង កាសស្ដាប់ និងគ្រឿងអេឡិចត្រូនិចទំនើបៗ។'],
                'image' => '/images/categories/electronics.jpg',
                'order' => 4,
                'featured' => true,
                'attributes' => ['color', 'warranty'],
            ],
            'shoes-bags' => [
                'name' => ['en' => 'Shoes & Bags', 'km' => 'ស្បែកជើង & កាបូប'],
                'desc' => ['en' => 'Handcrafted leather goods, luxury travel bags, and artisan footwear.', 'km' => 'ស្បែកជើងប្រណិត និងកាបូបស្បែកពិតគុណភាពខ្ពស់។'],
                'image' => '/images/categories/shoes-bags.jpg',
                'order' => 5,
                'featured' => true,
                'attributes' => ['color', 'shoe_size'],
            ],
            'beauty-cosmetics' => [
                'name' => ['en' => 'Beauty & Cosmetics', 'km' => 'សម្រស់ & គ្រឿងសម្អាង'],
                'desc' => ['en' => 'Premium skincare, perfumes, and dermatologist-tested cosmetic formulas.', 'km' => 'ផលិតផលថែរក្សាស្បែក ទឹកអប់ និងគ្រឿងសម្អាងម៉ាកល្បីៗ។'],
                'image' => '/images/categories/beauty.jpg',
                'order' => 6,
                'featured' => true,
                'attributes' => [],
            ],
            'home-living' => [
                'name' => ['en' => 'Home & Living', 'km' => 'គេហដ្ឋាន & ការរស់នៅ'],
                'desc' => ['en' => 'Minimalist home furniture, designer lighting, and kitchen essentials.', 'km' => 'គ្រឿងសង្ហារិមតុបតែងគេហដ្ឋាន និងសម្ភារៈផ្ទះបាយទំនើប។'],
                'image' => '/images/categories/home.jpg',
                'order' => 7,
                'featured' => false,
                'attributes' => ['color'],
            ],
            'cars-motorcycles' => [
                'name' => ['en' => 'Cars & Motorcycles', 'km' => 'ឡាន & ម៉ូតូ'],
                'desc' => ['en' => 'Physical stock vehicles and motorcycles delivered with verified provenance.', 'km' => 'យានយន្ត និងម៉ូតូដែលមានស្តុកជាក់ស្តែង គុណភាពខ្ពស់។'],
                'image' => '/images/categories/vehicles.jpg',
                'order' => 8,
                'featured' => true,
                'attributes' => ['color', 'engine_cc', 'vehicle_year', 'warranty'],
            ],
            'gaming' => [
                'name' => ['en' => 'Gaming', 'km' => 'ហ្គេម & ឧបករណ៍លេង'],
                'desc' => ['en' => 'Next-gen gaming consoles, mechanical keyboards, and precision peripherals.', 'km' => 'ម៉ាស៊ីនហ្គេមទំនើប និងឧបករណ៍លេងហ្គេមកម្រិតអាជីព។'],
                'image' => '/images/categories/gaming.jpg',
                'order' => 9,
                'featured' => true,
                'attributes' => ['color', 'warranty'],
            ],
            'accessories' => [
                'name' => ['en' => 'Accessories', 'km' => 'គ្រឿងបន្លាស់ & គ្រឿងតុបតែង'],
                'desc' => ['en' => 'Luxury watches, minimalist sunglasses, leather wallets, and everyday carry.', 'km' => 'នាឡិកាដៃ វ៉ែនតា និងគ្រឿងតុបតែងខ្លួនប្រណិត។'],
                'image' => '/images/categories/accessories.jpg',
                'order' => 10,
                'featured' => false,
                'attributes' => ['color'],
            ],
            'other-products' => [
                'name' => ['en' => 'Other Products', 'km' => 'ផលិតផលផ្សេងៗ'],
                'desc' => ['en' => 'Curated boutique lifestyle items and unique limited editions.', 'km' => 'ទំនិញប្លែកៗ និងផលិតផលពិសេសៗជាច្រើនទៀត។'],
                'image' => '/images/categories/other.jpg',
                'order' => 11,
                'featured' => false,
                'attributes' => [],
            ],
        ];

        foreach ($categoriesData as $slug => $data) {
            $cat = Category::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $data['name'],
                    'description' => $data['desc'],
                    'image_url' => $data['image'],
                    'display_order' => $data['order'],
                    'is_featured' => $data['featured'],
                    'is_active' => true,
                ]
            );

            // Attach assigned attributes
            $attrIds = collect($data['attributes'])->map(fn ($code) => $createdAttributes[$code]->id ?? null)->filter();
            $cat->attributes()->sync($attrIds);
        }
    }
}
