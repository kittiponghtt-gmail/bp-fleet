<?php

namespace App\Console\Commands;

use App\Services\FleetDatabase;
use Illuminate\Console\Command;

class ImportFleetBackup extends Command
{
    protected $signature = 'fleet:import {file : Path to bp_fleet_backup JSON}';

    protected $description = 'Replace fleet rows with a backup exported from the browser app';

    public function handle(FleetDatabase $fleet): int
    {
        $path = $this->argument('file');
        if (! is_file($path)) {
            $this->error('Backup file not found.');

            return self::FAILURE;
        }

        $payload = json_decode((string) file_get_contents($path), true);
        if (! is_array($payload)) {
            $this->error('Backup file is not valid JSON.');

            return self::FAILURE;
        }

        $data = $payload['data'] ?? $payload;
        foreach (['vehicles', 'services', 'rules'] as $key) {
            if (! isset($data[$key]) || ! is_array($data[$key])) {
                $this->error("Backup is missing the {$key} list.");

                return self::FAILURE;
            }
        }

        $fleet->replace($data);

        $this->info(sprintf(
            'Imported %d vehicles, %d services, %d rules, %d odometer readings.',
            count($data['vehicles']),
            count($data['services']),
            count($data['rules']),
            count($data['odometerLogs'] ?? [])
        ));

        return self::SUCCESS;
    }
}
