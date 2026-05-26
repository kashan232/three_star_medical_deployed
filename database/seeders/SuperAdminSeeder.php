<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // create super-admin role
        $role = Role::firstOrCreate(['name' => 'Super Admin']);

        // We will assign all existing permissions just in case.
        $role->syncPermissions(Permission::all());

        // Create Super Admin User
        $user = User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('808080'), // Default password
                'usertype' => 'super_admin',
            ]
        );

        $user->assignRole($role);
    }
}
