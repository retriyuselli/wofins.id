<?php

namespace Database\Seeders;

use App\Support\PackageRolePermissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Role jabatan Spatie (bukan nama paket).
     *
     * - Paket (starter/professional/business) menempel di companies.subscription_plan.
     - Role `pengunjung` = pemilik/tim paket; permission = PackageRolePermissions (CRUD Starter+).
     - Menu modul Pro/Business digating PlanResourceGate (nota dinas, rekonsiliasi, dokumen, HRIS, dll).
     - `role_management` tidak ada di paket mana pun; Role Filament hanya untuk super_admin (bypass).
     - Permission Shield penuh (LeaveRequest, Role, BankStatement, …) dibuat via `shield:generate`,
       bukan di seeder ini — setelah generate, sync ulang ke super_admin.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Legacy snake_case (kompatibilitas lama / PackageRolePermissions).
        // Akses Filament utama memakai format Shield: ViewAny:Order, Create:User, dll.
        $legacyPermissions = [
            'view_prospects',
            'create_prospects',
            'edit_prospects',
            'delete_prospects',
            'view_orders',
            'create_orders',
            'edit_orders',
            'delete_orders',
            'view_products',
            'create_products',
            'edit_products',
            'delete_products',
            'view_reports',
            'manage_users',
            'manage_roles',
        ];

        foreach ($legacyPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Permission Shield untuk role pengunjung (tim paket).
        foreach (PackageRolePermissions::forStarter() as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $accountManager = Role::firstOrCreate(['name' => 'Account Manager', 'guard_name' => 'web']);
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $employee = Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);
        $finance = Role::firstOrCreate(['name' => 'finance', 'guard_name' => 'web']);
        $eventManager = Role::firstOrCreate(['name' => 'Event Manager', 'guard_name' => 'web']);
        $pengunjung = Role::firstOrCreate(['name' => 'pengunjung', 'guard_name' => 'web']);

        // Staf internal — masih legacy; akses panel penuh mengandalkan Shield + role policy.
        $accountManager->syncPermissions([
            'view_prospects',
            'create_prospects',
            'edit_prospects',
            'view_orders',
            'create_orders',
            'edit_orders',
            'view_products',
            'view_reports',
        ]);

        $admin->syncPermissions([
            'view_prospects',
            'create_prospects',
            'edit_prospects',
            'delete_prospects',
            'view_orders',
            'create_orders',
            'edit_orders',
            'view_products',
            'create_products',
            'edit_products',
            'view_reports',
        ]);

        $employee->syncPermissions([
            'view_prospects',
            'view_orders',
            'view_products',
        ]);

        $finance->syncPermissions([
            'view_orders',
            'view_products',
            'view_reports',
        ]);

        $eventManager->syncPermissions([
            'view_prospects',
            'view_orders',
            'view_products',
        ]);

        // Pemilik paket: CRUD fitur Starter (+ permission modul Pro/Business agar upgrade
        // tidak perlu re-sync). Tampilan menu tetap digating PlanResourceGate.
        $pengunjung->syncPermissions(PackageRolePermissions::forStarter());

        // Setelah semua permission ada: super_admin dapat semuanya.
        $superAdmin->syncPermissions(Permission::all());

        $this->command->info('✅ Roles and permissions created successfully!');
        $this->command->newLine();
    }
}
