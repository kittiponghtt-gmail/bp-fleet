<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateFleetUser extends Command
{
    protected $signature = 'fleet:user {username} {password} {display_name}';

    protected $description = 'Create a login for the fleet app';

    public function handle(): int
    {
        $username = (string) $this->argument('username');
        if (User::query()->where('username', $username)->exists()) {
            $this->error('That username already exists.');

            return self::FAILURE;
        }

        User::query()->create([
            'username' => $username,
            'display_name' => (string) $this->argument('display_name'),
            'password_hash' => (string) $this->argument('password'),
        ]);

        $this->info('User created.');

        return self::SUCCESS;
    }
}
