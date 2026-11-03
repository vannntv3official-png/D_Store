<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            CategoryAndAttributeSeeder::class,
            ShippingZoneSeeder::class,
            SettingSeeder::class,
            ContentSeeder::class,
            DemoProductSeeder::class,
        ]);
    }
}
