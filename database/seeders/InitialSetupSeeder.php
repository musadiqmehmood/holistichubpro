<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

use App\Models\Branch;
use App\Models\Currency;
use App\Models\PaymentType;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SiteSetting;
use App\Models\StoreSetting;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\User;

class InitialSetupSeeder extends Seeder
{
    // ── Permission definitions ─────────────────────────────────────────────────

    /**
     * Every permission that exists in the system.
     * Defined once here so create, role-assignment, and assertions
     * all reference the same source of truth.
     */
    private function allPermissions(): array
    {
        $crudResources = [
            // Core
            'users', 'roles', 'permissions', 'branches',
            // Business
            'services', 'products', 'customers', 'appointments',
            // Audit
            'audit',
            // Settings — reference entities
            'taxes', 'tax_groups', 'units', 'payment_types', 'currencies',
        ];

        $permissions = [];
        foreach ($crudResources as $resource) {
            foreach (['view', 'create', 'edit', 'delete'] as $action) {
                $permissions[] = "$resource.$action";
            }
        }

        // Non-CRUD singleton permissions
        return array_merge($permissions, [
            'pos.access',
            'reports.view',
            'settings.view',    // read-only access to settings pages
            'settings.manage',  // save/change settings
        ]);
    }

    // ── Role permission maps ───────────────────────────────────────────────────

    private function managerPermissions(): array
    {
        return [
            'users.view', 'users.create', 'users.edit',
            'services.view', 'services.create', 'services.edit', 'services.delete',
            'products.view', 'products.create', 'products.edit', 'products.delete',
            'customers.view', 'customers.create', 'customers.edit',
            'appointments.view', 'appointments.create', 'appointments.edit', 'appointments.delete',
            'pos.access',
            'reports.view',
            // Read-only settings access
            'settings.view',
            'taxes.view', 'tax_groups.view', 'units.view', 'payment_types.view', 'currencies.view',
        ];
    }

    private function stylistPermissions(): array
    {
        return [
            'customers.view', 'customers.create',
            'appointments.view', 'appointments.edit',
            'services.view',
        ];
    }

    private function receptionistPermissions(): array
    {
        return [
            'customers.view', 'customers.create', 'customers.edit',
            'appointments.view', 'appointments.create', 'appointments.edit',
            'pos.access',
        ];
    }

    private function staffPermissions(): array
    {
        return [
            'customers.view',
            'appointments.view',
            'services.view',
        ];
    }

    // ── Entry point ────────────────────────────────────────────────────────────

    public function run(): void
    {
        DB::transaction(function () {
            $this->seedBranch();
            $this->seedPermissionsAndRoles();
            $this->seedUsers();
            $this->seedCurrencies();
            $this->seedTaxes();
            $this->seedUnits();
            $this->seedPaymentTypes();
            $this->seedSiteSettings();
            $this->seedStoreSettings();
        });

        // Clear the Spatie permission cache AFTER the transaction commits
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->printSummary();
    }

    // ── Step 1: Branch ─────────────────────────────────────────────────────────

    private function seedBranch(): void
    {
        Branch::firstOrCreate(
            ['name' => 'Main Branch'],
            [
                'address'      => '123 Main Street',
                'city'         => 'Multan',
                'phone'        => '0303-0759014',
                'email'        => 'musadiq@holistichubpro.com',
                'is_active'    => true,
                'opening_time' => '09:00:00',
                'closing_time' => '21:00:00',
                // Cast to JSON array — Branch model must have working_days cast as 'array'
                'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
                'country'      => 'Pakistan',
            ]
        );

        $this->command->info('  Branch ensured: Main Branch');
    }

    // ── Step 2: Permissions & Roles ────────────────────────────────────────────

    private function seedPermissionsAndRoles(): void
    {
        // Flush cache before creating new permissions so Spatie sees the new entries
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Create every permission — idempotent (firstOrCreate)
        foreach ($this->allPermissions() as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // Fetch roles — create if missing
        $superAdmin   = Role::firstOrCreate(['name' => 'super-admin',  'guard_name' => 'web']);
        $admin        = Role::firstOrCreate(['name' => 'admin',        'guard_name' => 'web']);
        $manager      = Role::firstOrCreate(['name' => 'manager',      'guard_name' => 'web']);
        $stylist      = Role::firstOrCreate(['name' => 'stylist',      'guard_name' => 'web']);
        $receptionist = Role::firstOrCreate(['name' => 'receptionist', 'guard_name' => 'web']);
        $staff        = Role::firstOrCreate(['name' => 'staff',        'guard_name' => 'web']);

        // Admin gets every permission — query only IDs, no mass hydration
        $admin->syncPermissions(Permission::pluck('name')->toArray());

        // super-admin has no explicit permissions — the Gate::before bypass covers all
        // (keeping the role clean makes auditing easier)

        $manager->syncPermissions($this->managerPermissions());
        $stylist->syncPermissions($this->stylistPermissions());
        $receptionist->syncPermissions($this->receptionistPermissions());
        $staff->syncPermissions($this->staffPermissions());

        $this->command->info('  Permissions & roles ensured.');
    }

    // ── Step 3: Users ──────────────────────────────────────────────────────────

    private function seedUsers(): void
    {
        $branch = Branch::where('name', 'Main Branch')->firstOrFail();

        // System user — used as the audit log fallback actor (ID is not assumed)
        $systemUser = User::firstOrCreate(
            ['email' => 'system@holistichubpro.com'],
            [
                'name'                  => 'System',
                'password'              => Hash::make(Str::random(64)),
                'email_verified_at'     => now(),
                'branch_id'             => $branch->id,
                'password_changed_at'   => now(),
                'failed_login_attempts' => 0,
            ]
        );

        if ($systemUser->wasRecentlyCreated) {
            $this->command->info('  System user created (ID: ' . $systemUser->id . ')');
        }

        // Super admin
        $this->ensureUser(
            email:    'admin@holistichubpro.com',
            name:     'Super Admin',
            password: 'Admin@123!',
            branch:   $branch,
            role:     'super-admin',
        );

        // Manager
        $this->ensureUser(
            email:    'manager@holistichubpro.com',
            name:     'Branch Manager',
            password: 'Manager@123!',
            branch:   $branch,
            role:     'manager',
        );

        // Stylist
        $this->ensureUser(
            email:    'stylist@holistichubpro.com',
            name:     'Test Stylist',
            password: 'Stylist@123!',
            branch:   $branch,
            role:     'stylist',
        );

        // Receptionist
        $this->ensureUser(
            email:    'reception@holistichubpro.com',
            name:     'Test Receptionist',
            password: 'Reception@123!',
            branch:   $branch,
            role:     'receptionist',
        );
    }

    /**
     * Creates a user if they do not exist and assigns a role via Spatie's
     * assignRole() so that events fire and cache is managed correctly.
     * Raw DB::table inserts are intentionally avoided.
     */
    private function ensureUser(
        string $email,
        string $name,
        string $password,
        Branch $branch,
        string $role,
    ): User {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name'                  => $name,
                'password'              => Hash::make($password),
                'branch_id'             => $branch->id,
                'email_verified_at'     => now(),
                'password_changed_at'   => now(),
                'failed_login_attempts' => 0,
            ]
        );

        // Use Spatie's assignRole — fires RoleAttached event + clears cache
        if (!$user->hasRole($role)) {
            $user->assignRole($role);
            $this->command->info("  Role '$role' assigned to $email");
        }

        if ($user->wasRecentlyCreated) {
            $this->command->info("  User created: $email / $password");
        } else {
            $this->command->info("  User exists:  $email");
        }

        return $user;
    }

    // ── Step 4: Default Currencies ─────────────────────────────────────────────

    private function seedCurrencies(): void
    {
        $currencies = [
            ['name' => 'US Dollar',        'code' => 'USD', 'symbol' => '$',   'status' => true],
            ['name' => 'Euro',             'code' => 'EUR', 'symbol' => '€',   'status' => true],
            ['name' => 'British Pound',    'code' => 'GBP', 'symbol' => '£',   'status' => true],
            ['name' => 'Pakistani Rupee',  'code' => 'PKR', 'symbol' => '₨',   'status' => true],
            ['name' => 'Indian Rupee',     'code' => 'INR', 'symbol' => '₹',   'status' => true],
            ['name' => 'UAE Dirham',       'code' => 'AED', 'symbol' => 'د.إ', 'status' => true],
            ['name' => 'Saudi Riyal',      'code' => 'SAR', 'symbol' => '﷼',   'status' => true],
            ['name' => 'Australian Dollar','code' => 'AUD', 'symbol' => 'A$',  'status' => true],
            ['name' => 'Canadian Dollar',  'code' => 'CAD', 'symbol' => 'C$',  'status' => true],
        ];

        foreach ($currencies as $c) {
            Currency::firstOrCreate(['code' => $c['code']], $c);
        }

        $this->command->info('  Default currencies ensured (' . count($currencies) . ')');
    }

    // ── Step 5: Default Taxes ──────────────────────────────────────────────────

    private function seedTaxes(): void
    {
        $taxes = [
            ['name' => 'Tax Exempt', 'percentage' => 0.00,  'status' => true],
            ['name' => 'GST 5%',     'percentage' => 5.00,  'status' => true],
            ['name' => 'GST 12%',    'percentage' => 12.00, 'status' => true],
            ['name' => 'GST 18%',    'percentage' => 18.00, 'status' => true],
            ['name' => 'GST 28%',    'percentage' => 28.00, 'status' => true],
            ['name' => 'VAT 5%',     'percentage' => 5.00,  'status' => true],
            ['name' => 'VAT 10%',    'percentage' => 10.00, 'status' => true],
            ['name' => 'VAT 20%',    'percentage' => 20.00, 'status' => true],
        ];

        foreach ($taxes as $t) {
            Tax::firstOrCreate(['name' => $t['name']], $t);
        }

        $this->command->info('  Default taxes ensured (' . count($taxes) . ')');
    }

    // ── Step 6: Default Units ──────────────────────────────────────────────────

    private function seedUnits(): void
    {
        $units = [
            ['name' => 'Piece',      'description' => 'Single unit/item',          'status' => true],
            ['name' => 'Kg',         'description' => 'Kilogram',                   'status' => true],
            ['name' => 'Gram',       'description' => 'Gram',                       'status' => true],
            ['name' => 'Litre',      'description' => 'Litre',                      'status' => true],
            ['name' => 'Millilitre', 'description' => 'Millilitre',                 'status' => true],
            ['name' => 'Hour',       'description' => 'Service billed per hour',    'status' => true],
            ['name' => 'Session',    'description' => 'Fixed-duration appointment', 'status' => true],
            ['name' => 'Box',        'description' => 'Boxed quantity',             'status' => true],
        ];

        foreach ($units as $u) {
            Unit::firstOrCreate(['name' => $u['name']], $u);
        }

        $this->command->info('  Default units ensured (' . count($units) . ')');
    }

    // ── Step 7: Default Payment Types ─────────────────────────────────────────

    private function seedPaymentTypes(): void
    {
        $types = [
            ['name' => 'Cash',          'status' => true],
            ['name' => 'Credit Card',   'status' => true],
            ['name' => 'Debit Card',    'status' => true],
            ['name' => 'Bank Transfer', 'status' => true],
            ['name' => 'Cheque',        'status' => true],
            ['name' => 'Online / UPI',  'status' => true],
        ];

        foreach ($types as $t) {
            PaymentType::firstOrCreate(['name' => $t['name']], $t);
        }

        $this->command->info('  Default payment types ensured (' . count($types) . ')');
    }

    // ── Step 8: Site Settings ──────────────────────────────────────────────────

    private function seedSiteSettings(): void
    {
        SiteSetting::firstOrCreate([], ['site_name' => 'HolisticHubPro']);

        $this->command->info('  Site settings ensured.');
    }

    // ── Step 9: Store Settings ─────────────────────────────────────────────────

    private function seedStoreSettings(): void
    {
        if (StoreSetting::exists()) {
            $this->command->info('  Store settings already exist — skipped.');
            return;
        }

        $branch = Branch::where('name', 'Main Branch')->first();

        if (!$branch) {
            $this->command->warn('  Store settings skipped — Main Branch not found.');
            return;
        }

        StoreSetting::create([
            'store_code'                => 'HHP-001',
            'store_name'                => 'HolisticHubPro',
            'mobile'                    => '+10000000000',
            'email'                     => 'store@holistichubpro.com',
            'branch_id'                 => $branch->id,
            'timezone'                  => 'UTC',
            'date_format'               => 'Y-m-d',
            'time_format'               => 'H:i',
            'currency'                  => 'PKR',
            'currency_symbol_placement' => 'before',
            'decimals'                  => 2,
            'decimals_for_quantity'     => 2,
            'show_signature_on_invoice' => false,
        ]);

        $this->command->info('  Default store settings created.');
    }

    // ── Output ─────────────────────────────────────────────────────────────────

    private function printSummary(): void
    {
        $this->command->newLine();
        $this->command->info('============================================================');
        $this->command->info('  Initial setup complete!');
        $this->command->info('============================================================');
        $this->command->info('  Super Admin:   admin@holistichubpro.com  / Admin@123!');
        $this->command->info('  Manager:       manager@holistichubpro.com / Manager@123!');
        $this->command->info('  Stylist:       stylist@holistichubpro.com  / Stylist@123!');
        $this->command->info('  Receptionist:  reception@holistichubpro.com / Reception@123!');
        $this->command->info('============================================================');
        $this->command->newLine();
    }
}
