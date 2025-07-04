# Per-Company Role & Permission Management

**Date:** 2025-07-04  
**Author:** AI Assistant / o3

## Background

Previously, roles & permissions were stored and managed globally. This prevented companies from tailoring their own access policies and forced everyone to follow the system-level (hard-coded) configuration.

## Objective

Allow each company to create and manage **its own** roles (and their permissions) while still supporting:

1. Global/system roles such as **SuperAdmin**.
2. Company-agnostic permissions that can be re-used across companies.

## Key Changes

1. **Database Schema**
    - Added composite unique indexes to `roles` and `permissions` to include `company_id` (`roles_company_name_guard_unique`, `permissions_company_name_guard_unique`).
    - Migration file: `2025_07_04_000000_update_roles_permissions_unique_index.php`.
2. **Model Enhancements**
    - `app/Models/Role.php` now auto-assigns `company_id` for new records using the authenticated user's company.
    - Added `Auth` import + `boot()` override with `creating` hook.
3. **Livewire Components**
    - **RoleList** (`app/Livewire/Permission/RoleList.php`) now displays roles that
        - belong to the current company **or** are global (`company_id=null`).
    - **RoleModal** (`app/Livewire/Permission/RoleModal.php`)
        - Finds roles scoped to the current company or global.
        - Automatically sets `company_id` when creating/updating.
4. **User Model**
    - `getAvailableRoles()` returns roles scoped to company/global instead of reading static config.
5. **Migration**
    - New migration updates unique constraints allowing duplicate role names across different companies.

## Usage

-   **SuperAdmin** continues to manage system-wide (global) roles by leaving `company_id` **NULL**.
-   **Company Administrator** sees only global + their company roles and can create/update their own.

## Future Work

-   Extend **RoleBackupService** to accept a `company_id` parameter so backups can be taken per company.
-   Apply similar company scope to **Permission** CRUD screens when (and if) editable by end-users.

## Automatic Seeding on Company Creation

-   Observer `CompanyObserver` ⟶ Job `SyncCompanyDefaultMasterData`.
-   Seeder added: `CompanyRolesPermissionsSeeder` which builds default roles for the new company.
-   Uses `config('seeder.current_company_id')` to scope roles.

## Integrity Command

-   Artisan command `php artisan company:roles-integrity {companyId?}`  
    • Without argument or `all`: scan every company plus global roles.  
    • With specific UUID: check only that company.
-   Reports missing roles or missing permissions per role so admins can re-seed.

## Company Permission Scope Management

-   Pivot table `company_permission` stores mapping of which permissions are usable by a company.
-   Model relation: `Company::allowedPermissions()`.
-   Livewire `RoleModal` now restricts Administrator to only pick permissions allowed for their company.
-   Artisan command for SuperAdmin:

```
# list allowed permissions
php artisan company:permissions {companyId} --list

# add permissions
php artisan company:permissions {companyId} --add="read report management" --add="export report management"

# remove permissions
php artisan company:permissions {companyId} --remove="delete supplier management"
```

This empowers SuperAdmin to tailor permission availability per company without touching individual roles.

---

Automatic diagrams are not required for this update because the changes are confined to backend models and Livewire components.
