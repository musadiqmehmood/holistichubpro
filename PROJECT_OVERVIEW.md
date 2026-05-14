# HolisticHubPro - System Reference

## Stack
- Laravel 12 + Sanctum + Spatie RBAC + Queue
- Vue 3 + Vite + Pinia + Tailwind v4

## Key Architecture Decisions
- branch_id injected into model_has_roles pivot
- Queue-based async audit logging
- Per-user dynamic theme via CSS variables
- Permission pattern: {resource}.{action}

## Critical Files (Backend)
- app/Models/User.php (custom assignRole/syncRoles)
- app/Http/Controllers/Admin/UserController.php
- routes/api.php (permission-based middleware)
- app/Services/AuditLogService.php

## Critical Files (Frontend)
- src/stores/auth.js (case-insensitive permissions)
- src/stores/settings.js (dynamic theme)
- src/router/index.js (navigation guards)
- src/style.css (aggressive !important overrides)

## Known Issues
- style.css breaks hover backgrounds globally
- AppToggle.vue uses TypeScript (no TS dep)
- dayjs used in format.js but not in package.json
- branches.js delete route missing leading slash