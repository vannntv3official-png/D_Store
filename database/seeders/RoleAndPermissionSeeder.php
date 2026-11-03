<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Catalog permissions
            ['name' => 'catalog.view', 'display_name' => 'View Catalog', 'group' => 'Catalog'],
            ['name' => 'catalog.create', 'display_name' => 'Create Catalog Items', 'group' => 'Catalog'],
            ['name' => 'catalog.edit', 'display_name' => 'Edit Catalog Items', 'group' => 'Catalog'],
            ['name' => 'catalog.delete', 'display_name' => 'Delete Catalog Items', 'group' => 'Catalog'],
            ['name' => 'catalog.import', 'display_name' => 'Import Products via CSV', 'group' => 'Catalog'],

            // Inventory permissions
            ['name' => 'inventory.view', 'display_name' => 'View Inventory', 'group' => 'Inventory'],
            ['name' => 'inventory.adjust', 'display_name' => 'Adjust Inventory Levels', 'group' => 'Inventory'],

            // Order permissions
            ['name' => 'orders.view', 'display_name' => 'View Orders', 'group' => 'Orders'],
            ['name' => 'orders.process', 'display_name' => 'Process & Fulfill Orders', 'group' => 'Orders'],
            ['name' => 'orders.cancel', 'display_name' => 'Cancel Orders', 'group' => 'Orders'],

            // Refund permissions
            ['name' => 'refunds.view', 'display_name' => 'View Refunds', 'group' => 'Refunds'],
            ['name' => 'refunds.create', 'display_name' => 'Initiate Refunds', 'group' => 'Refunds'],
            ['name' => 'refunds.approve', 'display_name' => 'Approve Refunds & Restock', 'group' => 'Refunds'],

            // Customer support permissions
            ['name' => 'customers.view', 'display_name' => 'View Customer Accounts', 'group' => 'Customers'],
            ['name' => 'customers.manage', 'display_name' => 'Manage Customer Accounts', 'group' => 'Customers'],
            ['name' => 'reviews.moderate', 'display_name' => 'Moderate Reviews', 'group' => 'Customers'],

            // Finance & reports permissions
            ['name' => 'finance.view', 'display_name' => 'View Financial Reports', 'group' => 'Finance'],
            ['name' => 'finance.reconcile', 'display_name' => 'Reconcile Transactions', 'group' => 'Finance'],

            // Marketing & Content permissions
            ['name' => 'marketing.manage', 'display_name' => 'Manage Coupons & Campaigns', 'group' => 'Marketing'],
            ['name' => 'loyalty.manage', 'display_name' => 'Manage Loyalty & Rewards', 'group' => 'Loyalty'],
            ['name' => 'content.manage', 'display_name' => 'Manage CMS & Content Pages', 'group' => 'Content'],

            // System administration permissions
            ['name' => 'staff.manage', 'display_name' => 'Manage Staff & Roles', 'group' => 'System'],
            ['name' => 'settings.manage', 'display_name' => 'Manage Store Settings', 'group' => 'System'],
            ['name' => 'activity.view', 'display_name' => 'View Audit Activity Logs', 'group' => 'System'],
        ];

        $permissionModels = [];
        foreach ($permissions as $perm) {
            $permissionModels[$perm['name']] = Permission::firstOrCreate(
                ['name' => $perm['name']],
                ['display_name' => $perm['display_name'], 'group' => $perm['group']]
            );
        }

        // 6 Standard Roles
        $rolesData = [
            'super_admin' => [
                'display_name' => 'Super Administrator',
                'description' => 'Full unrestricted system access',
                'permissions' => array_keys($permissionModels),
            ],
            'admin' => [
                'display_name' => 'Store Administrator',
                'description' => 'Administrative control excluding staff privilege escalation',
                'permissions' => [
                    'catalog.view', 'catalog.create', 'catalog.edit', 'catalog.delete', 'catalog.import',
                    'inventory.view', 'inventory.adjust',
                    'orders.view', 'orders.process', 'orders.cancel',
                    'refunds.view', 'refunds.create', 'refunds.approve',
                    'customers.view', 'customers.manage', 'reviews.moderate',
                    'finance.view', 'finance.reconcile',
                    'marketing.manage', 'loyalty.manage', 'content.manage',
                    'settings.manage', 'activity.view',
                ],
            ],
            'product_manager' => [
                'display_name' => 'Product Manager',
                'description' => 'Manages categories, products, attributes, inventory, and promotions',
                'permissions' => [
                    'catalog.view', 'catalog.create', 'catalog.edit', 'catalog.import',
                    'inventory.view', 'inventory.adjust',
                    'marketing.manage',
                ],
            ],
            'order_manager' => [
                'display_name' => 'Order Manager',
                'description' => 'Manages order processing, packing, shipping, and fulfillment',
                'permissions' => [
                    'orders.view', 'orders.process', 'orders.cancel',
                    'inventory.view',
                    'refunds.view', 'refunds.create',
                ],
            ],
            'customer_support' => [
                'display_name' => 'Customer Support',
                'description' => 'Handles customer inquiries, review moderation, and order status lookups',
                'permissions' => [
                    'orders.view',
                    'customers.view',
                    'reviews.moderate',
                    'content.manage',
                ],
            ],
            'accountant' => [
                'display_name' => 'Accountant',
                'description' => 'Reviews transactions, payments, reconciled revenues, and refund logs',
                'permissions' => [
                    'orders.view',
                    'finance.view', 'finance.reconcile',
                    'refunds.view', 'refunds.approve',
                ],
            ],
        ];

        foreach ($rolesData as $roleName => $data) {
            $role = Role::firstOrCreate(
                ['name' => $roleName],
                ['display_name' => $data['display_name'], 'description' => $data['description']]
            );

            $rolePermIds = collect($data['permissions'])->map(fn ($p) => $permissionModels[$p]->id ?? null)->filter();
            $role->permissions()->sync($rolePermIds);
        }

        // Create Seed Super Admin Account
        $adminEmail = 'admin@dstore.com.kh';
        $superAdmin = User::firstOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'D Store Admin',
                'phone' => '+85512000001',
                'password' => Hash::make('Admin@DStore2026!'),
                'is_active' => true,
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );

        $superAdminRole = Role::where('name', 'super_admin')->first();
        if ($superAdminRole && !$superAdmin->roles->contains($superAdminRole->id)) {
            $superAdmin->roles()->attach($superAdminRole->id);
        }
    }
}
