<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ResetAdminPasswordCommand extends Command
{
    protected $signature = 'admin:password
                            {email? : Email or phone number of the administrator}
                            {password? : New password to set}';

    protected $description = 'Set or reset the password for an administrator account';

    public function handle(): int
    {
        $login = $this->argument('email') ?: $this->ask('Administrator Email or Phone');
        $login = trim((string) $login);

        $user = User::where('email', $login)
            ->orWhere('phone', $login)
            ->first();

        if (!$user) {
            $this->error("No user found with email or phone: '{$login}'.");
            return self::FAILURE;
        }

        $password = $this->argument('password') ?: $this->secret('New Password');

        if (strlen((string) $password) < 8) {
            $this->error('Password must be at least 8 characters.');
            return self::FAILURE;
        }

        $user->password = Hash::make((string) $password);
        $user->is_active = true;
        $user->save();

        $this->info("Successfully updated password for: {$user->name} ({$user->email} / {$user->phone})");
        $this->info("You can now sign in at http://localhost:8000/admin/login or http://localhost:8000/login");

        return self::SUCCESS;
    }
}
