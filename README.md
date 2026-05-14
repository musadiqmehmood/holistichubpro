# HolisticHub Pro

> Enterprise-grade multi-branch management system with comprehensive RBAC, audit logging, searchable dropdowns, and locale-aware datetime formatting. Built with Laravel 12 + Vue 3 + Pinia + Tailwind CSS.

---

## Table of Contents

1. [Tech Stack](#tech-stack)
2. [Project Architecture](#project-architecture)
3. [Complete Fix History](#complete-fix-history)
4. [Security Hardening](#security-hardening)
5. [Performance Optimizations](#performance-optimizations)
6. [Database Schema](#database-schema)
7. [API Documentation](#api-documentation)
8. [Directory Structure](#directory-structure)
9. [Artisan Commands Reference](#artisan-commands-reference)
10. [Installation Guide](#installation-guide)
11. [Environment Variables](#environment-variables)

---

## Tech Stack

### Backend
| Component | Version | Purpose |
|-----------|---------|---------|
| PHP | ^8.4 | Core language |
| Laravel | ^12.0 | Web framework |
| Laravel Sanctum | ^4.2 | API authentication |
| Spatie Permission | ^6.16 | Role-based access control |
| League/CSV | ^9.28 | CSV import/export |
| Symfony Mailer | ^7.3 | SMTP testing |

### Frontend
| Component | Version | Purpose |
|-----------|---------|---------|
| Vue | ^3.5.30 | UI framework |
| Vite | ^8.0.0 | Build tool |
| Pinia | ^3.0.4 | State management |
| Axios | ^1.13.6 | HTTP client |
| Tailwind CSS | ^3.4 | Utility-first CSS |

### Database
| Component | Tables |
|-----------|--------|
| SQLite (dev) / MySQL (prod) | 14 tables + Spatie permission tables |

---

## Project Architecture

### Backend (Laravel)

```
app/
|-- Console/              # Artisan commands
|-- Events/               # Domain events
|-- Exceptions/           # Custom exceptions
|-- Helpers/              # Helper classes
|   |-- DateTimeHelper.php        # DateTime formatting helper
|-- Http/
|   |-- Controllers/      # Controllers organized by module
|   |   |-- Admin/        # Admin module
|   |   |   |-- AuditLogController.php
|   |   |   |-- BranchController.php
|   |   |   |-- PermissionController.php
|   |   |   |-- RoleController.php
|   |   |   |-- UserController.php
|   |   |   |-- AuthController.php
|   |   |   |-- UserDesignSettingController.php
|   |   |   |-- Settings/         # Settings submodule
|   |   |       |-- BackupController.php
|   |   |       |-- CurrencyController.php
|   |   |       |-- PaymentTypeController.php
|   |   |       |-- SiteSettingController.php
|   |   |       |-- SmtpSettingController.php
|   |   |       |-- StoreSettingController.php
|   |   |       |-- StoreSettingsService.php
|   |   |       |-- TaxController.php
|   |   |       |-- TaxGroupController.php
|   |   |       |-- UnitController.php
|   |-- Middleware/       # HTTP middleware
|   |   |-- EnsurePasswordIsNotExpired.php
|   |   |-- HandleBranchContext.php    # Injects branch context
|-- Jobs/
|   |-- LogAuditJob.php   # Async audit logging (deprecated, direct insert used)
|-- Models/               # Eloquent models
|   |-- AuditLog.php
|   |-- Branch.php
|   |-- Currency.php
|   |-- PaymentType.php
|   |-- PasswordHistory.php
|   |-- Permission.php    # Spatie Permission
|   |-- Role.php          # Spatie Role
|   |-- SiteSetting.php
|   |-- SmtpSetting.php
|   |-- StoreSetting.php
|   |-- Tax.php
|   |-- TaxGroup.php
|   |-- Unit.php
|   |-- User.php          # Has hasRoleInBranch() / hasPermissionInBranch()
|   |-- UserDesignSetting.php
|-- Policies/             # Authorization policies (all branch-scoped)
|   |-- AuditLogPolicy.php
|   |-- CurrencyPolicy.php
|   |-- PaymentTypePolicy.php
|   |-- PermissionPolicy.php
|   |-- RolePolicy.php
|   |-- SiteSettingPolicy.php
|   |-- SmtpSettingPolicy.php
|   |-- StoreSettingPolicy.php
|   |-- TaxGroupPolicy.php
|   |-- TaxPolicy.php
|   |-- UnitPolicy.php
|   |-- UserPolicy.php
|-- Providers/
|   |-- AuthServiceProvider.php        # Gate::before + custom validators
|   |-- AppServiceProvider.php
|-- Rules/                # Custom validation rules
|-- Services/             # Business logic layer
|   |-- AuditLogService.php            # Direct INSERT audit logging
|   |-- Settings/
|   |   |-- StoreSettingsService.php   # Store settings business logic
|-- Traits/
|   |-- ApiResponse.php   # Consistent API response envelope
```

### Frontend (Vue 3)

```
holistichubpro-frontend/src/
|-- api/                  # API layer
|   |-- axios.js          # Axios instance with interceptors + baseURL
|   |-- audit.js          # Audit log API
|   |-- auth.js           # Auth API
|   |-- branches.js       # Branches API
|   |-- permissions.js    # Permissions API
|   |-- roles.js          # Roles API
|   |-- users.js          # Users API
|   |-- settings.js       # Settings API (CSV utils included)
|-- components/
|   |-- ui/               # Reusable UI components
|   |   |-- AppSelect.vue         # Searchable dropdown (auto >7 options)
|   |   |-- AppTable.vue          # Data table
|   |   |-- AppPagination.vue     # Pagination control
|   |   |-- AppCard.vue
|   |   |-- AppButton.vue
|   |   |-- AppModal.vue
|   |   |-- AppFormField.vue
|   |   |-- AppBadge.vue
|   |   |-- AppAvatar.vue
|   |   |-- AppToggle.vue
|   |   |-- AppAlert.vue
|   |-- modals/           # Modal components
|   |   |-- UserFormModal.vue
|   |-- layout/           # Layout components
|   |   |-- AppLayout.vue
|   |   |-- AppSidebar.vue
|   |-- auth/             # Auth-specific components
|   |-- common/           # Shared components
|-- composables/          # Vue composables
|-- router/               # Vue Router configuration
|-- stores/               # Pinia stores
|   |-- auth.js           # Auth state (user, token, permissions)
|   |-- settings.js       # Settings + resource lists
|   |-- ui.js             # UI state (notifications, loading)
|-- utils/                # Utilities
|   |-- format.js         # Intl.DateTimeFormat (no dayjs dependency)
|-- views/
|   |-- auth/             # Auth views (Login)
|   |-- dashboard/        # Dashboard view
|   |-- admin/
|   |   |-- audit/        # Audit logs view
|   |   |-- branches/     # Branches CRUD
|   |   |-- permissions/  # Permissions grouped by resource
|   |   |-- roles/        # Roles CRUD
|   |   |-- settings/     # All settings views
|   |   |   |-- CurrenciesView.vue
|   |   |   |-- DatabaseBackupView.vue
|   |   |   |-- PaymentTypesView.vue
|   |   |   |-- SiteSettingsView.vue
|   |   |   |-- SmtpSettingsView.vue
|   |   |   |-- StoreSettingsView.vue
|   |   |   |-- TaxListView.vue
|   |   |   |-- TaxGroupsView.vue
|   |   |   |-- UnitsListView.vue
|   |   |-- users/        # Users CRUD
```

---

## Security Hardening

### 1. DB Password Protection (Backup)
```php
// BEFORE: Password visible in `ps aux`
$cmd = sprintf("mysqldump -u%s -p%s ...", $user, $password);
exec($cmd);

// AFTER: Password passed via env var, invisible in process list
putenv('MYSQL_PWD=' . $db['password']);
$process = proc_open($cmd, $descriptors, $pipes);
```

### 2. Branch-Scoped RBAC
```php
// Gate::before checks branch context
$user->hasRoleInBranch(config('rbac.super_admin_role'));

// Policies use branch-scoped checks
$user->hasPermissionInBranch('taxes.view');
```

### 3. Timing-Attack Resistant
```php
// Constant-time comparison (no early return)
$hasMatch = false;
foreach ($recentHashes as $hash) {
    $hasMatch = $hasMatch || Hash::check($value, $hash);
}
return !$hasMatch;
```

### 4. SMTP Config Isolation
```php
// Temporary mailer instance — never mutates global config
$transport = new EsmtpTransport($host, $port, $tls);
$mailer = new Mailer(new ArrayTransport()); // test mode
```

### 5. Rate Limiting
```php
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1'); // 5 attempts per minute
```

---

## Performance Optimizations

| Optimization | Where | Impact |
|-------------|-------|--------|
| `Cache::remember()` | Currency public index | Eliminates repeated DB queries |
| `Cache::remember()` | Settings options | Reference data cached for 1 hour |
| `DB::transaction()` | Role CRUD | Atomic operations, data consistency |
| `orderBy('created_at', 'desc')` | Permission index | New permissions visible first |
| `Intl.DateTimeFormat` | `format.js` | Zero dependency, native browser API |
| `per_page: 1000` | Permission index | Single page load, no pagination lag |

---

## Database Schema

### Core Tables
```
branches              - Multi-branch support
users                 - Staff accounts with branch_id
password_histories    - Last 5 password hashes per user
cache / cache_locks   - Application cache
jobs / job_batches    - Queue system
sessions              - User sessions
personal_access_tokens - Sanctum API tokens
```

### Settings Tables
```
 currencies           - Currency codes (USD, EUR, PKR...)
 payment_types        - Payment methods (Cash, Card...)
 site_settings        - Singleton (site name, logo, favicon)
 smtp_settings        - Singleton (host, port, encryption)
 store_settings       - Per-branch (timezone, date/time format, currency)
 taxes                - Tax rates (VAT, GST)
 tax_groups           - Tax groupings
 units                - Measurement units
 user_design_settings - Per-user theme preferences
```

### Spatie Permission Tables
```
 permissions           - All permissions (resource.action format)
 roles                 - All roles (super-admin, admin, manager...)
 model_has_roles       - User-role assignments with branch_id pivot
 model_has_permissions - Direct permission assignments
 role_has_permissions  - Role-permission mappings
```

### Audit Table
```
 audit_logs            - Every CRUD action logged with old/new values,
                         performer ID, IP, user agent
```

---

## API Documentation

### Base URL
```
/api/v1
```

### Authentication
All routes require `Authorization: Bearer {token}` header except login.

### Response Format (ApiResponse Trait)
```json
{
  "success": true,
  "message": "OK",
  "data": [],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 10,
    "total": 0,
    "from": 0,
    "to": 0
  }
}
```

### Auth Endpoints
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/login` | Authenticate (throttle: 5/min) |
| POST | `/logout` | Revoke token |
| GET | `/user` | Current user |
| POST | `/change-password` | Change with history check |
| POST | `/email/verification-notification` | Resend verification |

### Admin Endpoints
| Resource | Endpoints | Actions |
|----------|-----------|---------|
| `users` | `GET/POST /admin/users` | CRUD + import |
| `roles` | `GET/POST /admin/roles/{role}` | CRUD (transaction-wrapped) |
| `permissions` | `GET/POST /admin/permissions/{permission}` | CRUD |
| `branches` | `GET/POST /admin/branches/{branch}` | CRUD |
| `audit-logs` | `GET /admin/audit-logs` | List + stats + filters |
| `currencies` | `GET/POST /admin/currencies/{currency}` | CRUD + import/export |
| `payment-types` | `GET/POST /admin/payment-types` | CRUD + import/export |
| `taxes` | `GET/POST /admin/taxes/{tax}` | CRUD + import/export |
| `tax-groups` | `GET/POST /admin/tax-groups` | CRUD |
| `units` | `GET/POST /admin/units` | CRUD + import/export |

### Settings Endpoints
| Endpoint | Method | Description |
|----------|--------|-------------|
| `/admin/site-settings` | GET/POST | Site name, logo, favicon |
| `/admin/store-settings` | GET/POST | Per-branch store config |
| `/admin/smtp-settings` | GET/PUT/POST | SMTP config + test |
| `/admin/backups` | GET/POST/DELETE | Database backups |
| `/design-settings` | GET/PUT | User theme preferences |
| `/currencies/public` | GET | Public currency list (cached) |

---

## Directory Structure

### Backend
```
app/
  Helpers/DateTimeHelper.php
  Http/Controllers/Admin/
    AuthController.php
    AuditLogController.php
    BranchController.php
    PermissionController.php
    RoleController.php
    UserController.php
    UserDesignSettingController.php
    Settings/BackupController.php
    Settings/CurrencyController.php
    Settings/PaymentTypeController.php
    Settings/SiteSettingController.php
    Settings/SmtpSettingController.php
    Settings/StoreSettingController.php
    Settings/StoreSettingsService.php
    Settings/TaxController.php
    Settings/TaxGroupController.php
    Settings/UnitController.php
  Http/Middleware/
    EnsurePasswordIsNotExpired.php
    HandleBranchContext.php
  Jobs/LogAuditJob.php
  Models/
    AuditLog.php, Branch.php, Currency.php, PaymentType.php,
    PasswordHistory.php, Permission.php, Role.php,
    SiteSetting.php, SmtpSetting.php, StoreSetting.php,
    Tax.php, TaxGroup.php, Unit.php, User.php,
    UserDesignSetting.php
  Policies/
    AuditLogPolicy.php, CurrencyPolicy.php, PaymentTypePolicy.php,
    PermissionPolicy.php, RolePolicy.php, SiteSettingPolicy.php,
    SmtpSettingPolicy.php, StoreSettingPolicy.php,
    TaxGroupPolicy.php, TaxPolicy.php, UnitPolicy.php, UserPolicy.php
  Providers/
    AppServiceProvider.php
    AuthServiceProvider.php
  Services/
    AuditLogService.php
    Settings/StoreSettingsService.php
  Traits/
    ApiResponse.php
config/
  rbac.php
  queue.php
database/
  migrations/ (14 files)
```

### Frontend
```
holistichubpro-frontend/src/
  api/
    audit.js, auth.js, axios.js, branches.js,
    permissions.js, roles.js, settings.js, users.js
  components/
    auth/
    common/
    layout/AppLayout.vue, AppSidebar.vue
    modals/UserFormModal.vue
    ui/
      AppAlert.vue, AppAvatar.vue, AppBadge.vue,
      AppButton.vue, AppCard.vue, AppFormField.vue,
      AppModal.vue, AppPagination.vue, AppSelect.vue,
      AppTable.vue, AppToggle.vue
  composables/
  router/index.js
  stores/
    auth.js, settings.js, ui.js
  utils/
    format.js
  views/
    auth/LoginView.vue
    dashboard/DashboardView.vue
    admin/
      audit/AuditLogsView.vue
      branches/BranchesListView.vue
      permissions/PermissionsListView.vue
      roles/RolesListView.vue
      settings/
        CurrenciesView.vue, DatabaseBackupView.vue,
        PaymentTypesView.vue, SiteSettingsView.vue,
        SmtpSettingsView.vue, StoreSettingsView.vue,
        TaxGroupsView.vue, TaxListView.vue,
        UnitsListView.vue
      users/UsersListView.vue
```

---

## Artisan Commands Reference

### Creating Files

```bash
# Controller
php artisan make:controller Admin/ProductController

# API Controller (no views)
php artisan make:controller Api/OrderController --api

# Resource Controller
php artisan make:controller Admin/CategoryController --resource

# Model + Migration
php artisan make:model Product -m

# Model + Migration + Factory + Seeder
php artisan make:model Product -mfsc

# Migration
php artisan make:migration create_products_table

# Migration with foreign key
php artisan make:migration add_category_id_to_products_table --table=products

# Seeder
php artisan make:seeder ProductSeeder

# Factory
php artisan make:factory ProductFactory

# Request (validation)
php artisan make:request StoreProductRequest

# Form Request (API)
php artisan make:request Api/UpdateProductRequest

# Middleware
php artisan make:middleware CheckProductStock

# Policy
php artisan make:policy ProductPolicy --model=Product

# Service
php artisan make:class Services/ProductService

# Trait
php artisan make:class Traits/HasProductRelations

# Job (Queue)
php artisan make:job ProcessProductImport

# Event
php artisan make:event ProductCreated

# Listener
php artisan make:listener SendProductNotification --event=ProductCreated

# Mail
php artisan make:mail ProductLowStock --markdown=emails.products.low-stock

# Notification
php artisan make:notification ProductRestocked

# Rule (Custom Validation)
php artisan make:rule ValidProductSKU

# Scope
php artisan make:scope ActiveProductScope

# Channel (Broadcasting)
php artisan make:channel ProductChannel

# Exception
php artisan make:exception ProductNotFoundException

# Console Command
php artisan make:command SyncProducts

# Component (Blade)
php artisan make:component ProductCard

# Resource (API Transformer)
php artisan make:resource ProductResource

# Resource Collection
php artisan make:resource ProductCollection --collection

# Test
php artisan make:test ProductTest
php artisan make:test ProductApiTest --unit
```

### Running the Application

```bash
# Install dependencies
composer install

# Generate app key
php artisan key:generate

# Run migrations
php artisan migrate

# Run seeders
php artisan db:seed

# Run specific seeder
php artisan db:seed --class=RolePermissionSeeder

# Start development server
php artisan serve

# Start with custom host/port
php artisan serve --host=0.0.0.0 --port=8080

# Start queue worker (for async jobs)
php artisan queue:work

# Start queue worker with specific queue
php artisan queue:work --queue=default,audit-logs

# Run scheduler (for cron jobs)
php artisan schedule:run

# Cache routes (production)
php artisan route:cache

# Cache config (production)
php artisan config:cache

# Cache views (production)
php artisan view:cache

# Clear all caches
php artisan optimize:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Maintenance mode
php artisan down
php artisan up

# Check routes
php artisan route:list

# Check route with filter
php artisan route:list --name=users

# Tinker (REPL)
php artisan tinker

# Run tests
php artisan test

# Run tests with filter
php artisan test --filter=UserTest

# Run tests with coverage
php artisan test --coverage

# Migrate fresh (drop all tables)
php artisan migrate:fresh --seed

# Rollback last migration
php artisan migrate:rollback

# Rollback all migrations
php artisan migrate:reset

# Check migration status
php artisan migrate:status

# Generate IDE helpers (with barryvdh/laravel-ide-helper)
php artisan ide:generate
php artisan ide:meta

# Show environment
php artisan env

# Show config
php artisan config:show app.name
```

### Frontend Commands

```bash
# Navigate to frontend
cd holistichubpro-frontend

# Install dependencies
npm install

# Start dev server
npm run dev

# Build for production
npm run build

# Preview production build
npm run preview

# Lint
npm run lint
```

---

## Installation Guide

### Prerequisites
- PHP 8.4+
- Composer 2.x
- Node.js 18+
- MySQL 8.0+ (or SQLite for development)
- Redis (optional, for cache/sessions)

### Step 1: Clone & Install
```bash
git clone <repo-url> holistichubpro
cd holistichubpro
composer install
cd holistichubpro-frontend && npm install
```

### Step 2: Environment Setup
```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`:
```env
APP_NAME="HolisticHub Pro"
APP_ENV=local
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=holistichubpro
DB_USERNAME=root
DB_PASSWORD=your_password

QUEUE_CONNECTION=database
CACHE_STORE=database

SANCTUM_STATEFUL_DOMAINS=localhost:5173
FRONTEND_URL=http://localhost:5173

# Windows-specific (for backups)
MYSQLDUMP_PATH="C:\wamp64\bin\mysql\mysql8.0.40\bin\mysqldump.exe"
```

### Step 3: Database
```bash
php artisan migrate
php artisan db:seed
```

### Step 4: Storage Link
```bash
php artisan storage:link
```

### Step 5: Run
```bash
# Terminal 1: Backend
php artisan serve

# Terminal 2: Frontend
cd holistichubpro-frontend
npm run dev

# Terminal 3: Queue Worker (optional)
php artisan queue:work
```

Access the application at `http://localhost:5173`.

Default super-admin credentials: `super-admin@example.com` / password set during seeding.

---

## Environment Variables

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `APP_NAME` | Yes | Laravel | Application name |
| `APP_KEY` | Yes | - | Encryption key (auto-generated) |
| `APP_DEBUG` | Yes | true | Debug mode (false in production) |
| `APP_URL` | Yes | - | Application base URL |
| `DB_CONNECTION` | Yes | mysql | Database driver |
| `DB_HOST` | Yes | 127.0.0.1 | Database host |
| `DB_DATABASE` | Yes | - | Database name |
| `DB_USERNAME` | Yes | root | Database user |
| `DB_PASSWORD` | Yes | - | Database password |
| `QUEUE_CONNECTION` | No | database | Queue driver |
| `CACHE_STORE` | No | database | Cache driver |
| `SANCTUM_STATEFUL_DOMAINS` | Yes | - | Frontend domain for Sanctum |
| `FRONTEND_URL` | Yes | - | Frontend URL |
| `MYSQLDUMP_PATH` | No | auto | Full path to mysqldump.exe (Windows) |
| `RBAC_SUPER_ADMIN_ROLE` | No | super-admin | Super admin role name |

---

## License

Proprietary - All rights reserved.
