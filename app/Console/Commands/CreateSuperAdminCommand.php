<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class CreateSuperAdminCommand extends Command
{
    protected $signature = 'super-admin:create 
                            {--email= : Admin email}
                            {--password= : Admin password}
                            {--name=Super Admin : Admin name}';

    protected $description = 'Create a super admin user (role=super_admin, no organization)';

    public function handle(): int
    {
        $email = $this->option('email') ?: $this->ask('Email');
        $password = $this->option('password') ?: $this->secret('Password');
        $name = $this->option('name');

        $this->validateInputs($email, $password);

        if (User::where('email', $email)->exists()) {
            $this->error("User with email {$email} already exists.");

            return 1;
        }

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'organization_id' => null,
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        $this->info("Super admin created: {$email}");

        return 0;
    }

    private function validateInputs(string $email, string $password): void
    {
        $validator = validator(
            ['email' => $email, 'password' => $password],
            [
                'email' => ['required', 'email'],
                'password' => ['required', Password::defaults()],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            exit(1);
        }
    }
}
