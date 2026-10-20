<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

        return view('admin.roles.index', compact('roles', 'permissionGroups'));
    }

    public function create()
    {
        return view('admin.roles.form', [
            'role'             => new Role(),
            'permissionGroups' => $this->groupedPermissions(),
            'selected'         => [],
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
        return view('admin.roles.form', [
            'role'             => $role,
            'permissionGroups' => $this->groupedPermissions(),
            'selected'         => $role->permissions->pluck('name')->all(),
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
