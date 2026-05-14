# HolisticHubPro

> Modern Service business management platform  
> Laravel 12 API + Vue 3 SPA | Multi-branch | RBAC | Audit Logging

---

## Quick Start

```bash
# Backend
cd holistichubpro
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve

# Frontend  
cd holistichubpro-frontend
npm install
cp .env.example .env
npm run dev
```

**Default Login:** `admin@holistichubpro.com` / `Admin@123!`

---

## Architecture

```
┌─────────────┐     Bearer Token      ┌─────────────┐     SQL      ┌─────────┐
│  Vue 3 SPA  │ ◄──────────────────► │ Laravel API │ ◄──────────► │  MySQL  │
│  (Pinia)    │    Sanctum Auth       │   (RBAC)    │   Eloquent   │         │
└─────────────┘                       └─────────────┘              └─────────┘
```

---

## Tech Stack

| Layer | Tech | Purpose |
|-------|------|---------|
| **Backend** | Laravel 12, PHP 8.4+ | REST API, Auth, Business Logic |
| **Frontend** | Vue 3, Pinia, Vite | SPA, State Management, Build |
| **Auth** | Laravel Sanctum | Token-based API Auth |
| **RBAC** | Spatie Permission | Roles & Permissions |
| **Queue** | Laravel Queue | Audit Logging (Async) |
| **DB** | MySQL | Primary Data Store |

---

## Project Structure

```
holistichubpro/
├── app/
│   ├── Events/
│   │   ├── EventServiceProvider.php
│   │   ├── PermissionChanged.php
│   │   └── RoleChanged.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/
│   │   │   │   ├── AuditLogController.php
│   │   │   │   ├── BranchController.php
│   │   │   │   ├── PermissionController.php
│   │   │   │   ├── RoleController.php
│   │   │   │   ├── UserController.php
│   │   │   │   └── Settings/
│   │   │   │       ├── BackupController.php
│   │   │   │       ├── CurrencyController.php
│   │   │   │       ├── PaymentTypeController.php
│   │   │   │       ├── SettingsOptionsController.php
│   │   │   │       ├── SiteSettingController.php
│   │   │   │       ├── SmtpSettingController.php
│   │   │   │       ├── StoreSettingController.php
│   │   │   │       ├── TaxController.php
│   │   │   │       ├── TaxGroupController.php
│   │   │   │       └── UnitController.php
│   │   │   ├── AuthController.php
│   │   │   └── Controller.php
│   │   │   └── UserDesignSettingController.php
│   │   └── Middleware/
│   │       └── EnsurePasswordIsNotExpired.php
│   │       └── HandleBranchContext.php
│   ├── Listeners/
│   │   ├── LogPermissionChange.php
│   │   └── LogRoleChange.php
│   ├── Models/
│   │   ├── AuditLog.php
│   │   ├── Branch.php
│   │   ├── Currency.php
│   │   ├── PasswordHistory.php
│   │   ├── PaymentType.php
│   │   ├── Permission.php
│   │   ├── Role.php
│   │   ├── SiteSetting.php
│   │   ├── SmtpSetting.php
│   │   ├── StoreSetting.php
│   │   ├── Tax.php
│   │   ├── TaxGroup.php
│   │   ├── Unit.php
│   │   └── User.php
│   │   └── UserDesignSetting.php
│   ├── Policies/
│   │   ├── AuditLogPolicy.php
│   │   ├── CurrencyPolicy.php
│   │   ├── PaymentTypePolicy.php
│   │   ├── PermissionPolicy.php
│   │   ├── RolePolicy.php
│   │   ├── SiteSettingPolicy.php
│   │   ├── SmtpSettingPolicy.php
│   │   ├── StoreSettingPolicy.php
│   │   ├── TaxGroupPolicy.php
│   │   ├── TaxPolicy.php
│   │   ├── UnitPolicy.php
│   │   └── UserPolicy.php
│   ├── Providers/
│   │   ├── AppServiceProvider.php
│   │   ├── AuthServiceProvider.php
│   │   ├── EventServiceProvider.php
│   │   └── RouteServiceProvider.php
│   ├── Rules/
│   │   ├── NotRecentPassword.php
│   │   └── SalonPassword.php
│   └── Services/
│       └── AuditLogService.php
├── config/
│   ├── app.php
│   ├── auth.php
│   ├── cache.php
│   ├── cors.php
│   ├── database.php
│   ├── filesystems.php
│   ├── logging.php
│   ├── mail.php
│   ├── permission.php
│   ├── queue.php
│   ├── sanctum.php
│   ├── services.php
│   └── session.php
├── database/
│   ├── migrations/
│   │   ├── 2026_01_01_000001_create_cache_jobs_tokens_tables.php
│   │   ├── 2026_01_01_000002_create_branches_users_password_histories_tables.php
│   │   ├── 2026_01_01_000003_create_permission_tables_with_branch_support.php
│   │   ├── 2026_01_01_000004_create_audit_logs_table.php
│   │   ├── 2026_04_12_221432_create_site_settings_table.php
│   │   ├── 2026_04_12_221438_create_store_settings_table.php
│   │   ├── 2026_04_12_221439_create_smtp_settings_table.php
│   │   ├── 2026_04_12_221440_create_taxes_table.php
│   │   ├── 2026_04_12_221441_create_tax_groups_table.php
│   │   ├── 2026_04_12_221443_create_units_table.php
│   │   ├── 2026_04_12_221444_create_payment_types_table.php
│   │   └── 2026_04_12_221445_create_currencies_table.php
│   │   └── 2026_04_30_220101_create_user_design_settings_table.php
│   └── seeders/
│       ├── DatabaseSeeder.php
│       └── InitialSetupSeeder.php
├── routes/
│   ├── api.php
│   ├── console.php
│   └── web.php
├── composer.json
└── .env
```

---

## Frontend Structure

```
holistichubpro-frontend/
│
├── public/
├── src/
│   ├── api/
│   │   ├── axios.js
│   │   ├── auth.js
│   │   ├── users.js
│   │   ├── roles.js
│   │   ├── permissions.js
│   │   ├── branches.js
│   │   ├── audit.js
│   │   └── settings.js
│   ├── components/
│   │   ├── auth/
│   │   │   ├── ChangePasswordView.vue
│   │   │   ├── EmailVerificationView.vue
│   │   │   └── LoginView.vue
│   │   ├── common/
│   │   │   ├── PasswordStrength.vue
│   │   │   └── PermissionGate.vue
│   │   ├── layout/
│   │   │   ├── AppHeader.vue
│   │   │   ├── AppLayout.vue
│   │   │   └── AppSidebar.vue
│   │   ├── modals/
│   │   │   └── UserFormModal.vue
│   │   └── ui/
│   │       ├── AppAlert.vue
│   │       ├── AppAvatar.vue
│   │       ├── AppBadge.vue
│   │       ├── AppButton.vue
│   │       ├── AppCard.vue
│   │       ├── AppFormField.vue
│   │       ├── AppModal.vue
│   │       ├── AppPagination.vue
│   │       ├── AppSelect.vue
│   │       ├── AppTable.vue
│   │       ├── AppToggle.vue
│   │       └── BranchSelector.vue
│   ├── router/
│   │   └── index.js
│   ├── stores/
│   │   ├── auth.js
│   │   ├── branch.js
│   │   ├── ui.js
│   │   └── settings.js
│   ├── utils/
│   │   └── format.js
│   ├── views/
│   │   ├── admin/
│   │   │   ├── users/
│   │   │   │   └── UsersListView.vue
│   │   │   ├── roles/
│   │   │   │   └── RolesListView.vue
│   │   │   ├── permissions/
│   │   │   │   └── PermissionsListView.vue
│   │   │   ├── branches/
│   │   │   │   └── BranchesListView.vue
│   │   │   ├── audit/
│   │   │   │   └── AuditLogsView.vue
│   │   │   └── settings/
│   │   │       ├── ColorPicker.vue
│   │   │       ├── CurrenciesView.vue
│   │   │       ├── DatabaseBackupView.vue
│   │   │       ├── GlobalDesignView.vue
│   │   │       ├── SettingsView.vue
│   │   │       ├── SiteSettingsView.vue
│   │   │       ├── StoreSettingsView.vue
│   │   │       ├── SmtpSettingsView.vue
│   │   │       ├── TaxListView.vue
│   │   │       ├── PaymentTypesView.vue
│   │   │       └── UnitsListView.vue
│   │   ├── auth/
│   │   │   ├── ChangePasswordView.vue
│   │   │   ├── EmailVerificationView.vue
│   │   │   └── LoginView.vue
│   │   ├── dashboard/
│   │   │   └── DashboardView.vue
│   │   └── NotFoundView.vue
│   ├── App.vue
│   ├── index.css
│   ├── main.js
│   └── style.css
├── index.html
├── package.json
├── postcss.config.js
├── tailwind.config.js
├── package-lock.json
└── vite.config.js
```

---

## Core Features

| Feature | Implementation |
|---------|---------------|
| **Auth** | Sanctum tokens, email verification, password expiry (90d) |
| **Security** | Account lockout (5 fails/30min), password history (5 last) |
| **RBAC** | `resource.action` pattern (e.g., `users.create`) |
| **Multi-Branch** | Branch-scoped data, branch-based user filtering |
| **Audit** | Event-driven logging with IP, user agent, old/new values |
| **Queues** | Async audit logging via Laravel Queue |

---

## API Quick Reference

### Auth
```http
POST   /api/login              {email, password}
POST   /api/logout             Bearer Token
GET    /api/user               Current user
POST   /api/change-password    {current_password, new_password}
```

### Admin (Requires: `admin` or `super-admin` role)
```http
GET    /api/admin/users              ?page, per_page, branch_id, role, search
POST   /api/admin/users              {name, email, password, roles[], branch_id}
GET    /api/admin/users/{id}
PUT    /api/admin/users/{id}
DELETE /api/admin/users/{id}
POST   /api/admin/users/import       (CSV file)
GET    /api/admin/users/{id}/audit-logs

GET    /api/admin/roles
POST   /api/admin/roles              {name, permissions[]}
PUT    /api/admin/roles/{id}
DELETE /api/admin/roles/{id}

GET    /api/admin/permissions

GET    /api/admin/branches
POST   /api/admin/branches           {name, address, ...}
PUT    /api/admin/branches/{id}
DELETE /api/admin/branches/{id}

GET    /api/admin/audit-logs         ?action, entity_type, performed_by, from_date, to_date, search, page
GET    /api/admin/audit-logs/filters
POST   /api/admin/audit-logs/bulk-delete  {ids[]}
DELETE /api/admin/audit-logs/{id}
```

---

## RBAC System

### Permission Pattern
```
{resource}.{action}

Resources: users, roles, permissions, branches, audit
Actions:   view, create, edit, delete, access, manage

Examples:
- users.view      → List/view users
- users.create    → Create new user
- roles.manage    → Full role management
```

### Roles Hierarchy
| Role | Permissions |
|------|-------------|
| `super-admin` | Bypass all (Gate::before) |
| `admin` | All permissions |
| `manager` | users, services, products, customers, appointments, pos, reports |
| `stylist` | customers.view/create, appointments.view/edit, services.view |
| `receptionist` | customers, appointments, pos.access |

### Super Admin Override
```php
// AuthServiceProvider.php
Gate::before(function ($user, $ability) {
    if ($user->hasRole('super-admin')) {
        return true;
    }
});
```

---

## Audit Logging

### Auto-Logged Events
| Event | Listener | Data Stored |
|-------|----------|-------------|
| `RoleAttached` | `LogRoleChange` | role_id, role_name, user_id |
| `RoleDetached` | `LogRoleChange` | role_id, role_name, user_id |
| `PermissionAttached` | `LogPermissionChange` | permission_id, permission_name, user_id |
| `PermissionDetached` | `LogPermissionChange` | permission_id, permission_name, user_id |

### Audit Log Schema
```php
Schema::create('audit_logs', function (Blueprint $table) {
    $table->id();
    $table->string('action');
    $table->string('entity_type');
    $table->unsignedBigInteger('entity_id');
    $table->unsignedBigInteger('performed_by')->nullable(); // no default
    $table->json('old_values')->nullable();
    $table->json('new_values')->nullable();
    $table->string('ip_address')->nullable();
    $table->text('user_agent')->nullable();
    $table->timestamps();
    $table->index(['entity_type', 'entity_id']);
    $table->index('performed_by');
    $table->index('created_at');
    $table->index('action');
});
```

### Fallback System
```php
// AuditLogService.php - 5-layer fallback for performed_by:
1. Explicit override
2. Authenticated user (Auth::id())
3. Entity being modified (for User updates)
4. user_id from old/new values
5. System user (ID: 1) – ultimate fallback
```

---

## Frontend State (Pinia)

```javascript
// stores/auth.js
const authStore = {
  state: {
    user: { id, name, email, roles[], permissions[], branch, last_login_ip },
    token: string,
    isAuthenticated: boolean,
    requiresPasswordChange: boolean
  },
  getters: {
    isSuperAdmin: () => user.roles.includes('super-admin'),
    hasPermission: (perm) => case‑insensitive check,
    userBranch: () => user.branch
  },
  actions: {
    login(credentials),
    logout(),
    changePassword({ current_password, password, password_confirmation }),
    fetchUser()
  }
}
```



## Environment Variables

```env
# Backend (.env)
APP_URL=http://localhost:8000
DB_DATABASE=holistichubpro_db
DB_USERNAME=root
DB_PASSWORD=

SANCTUM_STATEFUL_DOMAINS=localhost:3000
SESSION_DOMAIN=localhost

QUEUE_CONNECTION=database      # For audit logging

# Frontend (.env)
VITE_API_BASE_URL=http://localhost:8000/api   # includes /api
```

---

## Deployment Checklist

```bash
# Production setup
composer install --optimize-autoloader --no-dev
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart

npm ci
npm run build

# Supervisor (queue worker)
[program:holistichubpro-worker]
command=php artisan queue:work --sleep=3 --tries=3
```

---

## Database Seeding

```bash
php artisan migrate:fresh --seed

# Creates:
# - System user (ID: 1) – audit log fallback
# - Super admin (ID: 2) – admin@holistichubpro.com / Admin@123!
# - Main branch
# - All roles & permissions (guard_name = 'web')
```

---

## License

This README now accurately reflects the final production‑ready state of the application.
