<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'View Organizations', 'slug' => 'organizations.view', 'module' => 'organizations', 'description' => 'View organizations list and details'],
            ['name' => 'Create Organizations', 'slug' => 'organizations.create', 'module' => 'organizations', 'description' => 'Create new organizations'],
            ['name' => 'Edit Organizations', 'slug' => 'organizations.edit', 'module' => 'organizations', 'description' => 'Edit organizations'],
            ['name' => 'Delete Organizations', 'slug' => 'organizations.delete', 'module' => 'organizations', 'description' => 'Delete organizations'],
            ['name' => 'View Users', 'slug' => 'users.view', 'module' => 'users', 'description' => 'View users list and details'],
            ['name' => 'Create Users', 'slug' => 'users.create', 'module' => 'users', 'description' => 'Create new users'],
            ['name' => 'Edit Users', 'slug' => 'users.edit', 'module' => 'users', 'description' => 'Edit users'],
            ['name' => 'Delete Users', 'slug' => 'users.delete', 'module' => 'users', 'description' => 'Delete users'],
            ['name' => 'Manage Roles', 'slug' => 'roles.manage', 'module' => 'roles', 'description' => 'Create, edit, delete roles and assign permissions'],
            ['name' => 'View Invoices', 'slug' => 'invoices.view', 'module' => 'invoices', 'description' => 'View invoices'],
            ['name' => 'Create Invoices', 'slug' => 'invoices.create', 'module' => 'invoices', 'description' => 'Create invoices'],
            ['name' => 'Edit Invoices', 'slug' => 'invoices.edit', 'module' => 'invoices', 'description' => 'Edit invoices'],
            ['name' => 'Delete Invoices', 'slug' => 'invoices.delete', 'module' => 'invoices', 'description' => 'Delete invoices'],
            ['name' => 'View Customers', 'slug' => 'customers.view', 'module' => 'customers', 'description' => 'View customers'],
            ['name' => 'Create Customers', 'slug' => 'customers.create', 'module' => 'customers', 'description' => 'Create customers'],
            ['name' => 'Edit Customers', 'slug' => 'customers.edit', 'module' => 'customers', 'description' => 'Edit customers'],
            ['name' => 'Delete Customers', 'slug' => 'customers.delete', 'module' => 'customers', 'description' => 'Delete customers'],
            ['name' => 'View Items', 'slug' => 'items.view', 'module' => 'items', 'description' => 'View items/products'],
            ['name' => 'Create Items', 'slug' => 'items.create', 'module' => 'items', 'description' => 'Create items'],
            ['name' => 'Edit Items', 'slug' => 'items.edit', 'module' => 'items', 'description' => 'Edit items'],
            ['name' => 'Delete Items', 'slug' => 'items.delete', 'module' => 'items', 'description' => 'Delete items'],
            ['name' => 'View Reports', 'slug' => 'reports.view', 'module' => 'reports', 'description' => 'View reports and dashboard'],
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['slug' => $p['slug']], $p);
        }

        $allPermissionIds = Permission::pluck('id')->toArray();

        $roles = [
            [
                'name' => 'Super Admin',
                'slug' => 'super_admin',
                'description' => 'Full system access',
                'is_system' => true,
                'permissions' => $allPermissionIds,
            ],
            [
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'Organization administrator',
                'is_system' => true,
                'permissions' => $allPermissionIds,
            ],
            [
                'name' => 'Sub Admin',
                'slug' => 'subadmin',
                'description' => 'Organization sub-administrator',
                'is_system' => true,
                'permissions' => $allPermissionIds,
            ],
            [
                'name' => 'Manager',
                'slug' => 'manager',
                'description' => 'Can manage sales and operations',
                'is_system' => true,
                'permissions' => Permission::whereIn('module', ['invoices', 'customers', 'items', 'reports'])->pluck('id')->toArray(),
            ],
            [
                'name' => 'User',
                'slug' => 'user',
                'description' => 'Standard user',
                'is_system' => true,
                'permissions' => Permission::whereIn('slug', [
                    'invoices.view', 'invoices.create', 'customers.view', 'customers.create',
                    'items.view', 'reports.view',
                ])->pluck('id')->toArray(),
            ],
            [
                'name' => 'Viewer',
                'slug' => 'viewer',
                'description' => 'Read-only access',
                'is_system' => true,
                'permissions' => Permission::where('slug', 'like', '%.view')->pluck('id')->toArray(),
            ],
        ];

        foreach ($roles as $r) {
            $permissionIds = $r['permissions'];
            unset($r['permissions']);
            $role = Role::firstOrCreate(['slug' => $r['slug']], $r);
            $role->permissions()->sync($permissionIds);
        }

        // Sync role_id for existing users based on role string
        foreach (['admin' => 'admin', 'subadmin' => 'subadmin', 'manager' => 'manager', 'user' => 'user', 'viewer' => 'viewer'] as $roleStr => $slug) {
            $role = Role::where('slug', $slug)->first();
            if ($role) {
                User::where('role', $roleStr)->whereNull('role_id')->update(['role_id' => $role->id]);
            }
        }
    }
}
