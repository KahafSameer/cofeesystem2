# Branch Management — Testing Guide

This guide explains how to test the newly added **Branch Management** feature in the
Laravel Coffee Shop POS, and exactly where each piece is implemented.

---

## What the feature does

- An admin can **create**, **edit**, and **activate/deactivate** branches (no hard delete).
- A branch has: `name` (required), `address` (optional), `status` (Active / Inactive).
- Staff users (`cashier`, `chef`, `waiter`) **must** be assigned to a branch; `admin`
  and `user` (customer) accounts stay branch-less.
- Only **Active** branches can be assigned to staff.

---

## Where it's implemented (file map)

### 1. Database / migrations
| File | Purpose |
|------|---------|
| `database/migrations/2026_08_28_164934_create_branches_table.php` | Creates the `branches` table (`name`, `address`, `status` default `Active`) |
| `database/migrations/2026_08_28_164946_add_branch_id_to_users_table.php` | Adds nullable `branch_id` FK to `users` (sets to `NULL` if branch deleted) |

> Both already ran — verify with: `php artisan migrate:status`

### 2. Models
| File | Purpose |
|------|---------|
| `app/Models/Branch.php` | Branch model: `$fillable = ['name','address','status']`, `users()` hasMany relation |
| `app/Models/User.php` | Added `'branch_id'` to `$fillable` and a `branch()` belongsTo relation |

### 3. Controller
`app/Http/Controllers/Admin/BranchController.php` — methods:
- `index` → `branch.list` view (paginated list)
- `create` → show create form
- `store` → validate + create (name required/unique, address nullable, status in Active/Inactive)
- `edit` → show edit form for a branch (`findOrFail`)
- `update` → validate + update existing branch
- `toggleStatus` → flips Active ↔ Inactive

### 4. Routes
`routes/admin.php` — added import + a route group under the `admin` middleware:
```
admin/branch/list     (branch.index)
admin/branch/create   (branch.create)
admin/branch/store    (branch.store)
admin/branch/edit/{id}(branch.edit)
admin/branch/update/{id} (branch.update)
admin/branch/status/{id} (branch.status)
```

### 5. Views
| File | Purpose |
|------|---------|
| `resources/views/admin/branch/list.blade.php` | Branch list with Edit + Activate/Deactivate buttons |
| `resources/views/admin/branch/create.blade.php` | Add branch form |
| `resources/views/admin/branch/edit.blade.php` | Edit branch form |
| `resources/views/admin/layouts/master.blade.php` | Sidebar link "Branches" (fa-store icon, under Settings) |

### 6. Asset Management (assets assigned to a branch)
| File | Purpose |
|------|---------|
| `database/migrations/2026_08_29_003655_add_branch_id_to_assets_table.php` | Adds nullable `branch_id` FK to `assets` |
| `app/Models/Asset.php` | Added `'branch_id'` to `$fillable` + `branch()` belongsTo |
| `app/Http/Controllers/Admin/AssetController.php` | `asset_create`/`asset_edit` pass Active `$branches`; `asset_store`/`asset_update` validate `branch_id` (nullable/exists) |
| `resources/views/admin/asset/partials/form.blade.php` | Branch dropdown in the asset add/edit form |
| `resources/views/admin/asset/list.blade.php` | Branch column in the asset list |

### 7. Staff assignment (who belongs to which branch)
`app/Http/Controllers/Admin/ProfileController.php`:
- `createNewUser` → passes **Active** branches to the create-user form.
- `addNewUser` → requires `branch_id` when role is `cashier`/`chef`/`waiter`; forces
  `branch_id = null` for `admin`/`user`.
- `changeProfilePage` → selects `branch_id`, passes Active `$branches`.
- `updateField` → handles `field=branch` (assign branch), clears branch when role
  becomes `admin`/`user`, a
- `resources/views/admin/profile/createprofile.blade.php` — Branch dropdown that
  appears/hides via `toggleBranchField()` JS based on selected role.
- `resources/views/admin/profile/changeprofile.blade.php` — per-user Branch `<select>`
  (auto-saves on change) shown only for `cashier`/`chef`/`waiter`.

---

## Pre-requisites

1. App is running: `php artisan serve`
2. Migrations are applied: `php artisan migrate`
3. Log in as an **admin** account.

---

## Test Plan

### A. Branch CRUD (Admin)

1. **List page**
   - Go to **Branches** in the sidebar (Settings → Branches) → URL `admin/branch/list`.
   - Confirm an empty list shows "No branches found" (if none exist yet).

2. **Create a branch**
   - Click **Add New Branch** → URL `admin/branch/create`.
   - Fill: Branch Name = `Branch 1`, Address = `Lahore`, Status = `Active`.
   - Click **Save Branch**.
   - **Expected:** success alert, redirected to the list, `Branch 1` appears with
     status badge **Active**.

3. **Validation**
   - Try saving with an empty name → should show "The name field is required."
   - Try creating a duplicate name (`Branch 1` again) → should show uniqueness error.

4. **Edit a branch**
   - On the list, click **Edit** → `admin/branch/edit/{id}`.
   - Change the name/address/status, click **Update Branch**.
   - **Expected:** success alert, changes reflected in the list.

5. **Deactivate / Activate**
   - On the list, click **Deactivate** (confirm dialog appears).
   - **Expected:** status badge flips to **Inactive**.
   - Click **Activate** → flips back to **Active**.
   - Note: there is **no Delete** — by design, branches are only toggled.

### B. Required branch for staff (create user)

1. Go to **Create New User** (Add User) → `admin/profile/create-profile` (route
   `profile.createNewUser`).
2. Select role = **Cashier** → the **Branch** dropdown should appear automatically.
   - **Expected:** Branch field is required; save with a branch selected succeeds.
   - Try saving with **no branch** selected → should error
     ("The branch id field is required...").
3. Select role = **Admin** (or User) → the **Branch** dropdown should **hide** and
   the field should be ignored (stays `NULL`).

### C. Assign / change branch on existing staff

1. Go to **Change User Profile** → `admin/profile/change-profile`.
2. Find a `cashier`/`chef`/`waiter` row.
   - A **Branch** column with a dropdown should be visible for them; `admin`/`user`
     rows show a dash `-`.
3. Change the branch via the dropdown → it auto-saves on change.
   - **Expected:** success alert "Branch updated successfully!".

### D. Verify in the database

Run (in the app's DB shell) e.g.:
```sql
SELECT id, name, email, role, branch_id FROM users;
SELECT id, name, address, status FROM branches;
```
- Staff rows should have a `branch_id` pointing to a valid branch.
- Admin / customer accounts should have `branch_id = NULL`.

### E. Only Active branches are assignable

1. Create a branch and set it to **Inactive**.
2. Go to **Create New User** → role **Cashier**.
   - **Expected:** the Inactive branch does **not** appear in the Branch dropdown.

### F. Branch in Asset Management

1. **Add a branch to an asset** — Go to **Assets** (Asset Management) → **Add Asset**
   (`admin/assets/create`).
   - The form now has a **Branch** dropdown (next to Assigned User) listing only
     **Active** branches.
   - Pick a branch, fill the required fields, click **Save**.
   - **Expected:** redirect to the asset list, success alert, and the **Branch**
     column shows the selected branch for that asset.
2. **Edit an asset's branch** — On the asset list, click the edit (pencil) button,
   change the Branch, click **Update**.
   - **Expected:** branch updated and reflected in the list.
3. **No branch assigned** — leave Branch as "Not Assigned" when adding/editing.
   - **Expected:** asset saves fine; the Branch column shows `-`.
4. **DB check**:
   ```sql
   SELECT id, name, branch_id FROM assets;
   ```
   - The branch column should hold the chosen branch id or `NULL` when not assigned.

---

## Quick checklist
- [ ] Branch list loads at `admin/branch/list`
- [ ] Create / Edit / Activate / Deactivate all work with success alerts
- [ ] Name uniqueness + required validation works
- [ ] Branch dropdown appears for cashier/chef/waiter and hides for admin/user
- [ ] Staff saved without a branch errors; staff saved with a branch succeeds
- [ ] Inactive branches are excluded from assignment dropdowns
- [ ] `branch_id` is `NULL` for admin/user rows in DB
- [ ] Asset form has a Branch dropdown (add/edit) with only Active branches
- [ ] Asset list shows a Branch column; unassigned assets show `-`
