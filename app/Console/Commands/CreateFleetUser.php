<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateFleetUser extends Command
{
    protected $signature = 'fleet:user {username} {password} {display_name} {--admin : Create an administrator}';

    protected $description = 'Create a login for the fleet app';

    public function handle(): int
    {
        $username = (string) $this->argument('username');
        if (User::query()->where('username', $username)->exists()) {
            $this->error('That username already exists.');

            return self::FAILURE;
        }

        $admin = $this->option('admin') || ! User::query()->where('role', 'admin')->exists();

        User::query()->create([
            'username' => $username,
            'display_name' => (string) $this->argument('display_name'),
            'password_hash' => (string) $this->argument('password'),
            'role' => $admin ? 'admin' : 'user',
            'active' => true,
        ]);

        $this->info('User created.');

        return self::SUCCESS;
    }
}
