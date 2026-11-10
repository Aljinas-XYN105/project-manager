<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    private const PROTECTED_ROLES = ['admin'];

    public function index()
    {
        $roles = Role::withCount(['users', 'permissions'])->orderBy('name')->get();
        $permissionGroups = $this->groupedPermissions();

        return Inertia::render('Admin/Roles/Index', [
            'roles' => $roles->map(fn ($r) => [
                'id' => $r->id, 'name' => $r->name, 'headline' => Str::headline($r->name),
                'users_count' => $r->users_count, 'permissions_count' => $r->permissions_count,
                'is_admin' => $r->name === 'admin',
                'edit_url' => route('admin.roles.edit', $r),
                'destroy_url' => route('admin.roles.destroy', $r),
                'users_url' => route('users.index', ['role' => $r->name]),
            ]),
            'permission_groups' => $permissionGroups->map(fn ($perms, $group) => [
                'group' => $group, 'headline' => Str::headline($group),
                'perms' => $perms->map(fn ($p) => [
                    'id' => $p->id, 'name' => $p->name,
                    'action_headline' => Str::headline(Str::after($p->name, '.')),
                    'destroy_url' => route('admin.permissions.destroy', $p),
                ])->values(),
            ])->values(),
            'store_permission_url' => route('admin.permissions.store'),
            'create_url' => route('admin.roles.create'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Roles/Form', [
            'role' => null,
            'permission_groups' => $this->groupedPermissions()->map(fn ($perms, $group) => [
                'group' => $group, 'headline' => Str::headline($group),
                'perms' => $perms->map(fn ($p) => ['name' => $p->name, 'action_headline' => Str::headline(Str::after($p->name, '.'))])->values(),
            ])->values(),
            'selected' => [],
            'is_admin_role' => false,
            'store_url' => route('admin.roles.store'),
            'back_url' => route('admin.roles.index'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
        $role->syncPermissions($data['permissions'] ?? []);
        $this->flushCache();

        return redirect()->route('admin.roles.index')->with('success', "Role \"{$role->name}\" created.");
    }

    public function edit(Role $role)
    {
        return Inertia::render('Admin/Roles/Form', [
            'role' => ['id' => $role->id, 'name' => $role->name, 'headline' => Str::headline($role->name)],
            'permission_groups' => $this->groupedPermissions()->map(fn ($perms, $group) => [
                'group' => $group, 'headline' => Str::headline($group),
                'perms' => $perms->map(fn ($p) => ['name' => $p->name, 'action_headline' => Str::headline(Str::after($p->name, '.'))])->values(),
            ])->values(),
            'selected' => $role->permissions->pluck('name')->all(),
            'is_admin_role' => $role->name === 'admin',
            'update_url' => route('admin.roles.update', $role),
            'back_url' => route('admin.roles.index'),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $data = $this->validated($request, $role);

        if (! in_array($role->name, self::PROTECTED_ROLES)) {
            $role->update(['name' => $data['name']]);
        }
        // Admin always keeps every permission so it can never lock itself out.
        $role->syncPermissions($role->name === 'admin' ? Permission::all() : ($data['permissions'] ?? []));
        $this->flushCache();

        return redirect()->route('admin.roles.index')->with('success', "Role \"{$role->name}\" updated.");
    }

    public function destroy(Role $role)
    {
        if (in_array($role->name, self::PROTECTED_ROLES)) {
            return back()->with('error', 'The admin role cannot be deleted.');
        }
        if ($role->users()->exists()) {
            return back()->with('error', "Reassign the {$role->users()->count()} member(s) with this role before deleting it.");
        }

        $role->delete();
        $this->flushCache();

        return back()->with('success', "Role \"{$role->name}\" deleted.");
    }

    public function storePermission(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9\-]+\.[a-zA-Z0-9\-]+$/', 'unique:permissions,name'],
        ], ['name.regex' => 'Use the format area.action, e.g. invoices.view']);

        $permission = Permission::create(['name' => $data['name'], 'guard_name' => 'web']);
        Role::findByName('admin')->givePermissionTo($permission);
        $this->flushCache();

        return back()->with('success', "Permission \"{$permission->name}\" created.");
    }

    public function destroyPermission(Permission $permission)
    {
        $permission->delete();
        $this->flushCache();

        return back()->with('success', "Permission \"{$permission->name}\" deleted.");
    }

    private function validated(Request $request, ?Role $role = null): array
    {
        return $request->validate([
            'name'          => ['required', 'string', 'max:60', 'regex:/^[a-z0-9_]+$/', Rule::unique('roles', 'name')->ignore($role?->id)],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ], ['name.regex' => 'Use lowercase letters, numbers and underscores only (e.g. team_lead).']);
    }

    private function groupedPermissions()
    {
        return Permission::orderBy('name')->get()->groupBy(fn ($p) => explode('.', $p->name)[0]);
    }

    private function flushCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
