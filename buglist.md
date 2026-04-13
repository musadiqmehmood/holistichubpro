# Known Issues & Technical Debt

This document lists all identified issues in the backend codebase as of the last review.  
Severity levels: **🔴 Critical** | **🟠 Major** | **🟡 Minor**

---

## 🔴 Critical

### 1. Branch‑scoped roles are stored but never enforced
- **Location**: `model_has_roles` pivot, `User` model, all policies
- **Impact**: A user with role `manager` in Branch A will pass `$user->hasRole('manager')` even when operating in Branch B → cross‑branch privilege leakage.
- **Fix required**: Override Spatie’s `hasRole()` / `can()` to check `branch_id` or implement a custom authorization layer.

### 2. Hardcoded system user ID (`1`) in audit fallback
- **Location**: `AuditLogService::SYSTEM_USER_ID = 1`, `AuditLog::boot()`
- **Impact**: If the system user is deleted or its ID is not `1`, foreign key violations or incorrect audit trails occur.
- **Fix required**: Look up system user dynamically or use a configuration value.

### 3. `AuthController::sendPasswordExpiredResponse()` creates unusable token
- **Location**: `AuthController.php`, line ~162
- **Impact**: Token uses ability `['password:change']` which is not defined anywhere → password‑change flow broken.
- **Fix required**: Define the ability in Sanctum configuration or use a different approach (e.g., a dedicated endpoint with the existing token).

### 4. `SmtpSettingController::test()` permanently mutates global mail config
- **Location**: `SmtpSettingController.php`, `test()` method
- **Impact**: Any other email sent in the same request (queued jobs, notifications) will use the tested SMTP settings, not the original configuration.
- **Fix required**: Use a temporary mailer instance instead of modifying `config()` globally.

### 5. User import accepts plain‑text passwords in CSV
- **Location**: `UserController::import()`
- **Impact**: Plain‑text passwords are uploaded and stored temporarily → security exposure for all imported users.
- **Fix required**: Require pre‑hashed passwords, or document the risk and enforce HTTPS + immediate deletion after import.

---

## 🟠 Major

### 6. CSV import uses `file()` + `str_getcsv` instead of proper parser
- **Location**: `CurrencyController`, `PaymentTypeController`, `TaxController`, `UnitController`
- **Impact**: Breaks on quoted fields, embedded newlines, and escaped delimiters. Inconsistent with `UserController` (which uses `League\Csv\Reader`).
- **Fix required**: Replace with `League\Csv\Reader` across all import methods.

### 7. Missing branch context in all policies
- **Location**: Every policy (e.g., `UserPolicy`, `RolePolicy`, etc.)
- **Impact**: A user with `users.view` permission can view all users across all branches because the pivot `branch_id` is never checked.
- **Fix required**: Inject branch context into policy methods (e.g., require `branch_id` parameter or use a `BranchScope` middleware).

### 8. `StoreSettingController::show()` returns `null` when no settings exist
- **Location**: `StoreSettingController.php`, `show()` method
- **Impact**: API contract implies `data` is always an object; frontend must handle `null` unnecessarily.
- **Fix required**: Create default settings on the fly or return an empty object instead of `null`.

### 9. `BackupController` exposes database password in process list
- **Location**: `BackupController.php`, `create()` method
- **Impact**: Any user with `ps` or process listing can see the plain‑text password.
- **Fix required**: Use `MYSQL_PWD` environment variable or a temporary config file.

### 10. `AuthController::resendVerification()` lacks rate limiting
- **Location**: `AuthController.php`, `resendVerification()` method
- **Impact**: Potential abuse (spam, mailbox flooding).
- **Fix required**: Apply Laravel’s rate limiter to this endpoint.

### 11. User import fails silently when `branch_id` is null
- **Location**: `UserController::import()`, row processing
- **Impact**: If CSV omits `branch_id`, `syncRoles()` throws an exception with a misleading error message (“Branch ID does not exist”).
- **Fix required**: Validate that `branch_id` is provided for users with roles, or skip role assignment with a clear warning.

---

## 🟡 Minor / Improvements

### 12. `PermissionController::index()` returns a flat array without pagination
- **Location**: `PermissionController.php`, `index()`
- **Impact**: Performance issue for large numbers of permissions.
- **Suggestion**: Implement pagination or at least a `per_page` parameter.

### 13. `TaxGroupController::index()` wraps paginated data inside `data` key
- **Location**: `TaxGroupController.php`, `index()`
- **Impact**: Inconsistent API response shape across settings endpoints.
- **Suggestion**: Return paginator directly (like `TaxController::index()`).

### 14. `SmtpSettingController` sentinel value `••••••••` is only masked in the response
- **Location**: `SmtpSettingController.php`, `masked()` method
- **Impact**: If any other code serializes the model (e.g., audit log), the real password could leak.
- **Suggestion**: Override the model’s `toArray()` method to always mask the password.

### 15. `created_by` foreign key is never populated
- **Location**: Migrations for `taxes`, `tax_groups`
- **Impact**: `created_by` remains `null` for all records.
- **Suggestion**: Set `created_by` automatically from `auth()->id()` in controllers.

### 16. Duplicate password validation logic
- **Location**: `AuthServiceProvider` (custom validators) and separate rule classes (`SalonPassword`, `NotRecentPassword`)
- **Impact**: Code duplication, potential maintenance confusion.
- **Suggestion**: Use only the rule classes and remove the inline validators.

### 17. `AppServiceProvider` has commented‑out `Gate::before`
- **Location**: `AppServiceProvider.php`
- **Impact**: Dead code that may confuse future developers.
- **Suggestion**: Remove the commented block.

### 18. `LogPermissionChange` and `LogRoleChange` capture IP/UA but never pass them
- **Location**: `LogPermissionChange.php`, `LogRoleChange.php`
- **Impact**: Captured values are wasted; the service methods rely on `request()` helper instead.
- **Suggestion**: Pass IP/UA to `AuditLogService` for consistency.

---

## 📌 Notes (Non‑critical observations)

- The seeder runs inside a transaction – good practice.
- Overridden `assignRole`/`syncRoles` in `User` correctly handle the custom pivot `branch_id`.
- `SettingsOptionsController` reduces frontend round trips – good design.
- `salon_password` rule accepts **any** non‑alphanumeric character as a special character – intentional improvement.
- `AuthServiceProvider`’s `Gate::before` is correctly implemented; `AppServiceProvider`’s is commented out – no conflict.

---

**Last updated:** 2026-04-13  
**Reviewer:** Backend Team
