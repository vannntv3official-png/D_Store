<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdminCommand extends Command
{
    protected $signature = 'admin:create
                            {--name= : Full name of the administrator}
                            {--email= : Official staff email address}
                            {--phone= : Primary phone number}
                            {--password= : Administrator password (prompts securely if omitted)}';

    protected $description = 'Provision a new Super Administrator without hardcoded credentials';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Administrator Full Name');
        $email = $this->option('email') ?: $this->ask('Administrator Email Address');
        $phone = $this->option('phone') ?: $this->ask('Administrator Phone Number');
        $password = $this->option('password') ?: $this->secret('Administrator Password');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:32', 'unique:users,phone'],
            'password' => ['required', Password::defaults()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return self::FAILURE;
        }

        $superAdminRole = Role::firstOrCreate(
            ['name' => 'super_admin'],
            ['display_name' => 'Super Administrator']
        );

        $user = User::create([
            'name' => $name,
            'email' => strtolower(trim((string) $email)),
            'phone' => trim((string) $phone),
            'password' => Hash::make((string) $password),
            'is_active' => true,
        ]);

        $user->roles()->sync([$superAdminRole->id]);

        $this->info("Successfully provisioned Super Administrator: {$user->name} ({$user->email})");

        return self::SUCCESS;
    }
}
