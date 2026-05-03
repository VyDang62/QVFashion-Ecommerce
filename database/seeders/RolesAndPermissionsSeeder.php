<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //Xóa cache permission
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        //ĐỊNH NGHĨA CÁC QUYỀN THEO MODULE
        $permissionsByModule = [
            'dashboard'      => ['view_overall_dashboard','view_product_statistics', 'view_inventory_statistics','view_financial', 'export_financial', 'export_inventory'], // Thống kê, báo cáo
            'activitylogs'   => ['view'],
            'users'          => ['view', 'create', 'edit', 'delete'],
            'roles'          => ['view', 'create', 'edit', 'delete'],
            // Quản lý sản phẩm & Thuộc tính
            'products'       => ['view', 'create', 'edit', 'delete'],
            'categories'     => ['view', 'create', 'edit', 'delete'],
            'brands'         => ['view', 'create', 'edit', 'delete'],
            'attributes'     => ['view', 'create', 'edit', 'delete'],
            'product-types'  => ['view', 'create', 'edit', 'delete'],
            'ratings'        => ['view', 'approve', 'delete'],
            // Quản lý kho
            'suppliers'      => ['view', 'create', 'edit', 'delete'],
            'goods-receipts' => ['view', 'create', 'edit', 'cancel', 'approve'],
            'inventory'      => ['view', 'edit'],
            // Marketing
            'banners'        => ['view', 'create', 'edit', 'delete'],
            'vouchers'       => ['view', 'create', 'edit', 'delete'],
            'flashsales'     => ['view', 'create', 'edit', 'delete'],
            // Bán hàng
            'orders'         => ['view', 'edit', 'cancel', 'approve', 'export'],
            // Nội dung & Cấu hình
            'pages'          => ['view', 'create', 'edit', 'delete'],
            'settings'       => ['view', 'edit'],
            'notifications'  => ['view', 'edit'],
        ];

        //Tạo Permissions
        foreach ($permissionsByModule as $module => $actions) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name' => "$module.$action",
                    'guard_name' => 'web'
                ]);
            }
        }

        //TẠO VÀ GÁN QUYỀN CHO CÁC VAI TRÒ

        //Customer (Khách hàng)
        $customerRole = Role::firstOrCreate(['name' => 'customer']);

        //Super Admin (Toàn quyền)
        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin']);
        $superAdminRole->syncPermissions(Permission::all());

        //Quản lý kho (Warehouse Manager)
        $warehouseRole = Role::firstOrCreate(['name' => 'warehouse-manager']);
        $warehouseRole->syncPermissions([
            'dashboard.view_overall_dashboard', 'dashboard.view_overall_dashboard', 'dashboard.view_inventory_statistics', 'dashboard.export_inventory',
            'products.view', 'products.create', 'products.edit',
            'categories.view', 'brands.view', 'attributes.view', 'product-types.view',
            'suppliers.view', 'suppliers.create', 'suppliers.edit',
            'goods-receipts.view', 'goods-receipts.create', 'goods-receipts.edit', 'goods-receipts.approve',
            'inventory.view', 'inventory.edit',
            'notifications.view','notifications.edit'
        ]);

        //Nhân viên bán hàng (Sale)
        $salesRole = Role::firstOrCreate(['name' => 'sales-staff']);
        $salesRole->syncPermissions([
            'dashboard.view_overall_dashboard', 'dashboard.view_product_statistics', 
            'products.view',
            'categories.view',
            'orders.view', 'orders.edit', 'orders.approve', 'orders.cancel', 'orders.export',
            'banners.view', 'banners.create', 'banners.edit',
            'vouchers.view', 'vouchers.create', 'vouchers.edit',
            'flashsales.view', 'flashsales.create', 'flashsales.edit',
            'ratings.view', 'ratings.approve',
            'pages.view','pages.create', 'pages.edit',
            'notifications.view','notifications.edit'
        ]);

        //TẠO TÀI KHOẢN MẪU

        $userData = [
            [
                'email' => 'admin@gmail.com',
                'full_name' => 'Super Admin',
                'phone_number' => '0123456789',
                'role' => $superAdminRole
            ],
            [
                'email' => 'warehouse@gmail.com',
                'full_name' => 'Quản lý kho',
                'phone_number' => '0123456787',
                'role' => $warehouseRole
            ],
            [
                'email' => 'salesstaff@gmail.com',
                'full_name' => 'Nhân viên bán hàng',
                'phone_number' => '0123456782',
                'role' => $salesRole
            ],
            [
                'email' => 'customer@gmail.com',
                'full_name' => 'Khách hàng mẫu',
                'phone_number' => '0123456785',
                'role' => $customerRole
            ],
        ];

        foreach ($userData as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'full_name' => $data['full_name'],
                    'password' => Hash::make('password'),
                    'phone_number' => $data['phone_number'],
                    'email_verified_at' => now(),
                ]
            );
            $user->syncRoles([$data['role']]);
        }

        $this->command->info('Đã cập nhật Quyền, Vai trò và Tài khoản mẫu!');
    }
}
