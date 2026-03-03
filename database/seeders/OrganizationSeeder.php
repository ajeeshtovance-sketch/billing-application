<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::firstOrCreate(
            ['name' => 'Demo Store'],
            ['base_currency' => 'INR']
        );

        $adminRole = Role::where('slug', 'admin')->first();

        User::updateOrCreate(
            ['email' => 'admin@billing.test'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'organization_id' => $org->id,
                'role_id' => $adminRole?->id,
                'role' => 'admin',
                'status' => 'active',
            ]
        );
    }
}
