# HolisticHubPro — Settings Module: Complete Implementation Prompt

---

## 1. Project Context

You are implementing the **Settings module** for **HolisticHubPro**, a Laravel 12 + Vue 3 SPA multi-branch management platform for salons and wellness businesses.

### What is already built and fully working

| Layer | What exists |
|-------|-------------|
| **Auth** | Laravel Sanctum (token-based SPA mode), email verification, password expiry (90 days), account lockout (5 attempts / 30 min) |
| **RBAC** | Spatie Laravel Permission — guard name is always `'web'`. Permissions follow `{resource}.{action}` naming (e.g. `users.view`, `branches.edit`) |
| **Multi-branch** | `branches` table; `branch_id` on users and role pivot; `BranchSelector` component for super-admin context switching |
| **Audit logging** | `AuditLogService::log($action, $model, $old, $new)` — call this in every controller `store` / `update` / `destroy` |
| **Modules complete** | Users, Roles, Permissions, Branches, Audit Logs — all CRUD, import/export, pagination fully working |
| **Frontend stack** | Vue 3 (Composition API `<script setup>`), Pinia, Vue Router 4, Vite, Tailwind CSS v4 |
| **Design system** | `AppTable`, `AppModal`, `AppButton`, `AppCard`, `AppFormField`, `AppPagination`, `AppAlert`, `AppBadge`, `AppAvatar`, `AppSelect`, `BranchSelector` — all in `src/components/ui/` |
| **Notifications** | `uiStore.addNotification({ type: 'success'|'error'|'warning', message })` — **never use `alert()`** |

### Non-negotiable patterns — follow these exactly

1. **Audit logging** — call `AuditLogService::log()` with old and new values in every `store`, `update`, and `destroy` controller method.
2. **Guard name** — always `'web'`. Never `'api'` or `'sanctum'`.
3. **Permissions** — add all new permissions to `InitialSetupSeeder` using `App\Models\Permission`, not Spatie's base class.
4. **Import** — accept a CSV file via `POST`, validate each row, wrap in a DB transaction, return `{ imported, skipped, errors }`.
5. **Export** — backend returns a JSON array of all matching records (not a CSV file). Frontend generates the CSV client-side using the native `Blob` API and triggers download via a temporary `<a>` element. No `file-saver` library.
6. **Pagination** — every list endpoint must accept `?page=`, `?per_page=` (10/25/50/100), `?search=`, `?status=` (active/inactive/all), `?sort_by=`, `?sort_dir=` (asc/desc).
7. **Validation** — use Laravel `FormRequest` classes. Return HTTP 422 with a JSON `errors` object.
8. **File uploads** — store in `storage/app/public/settings/`, run `php artisan storage:link`. Return the full public URL.
9. **Frontend state** — all server state (lists, pagination, filters, settings objects) lives in `src/stores/settings.js`. Components only call store actions; they never call the API directly.
10. **No `alert()`** — use `uiStore.addNotification(...)` for every success, error, and warning.
11. **Edit opens a deep clone** — `JSON.parse(JSON.stringify(item))` before populating the form. Never mutate the table row directly.
12. **Delete is always soft** — use `SoftDeletes` on all resource models. Hard delete is not exposed.

---

## 2. Database Migrations

Create one migration file per table in the order listed below.

### `site_settings` *(singleton — always exactly one row)*

| Column | Type | Notes |
|--------|------|-------|
| id | bigIncrements | |
| site_name | string | |
| site_logo | string, nullable | Storage path |
| timestamps | | |

### `store_settings` *(singleton — always exactly one row)*

| Column | Type | Notes |
|--------|------|-------|
| id | bigIncrements | |
| store_code | string, unique | Required |
| store_name | string | Required |
| mobile | string | Required |
| email | string | Required |
| phone | string, nullable | |
| gst_number | string, nullable | |
| tax_number | string, nullable | |
| pan_number | string, nullable | |
| store_website | string, nullable | |
| show_signature_on_invoice | boolean, default false | |
| signature | string, nullable | Storage path |
| bank_details | text, nullable | |
| store_logo | string, nullable | Storage path |
| branch_id | foreignId → branches | Not null, constrained |
| timezone | string, default 'UTC' | |
| date_format | string, default 'Y-m-d' | |
| time_format | string, default 'H:i:s' | |
| currency | string, default 'USD' | Currency code |
| currency_symbol_placement | enum('before','after'), default 'before' | |
| decimals | unsignedTinyInteger, default 2 | |
| decimals_for_quantity | unsignedTinyInteger, default 2 | |
| timestamps | | |

### `smtp_settings` *(singleton — always exactly one row)*

| Column | Type | Notes |
|--------|------|-------|
| id | bigIncrements | |
| status | boolean, default false | |
| host | string | |
| port | unsignedSmallInteger | |
| username | string | |
| password | text | Encrypted via `encrypt()` before saving |
| encryption | string, default 'tls' | tls / ssl / none |
| timestamps | | |

### `taxes`

| Column | Type | Notes |
|--------|------|-------|
| id | bigIncrements | |
| name | string, unique | |
| percentage | decimal(5,2) | 0.00–100.00 |
| status | boolean, default true | |
| created_by | foreignId → users, nullable | |
| softDeletes | | |
| timestamps | | |

Add index on `status` and `created_at`.

### `tax_groups`

| Column | Type | Notes |
|--------|------|-------|
| id | bigIncrements | |
| name | string, unique | |
| tax_ids | json | Array of tax IDs |
| calculated_percentage | decimal(5,2) | Sum of attached taxes — store computed value |
| status | boolean, default true | |
| created_by | foreignId → users, nullable | |
| softDeletes | | |
| timestamps | | |

### `units`

| Column | Type | Notes |
|--------|------|-------|
| id | bigIncrements | |
| name | string, unique | |
| description | text, nullable | |
| status | boolean, default true | |
| softDeletes | | |
| timestamps | | |

### `payment_types`

| Column | Type | Notes |
|--------|------|-------|
| id | bigIncrements | |
| name | string, unique | |
| status | boolean, default true | |
| softDeletes | | |
| timestamps | | |

### `currencies`

| Column | Type | Notes |
|--------|------|-------|
| id | bigIncrements | |
| name | string, unique | |
| code | string(3), unique | Uppercase, e.g. USD |
| symbol | string(10) | e.g. $, ₨, € |
| status | boolean, default true | |
| softDeletes | | |
| timestamps | | |

---

## 3. Eloquent Models

Create one model per table. All models must:

- Use `HasFactory`, `SoftDeletes` (except the three singleton models)
- Define `$fillable` for all non-primary, non-timestamp columns
- Define `$casts` (booleans, JSON columns, decimals as `string` to avoid float rounding)

### Special model requirements

**`Tax`**
```php
protected $casts = [
    'percentage' => 'string',
    'status'     => 'boolean',
];

public function creator(): BelongsTo
{
    return $this->belongsTo(User::class, 'created_by');
}
```

**`TaxGroup`**
```php
protected $casts = [
    'tax_ids'                => 'array',
    'calculated_percentage'  => 'string',
    'status'                 => 'boolean',
];

// Helper: resolve Tax models from stored tax_ids
public function taxes(): Collection
{
    return Tax::whereIn('id', $this->tax_ids ?? [])->get();
}

public function creator(): BelongsTo
{
    return $this->belongsTo(User::class, 'created_by');
}
```

**`SmtpSetting`**
```php
// Decrypt password on read
public function getPasswordAttribute(string $value): string
{
    try { return decrypt($value); } catch (\Throwable) { return ''; }
}

// Encrypt password on write
public function setPasswordAttribute(string $value): void
{
    $this->attributes['password'] = encrypt($value);
}
```

**`StoreSetting`**
```php
protected $casts = [
    'show_signature_on_invoice' => 'boolean',
    'decimals'                  => 'integer',
    'decimals_for_quantity'     => 'integer',
];
```

---

## 4. Permissions — Add to `InitialSetupSeeder`

Use `App\Models\Permission` (not Spatie's base class). Guard name is `'web'` for all.

```php
$newPermissions = [
    // Settings (singleton forms)
    'settings.view', 'settings.manage',

    // Taxes
    'taxes.view', 'taxes.create', 'taxes.edit', 'taxes.delete',

    // Tax Groups
    'tax_groups.view', 'tax_groups.create', 'tax_groups.edit', 'tax_groups.delete',

    // Units
    'units.view', 'units.create', 'units.edit', 'units.delete',

    // Payment Types
    'payment_types.view', 'payment_types.create', 'payment_types.edit', 'payment_types.delete',

    // Currencies
    'currencies.view', 'currencies.create', 'currencies.edit', 'currencies.delete',
];

foreach ($newPermissions as $name) {
    Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
}

$superAdmin = Role::where('name', 'super-admin')->first();
$admin      = Role::where('name', 'admin')->first();

$superAdmin->givePermissionTo($newPermissions);
$admin->givePermissionTo($newPermissions); // Adjust as needed per your role hierarchy
```

---

## 5. Policies

Create a Policy class for each resource: `TaxPolicy`, `TaxGroupPolicy`, `UnitPolicy`, `PaymentTypePolicy`, `CurrencyPolicy`.

Every policy must follow this template:

```php
class TaxPolicy
{
    // Super-admin bypasses all checks
    public function before(User $user): ?bool
    {
        return $user->hasRole('super-admin') ? true : null;
    }

    public function viewAny(User $user): bool   { return $user->hasPermissionTo('taxes.view'); }
    public function view(User $user): bool      { return $user->hasPermissionTo('taxes.view'); }
    public function create(User $user): bool    { return $user->hasPermissionTo('taxes.create'); }
    public function update(User $user): bool    { return $user->hasPermissionTo('taxes.edit'); }
    public function delete(User $user): bool    { return $user->hasPermissionTo('taxes.delete'); }
}
```

Register all five policies in `AuthServiceProvider::$policies`.

---

## 6. Backend Controllers & API Routes

All controllers go in `App\Http\Controllers\Admin\Settings\`.

### Route definitions — add to `routes/api.php`

Place these inside the existing `auth:sanctum` + `verified` middleware group:

```php
Route::prefix('admin')->group(function () {

    // Singleton settings
    Route::get('site-settings',  [SiteSettingController::class,  'show']);
    Route::put('site-settings',  [SiteSettingController::class,  'update']);

    Route::get('store-settings', [StoreSettingController::class, 'show']);
    Route::put('store-settings', [StoreSettingController::class, 'update']);

    Route::get('smtp-settings',  [SmtpSettingController::class,  'show']);
    Route::put('smtp-settings',  [SmtpSettingController::class,  'update']);
    Route::post('smtp-settings/test', [SmtpSettingController::class, 'test']);

    // Resource lists — export/import routes MUST come before apiResource()
    Route::get( 'taxes/export',  [TaxController::class, 'export']);
    Route::post('taxes/import',  [TaxController::class, 'import']);
    Route::apiResource('taxes',  TaxController::class);

    Route::apiResource('tax-groups', TaxGroupController::class);

    Route::get( 'units/export',  [UnitController::class, 'export']);
    Route::post('units/import',  [UnitController::class, 'import']);
    Route::apiResource('units',  UnitController::class);

    Route::get( 'payment-types/export', [PaymentTypeController::class, 'export']);
    Route::post('payment-types/import', [PaymentTypeController::class, 'import']);
    Route::apiResource('payment-types', PaymentTypeController::class);

    Route::get( 'currencies/export', [CurrencyController::class, 'export']);
    Route::post('currencies/import', [CurrencyController::class, 'import']);
    Route::apiResource('currencies', CurrencyController::class);

    // Database backup — super-admin only
    Route::post('backup',          [BackupController::class, 'create'])->middleware('can:super-admin');
    Route::get('backups',          [BackupController::class, 'index'])->middleware('can:super-admin');
    Route::get('backups/{file}/download', [BackupController::class, 'download'])->middleware('can:super-admin');
    Route::delete('backups/{file}', [BackupController::class, 'destroy'])->middleware('can:super-admin');
});

// Public route — no auth needed — used by Store Settings currency dropdown
Route::get('currencies', [CurrencyController::class, 'publicIndex']);
```

---

### `SiteSettingController`

- `show()` — return the single `SiteSettings` row; if none exists, call `SiteSettings::firstOrCreate([])` with sensible defaults and return it.
- `update(Request $request)` — validate `site_name` (required, string, max 255), `site_logo` (nullable, image, max 2048 KB). If logo uploaded: store via `$request->file('site_logo')->store('public/settings')`, delete old file. Call `AuditLogService::log('updated', $record, $old, $new)`. Return updated record.

---

### `StoreSettingController`

- `show()` — return record with `branch` relationship eager-loaded.
- `update(Request $request)` — validate all fields (see column table in section 2). Handle `store_logo` and `signature` uploads the same way as site logo. Call `AuditLogService::log(...)`. Return updated record.

---

### `SmtpSettingController`

- `show()` — return record; **mask the password** field (return `'••••••••'` if set, `null` if empty).
- `update(Request $request)` — if `password` field is sent and is not `'••••••••'`, encrypt and save it; otherwise leave existing password untouched. Call `AuditLogService::log(...)`.
- `test(Request $request)` — configure `config(['mail.*' => ...])` with the stored SMTP settings, then send a test email to `auth()->user()->email` using `Mail::raw(...)`. Return success or error message.

---

### Standard list controller pattern — `TaxController` (replicate for Unit, PaymentType, Currency)

```php
// index — paginated list
public function index(Request $request): JsonResponse
{
    $query = Tax::query()
        ->when($request->search,  fn($q) => $q->where('name', 'like', "%{$request->search}%"))
        ->when($request->status !== null && $request->status !== '',
               fn($q) => $q->where('status', filter_var($request->status, FILTER_VALIDATE_BOOLEAN)))
        ->orderBy($request->sort_by ?? 'name', $request->sort_dir ?? 'asc');

    return response()->json($query->paginate($request->per_page ?? 15));
}

// store
public function store(StoreTaxRequest $request): JsonResponse
{
    $tax = Tax::create([...$request->validated(), 'created_by' => auth()->id()]);
    AuditLogService::log('created', $tax, null, $tax->toArray());
    return response()->json($tax, 201);
}

// update
public function update(UpdateTaxRequest $request, Tax $tax): JsonResponse
{
    $old = $tax->toArray();
    $tax->update($request->validated());
    AuditLogService::log('updated', $tax, $old, $tax->fresh()->toArray());
    return response()->json($tax->fresh());
}

// destroy (soft delete)
public function destroy(Tax $tax): JsonResponse
{
    $old = $tax->toArray();
    $tax->delete();
    AuditLogService::log('deleted', $tax, $old, null);
    return response()->json(['message' => 'Deleted successfully']);
}

// export — returns full JSON array for client-side CSV generation
public function export(Request $request): JsonResponse
{
    $data = Tax::query()
        ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
        ->when($request->status !== null && $request->status !== '',
               fn($q) => $q->where('status', filter_var($request->status, FILTER_VALIDATE_BOOLEAN)))
        ->orderBy('name')
        ->get(['id', 'name', 'percentage', 'status', 'created_at']);
    return response()->json($data);
}

// import
public function import(Request $request): JsonResponse
{
    $request->validate(['file' => 'required|file|mimes:csv,txt|max:5120']);
    $path    = $request->file('file')->getRealPath();
    $rows    = array_map('str_getcsv', file($path));
    $header  = array_map('strtolower', array_shift($rows));
    // Expected columns: name, percentage, status
    $imported = $skipped = 0;
    $errors   = [];

    DB::beginTransaction();
    try {
        foreach ($rows as $i => $row) {
            $data = array_combine($header, $row);
            if (empty(trim($data['name'] ?? ''))) { $skipped++; continue; }
            $validated = validator($data, [
                'name'       => 'required|string|max:255',
                'percentage' => 'required|numeric|min:0|max:100',
                'status'     => 'nullable|in:0,1,true,false,active,inactive',
            ]);
            if ($validated->fails()) {
                $errors[] = "Row " . ($i + 2) . ": " . implode(', ', $validated->errors()->all());
                $skipped++;
                continue;
            }
            Tax::firstOrCreate(
                ['name' => trim($data['name'])],
                ['percentage' => $data['percentage'], 'status' => true, 'created_by' => auth()->id()]
            ) ? $imported++ : $skipped++;
        }
        DB::commit();
    } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json(['message' => 'Import failed: ' . $e->getMessage()], 500);
    }

    return response()->json(compact('imported', 'skipped', 'errors'));
}
```

---

### `TaxGroupController` — additional rules

- `store` / `update`: validate that all IDs in `tax_ids` exist in the `taxes` table. Compute `calculated_percentage = Tax::whereIn('id', $taxIds)->sum('percentage')` and store it.
- `index`: return groups with resolved tax names — loop `$group->taxes()` (use the helper method on the model).
- CSV import columns: `name, tax_names, status` — resolve tax names to IDs on import; skip row if any tax name is not found.

---

### `BackupController`

```php
public function create(): JsonResponse
{
    // Super-admin only (enforced by route middleware)
    $filename = 'backup_' . now()->format('Ymd_His') . '.sql';
    $path     = storage_path('app/backups/' . $filename);
    @mkdir(storage_path('app/backups'), 0755, true);

    $db   = config('database.connections.' . config('database.default'));
    $cmd  = sprintf(
        'mysqldump --user=%s --password=%s --host=%s %s > %s 2>&1',
        escapeshellarg($db['username']),
        escapeshellarg($db['password']),
        escapeshellarg($db['host']),
        escapeshellarg($db['database']),
        escapeshellarg($path)
    );
    exec($cmd, $output, $exitCode);

    if ($exitCode !== 0 || !file_exists($path)) {
        return response()->json(['message' => 'Backup failed'], 500);
    }

    // Clean backups older than 7 days
    foreach (glob(storage_path('app/backups/*.sql')) as $file) {
        if (filemtime($file) < strtotime('-7 days')) @unlink($file);
    }

    return response()->json([
        'filename'   => $filename,
        'size'       => filesize($path),
        'created_at' => now()->toISOString(),
    ]);
}

public function index(): JsonResponse
{
    $files = glob(storage_path('app/backups/*.sql')) ?: [];
    $list  = array_map(fn($f) => [
        'filename'   => basename($f),
        'size'       => filesize($f),
        'created_at' => date('Y-m-d H:i:s', filemtime($f)),
    ], $files);
    usort($list, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));
    return response()->json($list);
}

public function download(string $file): BinaryFileResponse
{
    $path = storage_path('app/backups/' . basename($file));
    abort_unless(file_exists($path), 404);
    return response()->download($path);
}

public function destroy(string $file): JsonResponse
{
    $path = storage_path('app/backups/' . basename($file));
    abort_unless(file_exists($path), 404);
    @unlink($path);
    return response()->json(['message' => 'Backup deleted']);
}
```

---

## 7. Frontend API Module — `src/api/settings.js`

```js
import api from './axios'

// Helper: trigger client-side CSV download from a JSON array
export function downloadCsv(rows, filename) {
    if (!rows.length) return
    const headers = Object.keys(rows[0])
    const csv = [
        headers.join(','),
        ...rows.map(r => headers.map(h => JSON.stringify(r[h] ?? '')).join(','))
    ].join('\n')
    const url = URL.createObjectURL(new Blob([csv], { type: 'text/csv' }))
    const a   = Object.assign(document.createElement('a'), { href: url, download: filename })
    document.body.appendChild(a)
    a.click()
    a.remove()
    URL.revokeObjectURL(url)
}

export const settingsApi = {
    // Site Settings
    getSite:    ()     => api.get('/admin/site-settings'),
    updateSite: (form) => api.post('/admin/site-settings', form), // FormData for logo

    // Store Settings
    getStore:    ()     => api.get('/admin/store-settings'),
    updateStore: (form) => api.post('/admin/store-settings', form), // FormData for images

    // SMTP
    getSmtp:    ()     => api.get('/admin/smtp-settings'),
    updateSmtp: (data) => api.put('/admin/smtp-settings', data),
    testSmtp:   ()     => api.post('/admin/smtp-settings/test'),

    // Taxes
    getTaxes:    (params) => api.get('/admin/taxes', { params }),
    createTax:   (data)   => api.post('/admin/taxes', data),
    updateTax:   (id, d)  => api.put(`/admin/taxes/${id}`, d),
    deleteTax:   (id)     => api.delete(`/admin/taxes/${id}`),
    exportTaxes: (params) => api.get('/admin/taxes/export', { params }),
    importTaxes: (file)   => { const f = new FormData(); f.append('file', file); return api.post('/admin/taxes/import', f) },

    // Tax Groups
    getTaxGroups:    (params) => api.get('/admin/tax-groups', { params }),
    createTaxGroup:  (data)   => api.post('/admin/tax-groups', data),
    updateTaxGroup:  (id, d)  => api.put(`/admin/tax-groups/${id}`, d),
    deleteTaxGroup:  (id)     => api.delete(`/admin/tax-groups/${id}`),

    // Units
    getUnits:    (params) => api.get('/admin/units', { params }),
    createUnit:  (data)   => api.post('/admin/units', data),
    updateUnit:  (id, d)  => api.put(`/admin/units/${id}`, d),
    deleteUnit:  (id)     => api.delete(`/admin/units/${id}`),
    exportUnits: (params) => api.get('/admin/units/export', { params }),
    importUnits: (file)   => { const f = new FormData(); f.append('file', file); return api.post('/admin/units/import', f) },

    // Payment Types
    getPaymentTypes:    (params) => api.get('/admin/payment-types', { params }),
    createPaymentType:  (data)   => api.post('/admin/payment-types', data),
    updatePaymentType:  (id, d)  => api.put(`/admin/payment-types/${id}`, d),
    deletePaymentType:  (id)     => api.delete(`/admin/payment-types/${id}`),
    exportPaymentTypes: (params) => api.get('/admin/payment-types/export', { params }),
    importPaymentTypes: (file)   => { const f = new FormData(); f.append('file', file); return api.post('/admin/payment-types/import', f) },

    // Currencies
    getCurrencies:    (params) => api.get('/admin/currencies', { params }),
    createCurrency:   (data)   => api.post('/admin/currencies', data),
    updateCurrency:   (id, d)  => api.put(`/admin/currencies/${id}`, d),
    deleteCurrency:   (id)     => api.delete(`/admin/currencies/${id}`),
    exportCurrencies: (params) => api.get('/admin/currencies/export', { params }),
    importCurrencies: (file)   => { const f = new FormData(); f.append('file', file); return api.post('/admin/currencies/import', f) },

    // Database Backup
    createBackup:   ()         => api.post('/admin/backup'),
    listBackups:    ()         => api.get('/admin/backups'),
    downloadBackup: (filename) => api.get(`/admin/backups/${filename}/download`, { responseType: 'blob' }),
    deleteBackup:   (filename) => api.delete(`/admin/backups/${filename}`),
}
```

---

## 8. Pinia Store — `src/stores/settings.js`

```js
import { defineStore } from 'pinia'
import { ref, reactive } from 'vue'
import { settingsApi, downloadCsv } from '@/api/settings'
import { useUiStore } from '@/stores/ui'

export const useSettingsStore = defineStore('settings', () => {
    const uiStore = useUiStore()

    // ── Singleton settings ────────────────────────────────────────────────
    const siteSettings  = ref({})
    const storeSettings = ref({})
    const smtpSettings  = ref({})

    // ── Resource lists ────────────────────────────────────────────────────
    const taxes        = ref({ data: [], meta: {} })
    const taxGroups    = ref({ data: [], meta: {} })
    const units        = ref({ data: [], meta: {} })
    const paymentTypes = ref({ data: [], meta: {} })
    const currencies   = ref({ data: [], meta: {} })

    // ── Filters (one reactive object per list) ────────────────────────────
    const taxFilters        = reactive({ search: '', status: '', page: 1, per_page: 15, sort_by: 'name', sort_dir: 'asc' })
    const taxGroupFilters   = reactive({ search: '', status: '', page: 1, per_page: 15, sort_by: 'name', sort_dir: 'asc' })
    const unitFilters       = reactive({ search: '', status: '', page: 1, per_page: 15, sort_by: 'name', sort_dir: 'asc' })
    const paymentTypeFilters= reactive({ search: '', status: '', page: 1, per_page: 15, sort_by: 'name', sort_dir: 'asc' })
    const currencyFilters   = reactive({ search: '', status: '', page: 1, per_page: 15, sort_by: 'name', sort_dir: 'asc' })

    // ── Loading flags ─────────────────────────────────────────────────────
    const loading   = reactive({ site: false, store: false, smtp: false,
                                  taxes: false, taxGroups: false, units: false,
                                  paymentTypes: false, currencies: false })
    const saving    = ref(false)
    const importing = ref(false)
    const exporting = ref(false)

    // ── Generic helpers ───────────────────────────────────────────────────
    async function fetchList(key, apiFn, filters, store) {
        loading[key] = true
        try {
            const res = await apiFn({ ...filters })
            store.value = { data: res.data.data ?? res.data, meta: res.data.meta ?? res.data }
        } catch (e) {
            uiStore.addNotification({ type: 'error', message: e.response?.data?.message ?? 'Failed to load data' })
        } finally {
            loading[key] = false
        }
    }

    async function saveItem(apiFn, successMsg) {
        saving.value = true
        try {
            const res = await apiFn()
            uiStore.addNotification({ type: 'success', message: successMsg })
            return res.data
        } catch (e) {
            const errors = e.response?.data?.errors
            if (!errors) uiStore.addNotification({ type: 'error', message: e.response?.data?.message ?? 'Operation failed' })
            throw e // re-throw so components can handle field-level errors
        } finally {
            saving.value = false
        }
    }

    async function importItems(apiFn, file, refetchFn) {
        importing.value = true
        try {
            const res = await apiFn(file)
            const { imported, skipped, errors } = res.data
            uiStore.addNotification({ type: 'success', message: `Imported ${imported}, skipped ${skipped}` })
            if (errors?.length) console.warn('Import errors:', errors)
            await refetchFn()
        } catch (e) {
            uiStore.addNotification({ type: 'error', message: e.response?.data?.message ?? 'Import failed' })
        } finally {
            importing.value = false
        }
    }

    async function exportItems(apiFn, filename, filters) {
        exporting.value = true
        try {
            const res = await apiFn({ ...filters, per_page: 10000 })
            downloadCsv(res.data, filename)
        } catch (e) {
            uiStore.addNotification({ type: 'error', message: 'Export failed' })
        } finally {
            exporting.value = false
        }
    }

    // ── Site Settings ─────────────────────────────────────────────────────
    const fetchSiteSettings  = async () => {
        loading.site = true
        try { siteSettings.value = (await settingsApi.getSite()).data } finally { loading.site = false }
    }
    const updateSiteSettings = (form) => saveItem(() => settingsApi.updateSite(form), 'Site settings saved')
        .then(data => { siteSettings.value = data })

    // ── Store Settings ────────────────────────────────────────────────────
    const fetchStoreSettings  = async () => {
        loading.store = true
        try { storeSettings.value = (await settingsApi.getStore()).data } finally { loading.store = false }
    }
    const updateStoreSettings = (form) => saveItem(() => settingsApi.updateStore(form), 'Store settings saved')
        .then(data => { storeSettings.value = data })

    // ── SMTP ──────────────────────────────────────────────────────────────
    const fetchSmtpSettings  = async () => {
        loading.smtp = true
        try { smtpSettings.value = (await settingsApi.getSmtp()).data } finally { loading.smtp = false }
    }
    const updateSmtpSettings = (data) => saveItem(() => settingsApi.updateSmtp(data), 'SMTP settings saved')
        .then(d => { smtpSettings.value = d })

    // ── Taxes ─────────────────────────────────────────────────────────────
    const fetchTaxes    = () => fetchList('taxes', settingsApi.getTaxes, taxFilters, taxes)
    const createTax     = (d) => saveItem(() => settingsApi.createTax(d), 'Tax created').then(fetchTaxes)
    const updateTax     = (id, d) => saveItem(() => settingsApi.updateTax(id, d), 'Tax updated').then(fetchTaxes)
    const deleteTax     = (id) => saveItem(() => settingsApi.deleteTax(id), 'Tax deleted').then(fetchTaxes)
    const importTaxes   = (file) => importItems(settingsApi.importTaxes, file, fetchTaxes)
    const exportTaxes   = () => exportItems(settingsApi.exportTaxes, 'taxes.csv', taxFilters)

    // ── Tax Groups ────────────────────────────────────────────────────────
    const fetchTaxGroups  = () => fetchList('taxGroups', settingsApi.getTaxGroups, taxGroupFilters, taxGroups)
    const createTaxGroup  = (d) => saveItem(() => settingsApi.createTaxGroup(d), 'Tax group created').then(fetchTaxGroups)
    const updateTaxGroup  = (id, d) => saveItem(() => settingsApi.updateTaxGroup(id, d), 'Tax group updated').then(fetchTaxGroups)
    const deleteTaxGroup  = (id) => saveItem(() => settingsApi.deleteTaxGroup(id), 'Tax group deleted').then(fetchTaxGroups)

    // ── Units ─────────────────────────────────────────────────────────────
    const fetchUnits  = () => fetchList('units', settingsApi.getUnits, unitFilters, units)
    const createUnit  = (d) => saveItem(() => settingsApi.createUnit(d), 'Unit created').then(fetchUnits)
    const updateUnit  = (id, d) => saveItem(() => settingsApi.updateUnit(id, d), 'Unit updated').then(fetchUnits)
    const deleteUnit  = (id) => saveItem(() => settingsApi.deleteUnit(id), 'Unit deleted').then(fetchUnits)
    const importUnits = (file) => importItems(settingsApi.importUnits, file, fetchUnits)
    const exportUnits = () => exportItems(settingsApi.exportUnits, 'units.csv', unitFilters)

    // ── Payment Types ─────────────────────────────────────────────────────
    const fetchPaymentTypes  = () => fetchList('paymentTypes', settingsApi.getPaymentTypes, paymentTypeFilters, paymentTypes)
    const createPaymentType  = (d) => saveItem(() => settingsApi.createPaymentType(d), 'Payment type created').then(fetchPaymentTypes)
    const updatePaymentType  = (id, d) => saveItem(() => settingsApi.updatePaymentType(id, d), 'Payment type updated').then(fetchPaymentTypes)
    const deletePaymentType  = (id) => saveItem(() => settingsApi.deletePaymentType(id), 'Payment type deleted').then(fetchPaymentTypes)
    const importPaymentTypes = (file) => importItems(settingsApi.importPaymentTypes, file, fetchPaymentTypes)
    const exportPaymentTypes = () => exportItems(settingsApi.exportPaymentTypes, 'payment-types.csv', paymentTypeFilters)

    // ── Currencies ────────────────────────────────────────────────────────
    const fetchCurrencies  = () => fetchList('currencies', settingsApi.getCurrencies, currencyFilters, currencies)
    const createCurrency   = (d) => saveItem(() => settingsApi.createCurrency(d), 'Currency created').then(fetchCurrencies)
    const updateCurrency   = (id, d) => saveItem(() => settingsApi.updateCurrency(id, d), 'Currency updated').then(fetchCurrencies)
    const deleteCurrency   = (id) => saveItem(() => settingsApi.deleteCurrency(id), 'Currency deleted').then(fetchCurrencies)
    const importCurrencies = (file) => importItems(settingsApi.importCurrencies, file, fetchCurrencies)
    const exportCurrencies = () => exportItems(settingsApi.exportCurrencies, 'currencies.csv', currencyFilters)

    return {
        siteSettings, storeSettings, smtpSettings,
        taxes, taxGroups, units, paymentTypes, currencies,
        taxFilters, taxGroupFilters, unitFilters, paymentTypeFilters, currencyFilters,
        loading, saving, importing, exporting,
        fetchSiteSettings, updateSiteSettings,
        fetchStoreSettings, updateStoreSettings,
        fetchSmtpSettings, updateSmtpSettings,
        fetchTaxes, createTax, updateTax, deleteTax, importTaxes, exportTaxes,
        fetchTaxGroups, createTaxGroup, updateTaxGroup, deleteTaxGroup,
        fetchUnits, createUnit, updateUnit, deleteUnit, importUnits, exportUnits,
        fetchPaymentTypes, createPaymentType, updatePaymentType, deletePaymentType, importPaymentTypes, exportPaymentTypes,
        fetchCurrencies, createCurrency, updateCurrency, deleteCurrency, importCurrencies, exportCurrencies,
    }
})
```

---

## 9. Vue Router — add to `src/router/index.js`

```js
{
    path: '/settings',
    component: () => import('@/views/admin/settings/SettingsView.vue'),
    meta: { requiresAuth: true, requiresPermission: 'settings.view' },
    children: [
        { path: '',               redirect: { name: 'SiteSettings' } },
        { path: 'site',           name: 'SiteSettings',     component: () => import('@/views/admin/settings/SiteSettingsView.vue') },
        { path: 'store',          name: 'StoreSettings',    component: () => import('@/views/admin/settings/StoreSettingsView.vue') },
        { path: 'smtp',           name: 'SmtpSettings',     component: () => import('@/views/admin/settings/SmtpSettingsView.vue') },
        { path: 'taxes',          name: 'TaxSettings',      component: () => import('@/views/admin/settings/TaxListView.vue') },
        { path: 'units',          name: 'UnitsSettings',    component: () => import('@/views/admin/settings/UnitsListView.vue') },
        { path: 'payment-types',  name: 'PaymentTypes',     component: () => import('@/views/admin/settings/PaymentTypesView.vue') },
        { path: 'currencies',     name: 'Currencies',       component: () => import('@/views/admin/settings/CurrenciesView.vue') },
        { path: 'change-password',name: 'SettingsPassword', component: () => import('@/views/auth/ChangePasswordView.vue') },
        { path: 'backup',         name: 'DatabaseBackup',   component: () => import('@/views/admin/settings/DatabaseBackupView.vue') },
    ]
}
```

---

## 10. Frontend View Specifications

### `SettingsView.vue` — shell layout

- Left sidebar showing all settings sub-links (matching the reference image: Store, Site Settings, SMTP, Tax List, Units List, Payment Types, Currency List, Change Password, Database Backup)
- Use `<router-link>` for each item; active state via `router-link-active` class
- Only show "Database Backup" if `authStore.isSuperAdmin`
- Only render items the user has `settings.view` permission for
- Right content area renders `<router-view />`

---

### `SiteSettingsView.vue`

Fields: `site_name` (text, required), `site_logo` (file input with image preview).
On save: build a `FormData` object (multipart), call `settingsStore.updateSiteSettings(formData)`.

---

### `StoreSettingsView.vue` — two tabs

**Tab 1 — General**

| Field | Input Type | Required |
|-------|-----------|----------|
| Store Code | text | yes |
| Store Name | text | yes |
| Mobile | text | yes |
| Email | email | yes |
| Phone | text | no |
| GST Number | text | no |
| Tax Number | text | no |
| PAN Number | text | no |
| Store Website | url | no |
| Show Signature on Invoice | toggle | no |
| Signature *(visible only when toggle is on)* | file, image | no |
| Bank Details | textarea | no |
| Store Logo | file, image, with preview | no |
| Branch | select, populated from `branchesApi.getAll()` | yes |

**Tab 2 — Localization**

| Field | Input Type | Options |
|-------|-----------|---------|
| Timezone | searchable select | PHP `timezone_identifiers_list()` — sent by backend on `GET /admin/store-settings` |
| Date Format | select | `d/m/Y`, `m/d/Y`, `Y-m-d`, `d-M-Y` |
| Time Format | select | `12 Hour (h:i A)`, `24 Hour (H:i)` |
| Currency | select | populated from `settingsStore.currencies.data` (active only) |
| Currency Symbol Placement | select | `Before Amount`, `After Amount` |
| Decimals | select | `0`, `1`, `2`, `3` |
| Decimals for Quantity | select | `0`, `1`, `2`, `3` |

---

### `SmtpSettingsView.vue`

Fields: status (toggle), host, port (number), username, password (type="password", never pre-fill), encryption (select: tls/ssl/none).
Include a **"Send Test Email"** button → calls `settingsStore.testSmtp()` → show notification.

---

### `TaxListView.vue` — two sections on one page

**Section A — Taxes**

Table columns: Name, Percentage (%), Status badge, Actions (Edit / Delete)
Header: `+ Add Tax`, `Import CSV`, `Export CSV`
Filters: search debounced (400ms), status select, per_page select

Create/Edit modal fields:
- `name` — text, required
- `percentage` — number 0–100, required
- `status` — toggle, default active

**Section B — Tax Groups** (below Section A, separated by a card)

Table columns: Group Name, Sub-Taxes (badge list), Total Percentage (%), Status badge, Actions (Edit / Delete)
Header: `+ Add Tax Group`

Create/Edit modal fields:
- `name` — text, required
- `tax_ids` — multi-select checkboxes, each option shows `Tax Name (XX%)`, populated from taxes list (active only)
- `calculated_percentage` — read-only display field, computed in real-time as sum of selected taxes' percentages
- `status` — toggle

---

### `UnitsListView.vue`

Table columns: Name, Description, Status badge, Actions
Header: `+ Add Unit`, `Import CSV`, `Export CSV`
Modal fields: `name` (required), `description` (textarea), `status` (toggle)
CSV import columns: `name, description, status`

---

### `PaymentTypesView.vue`

Table columns: Payment Type Name, Status badge, Actions
Header: `+ Add Payment Type`, `Import CSV`, `Export CSV`
Modal fields: `name` (required), `status` (toggle)
CSV import columns: `name, status`

---

### `CurrenciesView.vue`

Table columns: Name, Code, Symbol, Status badge, Actions
Header: `+ Add Currency`, `Import CSV`, `Export CSV`
Modal fields: `name` (required), `code` (required, 3 chars, auto-uppercase on input), `symbol` (required), `status` (toggle)
CSV import columns: `name, code, symbol, status`

---

### `DatabaseBackupView.vue`

- Accessible only if `authStore.isSuperAdmin` (redirect to `/403` if not)
- `Create Backup Now` button — calls store action, shows spinner, on success adds to list
- Table of existing backups: Filename, Size (formatted KB/MB), Created Date, Actions (Download / Delete)
- Download streams the file from the backend as a blob and triggers browser download

---

## 11. Sidebar Navigation Update

In the main app layout (wherever the primary sidebar nav is defined), add a collapsible `Settings` group:

```
⚙  Settings
   ├─ Store               → /settings/store
   ├─ Site Settings       → /settings/site
   ├─ SMTP                → /settings/smtp
   ├─ Tax List            → /settings/taxes
   ├─ Units List          → /settings/units
   ├─ Payment Types       → /settings/payment-types
   ├─ Currency List       → /settings/currencies
   ├─ Change Password     → /settings/change-password
   └─ Database Backup     → /settings/backup  [super-admin only]
```

The group is visible if the user has `settings.view`. Each child item is rendered only if the user has the relevant permission.

---

## 12. Default Data Seeding

Add to `InitialSetupSeeder`, after permissions are created:

```php
// Default currencies
$currencies = [
    ['name' => 'US Dollar',          'code' => 'USD', 'symbol' => '$',  'status' => true],
    ['name' => 'Euro',               'code' => 'EUR', 'symbol' => '€',  'status' => true],
    ['name' => 'British Pound',      'code' => 'GBP', 'symbol' => '£',  'status' => true],
    ['name' => 'Pakistani Rupee',    'code' => 'PKR', 'symbol' => '₨',  'status' => true],
    ['name' => 'Indian Rupee',       'code' => 'INR', 'symbol' => '₹',  'status' => true],
];
foreach ($currencies as $c) Currency::firstOrCreate(['code' => $c['code']], $c);

// Default tax
Tax::firstOrCreate(['name' => 'VAT 0%'], ['percentage' => 0.00, 'status' => true]);

// Default unit
Unit::firstOrCreate(['name' => 'Piece'], ['description' => 'Single unit', 'status' => true]);

// Default payment type
PaymentType::firstOrCreate(['name' => 'Cash'], ['status' => true]);

// Default store settings (only if no row exists)
if (!StoreSetting::exists()) {
    StoreSetting::create([
        'store_code'  => 'HHP-001',
        'store_name'  => 'HolisticHubPro',
        'mobile'      => '+1000000000',
        'email'       => 'admin@holistichubpro.com',
        'branch_id'   => Branch::first()?->id ?? 1,
        'timezone'    => 'UTC',
        'date_format' => 'Y-m-d',
        'time_format' => 'H:i:s',
        'currency'    => 'USD',
        'currency_symbol_placement' => 'before',
        'decimals'    => 2,
        'decimals_for_quantity' => 2,
    ]);
}
```

---

## 13. Deliverables Checklist

Generate complete, ready-to-copy code for each item in this order:

1. [ ] **Migrations** — all 8 tables in the order listed in section 2
2. [ ] **Models** — all 8 models with `$fillable`, `$casts`, relationships
3. [ ] **Form Requests** — `StoreTaxRequest`, `UpdateTaxRequest`, `StoreTaxGroupRequest`, `StoreUnitRequest`, `StorePaymentTypeRequest`, `StoreCurrencyRequest`
4. [ ] **Policies** — all 5 resource policies
5. [ ] **Controllers** — all 8 controllers, complete with every method
6. [ ] **Seeder update** — additions to `InitialSetupSeeder.php`
7. [ ] **Route additions** — paste-ready block for `routes/api.php`
8. [ ] **`src/api/settings.js`** — complete API module
9. [ ] **`src/stores/settings.js`** — complete Pinia store
10. [ ] **`src/views/admin/settings/SettingsView.vue`** — layout shell
11. [ ] **`SiteSettingsView.vue`**, **`StoreSettingsView.vue`**, **`SmtpSettingsView.vue`**
12. [ ] **`TaxListView.vue`** (both Tax List and Tax Groups sections)
13. [ ] **`UnitsListView.vue`**, **`PaymentTypesView.vue`**, **`CurrenciesView.vue`**
14. [ ] **`DatabaseBackupView.vue`**
15. [ ] **Router update** — additions to `src/router/index.js`
16. [ ] **Sidebar update** — changes to the layout component

Follow exactly the patterns used in the existing `BranchesListView.vue`, `UsersListView.vue`, and `src/stores/auth.js`. Provide the code file by file in the order above.
