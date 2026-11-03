<?php

namespace Database\Seeders;

use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use App\Models\ShippingZoneLocation;
use Illuminate\Database\Seeder;

class ShippingZoneSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Phnom Penh Zone
        $ppZone = ShippingZone::firstOrCreate(
            ['code' => 'KH-PP'],
            [
                'name' => ['en' => 'Phnom Penh Capital', 'km' => 'រាជធានីភ្នំពេញ'],
                'is_active' => true,
            ]
        );

        $ppDistricts = [
            'doun_penh' => 'Doun Penh (ដូនពេញ)',
            'chamkar_mon' => 'Chamkar Mon (ចំការមន)',
            'pram_pir_makara' => '7 Makara (៧ មករា)',
            'tuol_kouk' => 'Tuol Kouk (ទួលគោក)',
            'dangkao' => 'Dangkao (ដង្កោ)',
            'mean_chey' => 'Mean Chey (មានជ័យ)',
            'russey_keo' => 'Russey Keo (ឫស្សីកែវ)',
            'sen_sok' => 'Sen Sok (សែនសុខ)',
            'pou_senchey' => 'Pou Senchey (ពោធិ៍សែនជ័យ)',
            'chroy_changvar' => 'Chroy Changvar (ជ្រោយចង្វារ)',
            'preaek_pnov' => 'Preaek Pnov (ព្រែកព្នៅ)',
            'chbar_ampov' => 'Chbar Ampov (ច្បារអំពៅ)',
            'boeng_keng_kang' => 'Boeng Keng Kang (បឹងកេងកង)',
            'kamboul' => 'Kamboul (កំបូល)',
        ];

        foreach (array_keys($ppDistricts) as $distId) {
            ShippingZoneLocation::firstOrCreate([
                'shipping_zone_id' => $ppZone->id,
                'province_id' => 'phnom_penh',
                'district_id' => $distId,
            ]);
        }

        ShippingMethod::firstOrCreate(
            ['shipping_zone_id' => $ppZone->id, 'name->en' => 'Phnom Penh Same-Day / Next-Day Delivery'],
            [
                'name' => [
                    'en' => 'Phnom Penh Same-Day / Next-Day Delivery',
                    'km' => 'សេវាដឹកជញ្ជូនរហ័សក្នុងភ្នំពេញ (១-២ ថ្ងៃ)',
                ],
                'description' => [
                    'en' => 'Direct courier delivery to your home or office in Phnom Penh.',
                    'km' => 'ដឹកជញ្ជូនផ្ទាល់ដល់គេហដ្ឋាន ឬកន្លែងធ្វើការក្នុងរាជធានីភ្នំពេញ។',
                ],
                'rate' => 1.5000,
                'min_order_free_shipping' => 35.0000,
                'estimated_delivery_days' => '1-2 Days',
                'is_active' => true,
            ]
        );

        // 2. Provincial Zone
        $provincesZone = ShippingZone::firstOrCreate(
            ['code' => 'KH-PROV'],
            [
                'name' => ['en' => 'Provinces (All Cambodia)', 'km' => 'បណ្តាខេត្តនានាទូទាំងប្រទេស'],
                'is_active' => true,
            ]
        );

        $provinces = [
            'kandal' => 'Kandal (កណ្តាល)',
            'siem_reap' => 'Siem Reap (សៀមរាប)',
            'battambang' => 'Battambang (បាត់ដំបង)',
            'preah_sihanouk' => 'Preah Sihanouk (ព្រះសីហនុ)',
            'kampot' => 'Kampot (កំពត)',
            'kep' => 'Kep (កែប)',
            'kampong_cham' => 'Kampong Cham (កំពង់ចាម)',
            'kampong_chhnang' => 'Kampong Chhnang (កំពង់ឆ្នាំង)',
            'kampong_speu' => 'Kampong Speu (កំពង់ស្ពឺ)',
            'kampong_thom' => 'Kampong Thom (កំពង់ធំ)',
            'koh_kong' => 'Koh Kong (កោះកុង)',
            'kratie' => 'Kratie (ក្រចេះ)',
            'mondulkiri' => 'Mondulkiri (មណ្ឌលគិរី)',
            'preah_vihear' => 'Preah Vihear (ព្រះវិហារ)',
            'prey_veng' => 'Prey Veng (ព្រៃវែង)',
            'pursat' => 'Pursat (ពោធិ៍សាត់)',
            'ratanakiri' => 'Ratanakiri (រតនគិរី)',
            'stung_treng' => 'Stung Treng (ស្ទឹងត្រែង)',
            'svay_rieng' => 'Svay Rieng (ស្វាយរៀង)',
            'takeo' => 'Takeo (តាកែវ)',
            'oddar_meanchey' => 'Oddar Meanchey (ឧត្តរមានជ័យ)',
            'pailin' => 'Pailin (ប៉ៃលិន)',
            'tboung_khmum' => 'Tboung Khmum (ត្បូងឃ្មុំ)',
            'banteay_meanchey' => 'Banteay Meanchey (បន្ទាយមានជ័យ)',
        ];

        foreach (array_keys($provinces) as $provId) {
            ShippingZoneLocation::firstOrCreate([
                'shipping_zone_id' => $provincesZone->id,
                'province_id' => $provId,
                'district_id' => null,
            ]);
        }

        ShippingMethod::firstOrCreate(
            ['shipping_zone_id' => $provincesZone->id, 'name->en' => 'Provincial Express Delivery (Virak Buntham / J&T)'],
            [
                'name' => [
                    'en' => 'Provincial Express Delivery (Virak Buntham / J&T)',
                    'km' => 'សេវាដឹកជញ្ជូនទៅបណ្តាខេត្ត (វីរៈប៊ុនថាំ / J&T)',
                ],
                'description' => [
                    'en' => 'Insured express parcel dispatch to provincial hubs and pickup points.',
                    'km' => 'ផ្ញើតាមក្រុមហ៊ុនដឹកជញ្ជូនដែលអាចទុកចិត្តបានទូទាំងប្រទេសកម្ពុជា។',
                ],
                'rate' => 2.5000,
                'min_order_free_shipping' => 60.0000,
                'estimated_delivery_days' => '2-3 Days',
                'is_active' => true,
            ]
        );
    }
}
