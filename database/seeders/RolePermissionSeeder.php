<?php

namespace Database\Seeders;

use App\Enums\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        foreach (Permission::cases() as $permission) {
            PermissionModel::firstOrCreate(['name' => $permission->value]);
        }

        // Create roles and assign permissions
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $adminRole->givePermissionTo([
            Permission::MANAGE_TAGS->value,
            Permission::UPGRADE_USERS->value,
            Permission::MANAGE_ALL_BLUEPRINTS->value,
            Permission::MANAGE_ALL_COLLECTIONS->value,
        ]);

        $moderatorRole = Role::firstOrCreate(['name' => 'Moderator']);
        $moderatorRole->givePermissionTo([
            Permission::MANAGE_ALL_BLUEPRINTS->value,
            Permission::MANAGE_ALL_COLLECTIONS->value,
        ]);

        $userRole = Role::firstOrCreate(['name' => 'User']);
        // Regular users don't need explicit permissions
    }
}
