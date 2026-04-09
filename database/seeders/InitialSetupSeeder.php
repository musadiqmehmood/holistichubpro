<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Branch;
use App\Models\User;
use App\Models\Role;        // ✅ FIX B-08
use App\Models\Permission;  // ✅ FIX B-08
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InitialSetupSeeder extends Seeder
{
    public function run(): void
    {
        // ========== 1. BRANCH ==========
        $branch = Branch::firstOrCreate(
            ['name' => 'Main Branch'],
            [
                'address' => '123 Main Street',
                'city' => 'Multan',
                'phone' => '0303-0759014',
                'email' => 'musadiq@holistichubpro.com',
                'is_active' => true,
                'opening_time' => '09:00:00',
                'closing_time' => '21:00:00',
                'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
                'country' => 'Pakistan',
            ]
        );
        $this->command->info('✓ Branch ensured: ' . $branch->name);

        // ========== 2. PERMISSIONS & ROLES ==========
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $resources = ['users', 'roles', 'permissions', 'branches', 'services', 'products', 'customers', 'appointments', 'audit'];
        foreach ($resources as $resource) {
            foreach (['view', 'create', 'edit', 'delete'] as $action) {
                Permission::firstOrCreate([
                    'name' => "$resource.$action",
                    'guard_name' => 'web'
                ]);
            }
        }

        Permission::firstOrCreate(['name' => 'pos.access',      'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'reports.view',    'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'settings.manage', 'guard_name' => 'web']);

        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $admin      = Role::firstOrCreate(['name' => 'admin',       'guard_name' => 'web']);
        $manager    = Role::firstOrCreate(['name' => 'manager',     'guard_name' => 'web']);
        $stylist    = Role::firstOrCreate(['name' => 'stylist',     'guard_name' => 'web']);
        $receptionist = Role::firstOrCreate(['name' => 'receptionist', 'guard_name' => 'web']);
        $staff      = Role::firstOrCreate(['name' => 'staff',       'guard_name' => 'web']);

        $admin->givePermissionTo(Permission::all());

        $manager->givePermissionTo([
            'users.view', 'users.create', 'users.edit',
            'services.view', 'services.create', 'services.edit', 'services.delete',
            'products.view', 'products.create', 'products.edit', 'products.delete',
            'customers.view', 'customers.create', 'customers.edit',
            'appointments.view', 'appointments.create', 'appointments.edit', 'appointments.delete',
            'pos.access', 'reports.view',
        ]);

        $stylist->givePermissionTo([
            'customers.view', 'customers.create',
            'appointments.view', 'appointments.edit',
            'services.view',
        ]);

        $receptionist->givePermissionTo([
            'customers.view', 'customers.create', 'customers.edit',
            'appointments.view', 'appointments.create', 'appointments.edit',
            'pos.access',
        ]);

        $staff->givePermissionTo([
            'customers.view',
            'appointments.view',
            'services.view',
        ]);

        $this->command->info('✓ Roles and permissions ensured.');

        // ========== 3. SYSTEM USER ==========
        // ✅ FIX B-09: search by email, not by ID
        $systemUser = User::firstOrCreate(
            ['email' => 'system@holistichubpro.com'],
            [
                'name' => 'System',
                'password' => Hash::make(Str::random(64)),
                'email_verified_at' => now(),
                'branch_id' => $branch->id,
                'password_changed_at' => now(),
                'failed_login_attempts' => 0,
                'last_login_at' => null,
                'last_login_ip' => null,
            ]
        );
        if ($systemUser->wasRecentlyCreated) {
            $this->command->info('✓ System user created (ID: '.$systemUser->id.').');
        }

        // ========== 4. SUPER ADMIN USER ==========
        $superAdminUser = User::firstOrCreate(
            ['email' => 'admin@holistichubpro.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('Admin@123!'),
                'branch_id' => $branch->id,
                'email_verified_at' => now(),
                'password_changed_at' => now(),
                'failed_login_attempts' => 0,
                'last_login_at' => null,
                'last_login_ip' => null,
            ]
        );
        if (!$superAdminUser->hasRole('super-admin')) {
            // ✅ FIX B-10: use User::class instead of get_class()
            DB::table('model_has_roles')->insert([
                'role_id' => $superAdmin->id,
                'model_type' => User::class,
                'model_id' => $superAdminUser->id,
                'branch_id' => $superAdminUser->branch_id,
            ]);
            $this->command->info('✓ Super-admin role assigned.');
        }
        if ($superAdminUser->wasRecentlyCreated) {
            $this->command->info('✓ Super admin created: admin@holistichubpro.com / Admin@123!');
        } else {
            $this->command->info('✓ Super admin already exists.');
        }

        // ========== 5. TEST USERS ==========
        $managerUser = User::firstOrCreate(
            ['email' => 'manager@holistichubpro.com'],
            [
                'name' => 'Branch Admin',
                'password' => Hash::make('Manager@123!'),
                'branch_id' => $branch->id,
                'email_verified_at' => now(),
                'password_changed_at' => now(),
            ]
        );
        if (!$managerUser->hasRole('manager')) {
            DB::table('model_has_roles')->insert([
                'role_id' => $manager->id,
                'model_type' => User::class,
                'model_id' => $managerUser->id,
                'branch_id' => $managerUser->branch_id,
            ]);
        }
        if ($managerUser->wasRecentlyCreated) {
            $this->command->info('✓ Manager created: manager@holistichubpro.com / Manager@123!');
        }

        $stylistUser = User::firstOrCreate(
            ['email' => 'stylist@holistichubpro.com'],
            [
                'name' => 'Test Stylist',
                'password' => Hash::make('Stylist@123!'),
                'branch_id' => $branch->id,
                'email_verified_at' => now(),
                'password_changed_at' => now(),
            ]
        );
        if (!$stylistUser->hasRole('stylist')) {
            DB::table('model_has_roles')->insert([
                'role_id' => $stylist->id,
                'model_type' => User::class,
                'model_id' => $stylistUser->id,
                'branch_id' => $stylistUser->branch_id,
            ]);
        }
        if ($stylistUser->wasRecentlyCreated) {
            $this->command->info('✓ Stylist created: stylist@holistichubpro.com / Styli!');
        }

        $receptionistUser = User::firstOrCreate(
            ['email' => 'reception@holistichubpro.com'],
            [
                'name' => 'Test Receptionist',
                'password' => Hash::make('Reception@123!'),
                'branch_id' => $branch->id,
                'email_verified_at' => now(),
                'password_changed_at' => now(),
            ]
        );
        if (!$receptionistUser->hasRole('receptionist')) {
            DB::table('model_has_roles')->insert([
                'role_id' => $receptionist->id,
                'model_type' => User::class,
                'model_id' => $receptionistUser->id,
                'branch_id' => $receptionistUser->branch_id,
            ]);
        }
        if ($receptionistUser->wasRecentlyCreated) {
            $this->command->info('✓ Receptionist created: reception@holistichubpro.com / Reception@123!');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command->info('============================================');
        $this->command->info('Initial setup completed successfully!');
        $this->command->info('Super Admin: admin@holistichubpro.com / Admin@123!');
        $this->command->info('Manager: manager@holistichubpro.com / Manager@123!');
        $this->command->info('Stylist: stylist@holistichubpro.com / Styli!');
        $this->command->info('Receptionist: reception@holistichubpro.com / Reception@123!');
        $this->command->info('============================================');
    }
}
