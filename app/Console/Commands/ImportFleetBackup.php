<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportFleetBackup extends Command
{
    protected $signature = 'fleet:import {file : Path to bp_fleet_backup JSON}';

    protected $description = 'Replace fleet rows with a backup exported from the browser app';

    public function handle(): int
    {
        $path = $this->argument('file');
        if (! is_file($path)) {
            $this->error('Backup file not found.');

            return self::FAILURE;
        }

        $payload = json_decode(file_get_contents($path), true);
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

        DB::transaction(function () use ($data) {
            DB::table('issues')->delete();
            DB::table('downtime')->delete();
            DB::table('odometer_logs')->delete();
            DB::table('services')->delete();
            DB::table('rules')->delete();
            DB::table('vehicles')->delete();

            DB::table('vehicles')->insert(array_map(fn (array $row) => [
                'id' => $row['id'],
                'plate' => $row['plate'] ?? '',
                'status' => $row['status'] ?? 'active',
                'make' => $row['make'] ?? '',
                'model' => $row['model'] ?? '',
                'year' => $this->intOrNull($row['year'] ?? null),
                'vin' => $row['vin'] ?? '',
                'type' => $row['type'] ?? '',
                'driver' => $row['driver'] ?? '',
                'branch' => $row['branch'] ?? '',
                'department' => $row['department'] ?? '',
                'insurance_expiry' => $this->dateOrNull($row['insuranceExpiry'] ?? null),
                'tax_expiry' => $this->dateOrNull($row['taxExpiry'] ?? null),
                'registration_expiry' => $this->dateOrNull($row['registrationExpiry'] ?? null),
                'inspection_expiry' => $this->dateOrNull($row['inspectionExpiry'] ?? null),
                'current_odometer' => $this->intOrNull($row['currentOdometer'] ?? null),
                'odometer_date' => $this->dateOrNull($row['odometerDate'] ?? null),
                'engine_service_date' => $this->dateOrNull($row['engineServiceDate'] ?? null),
                'engine_service_odometer' => $this->intOrNull($row['engineServiceOdometer'] ?? null),
                'major_service_date' => $this->dateOrNull($row['majorServiceDate'] ?? null),
                'major_service_odometer' => $this->intOrNull($row['majorServiceOdometer'] ?? null),
                'notes' => $row['notes'] ?? null,
                'source_row' => $this->intOrNull($row['sourceRow'] ?? null),
            ], $data['vehicles']));

            DB::table('rules')->insert(array_map(fn (array $row) => [
                'id' => $row['id'],
                'category' => $row['category'],
                'km_interval' => $this->intOrNull($row['kmInterval'] ?? null),
                'days_interval' => $this->intOrNull($row['daysInterval'] ?? null),
                'alert_km' => $this->intOrNull($row['alertKm'] ?? 0) ?? 0,
                'alert_days' => $this->intOrNull($row['alertDays'] ?? 0) ?? 0,
                'active' => ($row['active'] ?? true) ? 1 : 0,
            ], $data['rules']));

            if ($data['services'] !== []) {
                DB::table('services')->insert(array_map(fn (array $row) => [
                    'id' => $row['id'],
                    'vehicle_id' => $row['vehicleId'],
                    'category' => $row['category'],
                    'service_date' => $this->dateOrNull($row['date'] ?? null),
                    'odometer' => $this->intOrNull($row['odometer'] ?? null),
                    'cost' => $this->decimalOrNull($row['cost'] ?? null),
                    'vendor' => $row['vendor'] ?? '',
                    'description' => $row['description'] ?? null,
                    'receipt' => $row['receipt'] ?? '',
                    'downtime_days' => $this->intOrNull($row['downtimeDays'] ?? 0) ?? 0,
                    'verified' => ($row['verified'] ?? true) ? 1 : 0,
                    'source' => $row['source'] ?? 'Manual',
                ], $data['services']));
            }

            $logs = $data['odometerLogs'] ?? [];
            if ($logs !== []) {
                DB::table('odometer_logs')->insert(array_map(fn (array $row) => [
                    'id' => $row['id'],
                    'vehicle_id' => $row['vehicleId'],
                    'reading_date' => $this->dateOrNull($row['date'] ?? null),
                    'odometer' => $this->intOrNull($row['odometer'] ?? 0) ?? 0,
                    'notes' => $row['notes'] ?? null,
                    'source' => $row['source'] ?? 'Manual',
                ], $logs));
            }

            $downtime = $data['downtime'] ?? [];
            if ($downtime !== []) {
                DB::table('downtime')->insert(array_map(fn (array $row) => [
                    'id' => $row['id'],
                    'vehicle_id' => $row['vehicleId'],
                    'start_date' => $this->dateOrNull($row['start'] ?? null),
                    'end_date' => $this->dateOrNull($row['end'] ?? null),
                    'reason' => $row['reason'] ?? '',
                    'description' => $row['description'] ?? null,
                ], $downtime));
            }

            $issues = $data['issues'] ?? [];
            if ($issues !== []) {
                DB::table('issues')->insert(array_map(fn (array $row) => [
                    'vehicle_id' => $row['vehicleId'] ?? null,
                    'field_name' => $row['field'] ?? '',
                    'issue' => $row['issue'] ?? '',
                    'value_text' => (string) ($row['value'] ?? ''),
                    'resolved' => ($row['resolved'] ?? false) ? 1 : 0,
                ], $issues));
            }

            DB::table('app_config')->updateOrInsert(
                ['id' => 1],
                ['tracking_start' => $this->dateOrNull($data['config']['trackingStart'] ?? null)]
            );
        });

        $this->info(sprintf(
            'Imported %d vehicles, %d services, %d rules, %d odometer readings.',
            count($data['vehicles']),
            count($data['services']),
            count($data['rules']),
            count($data['odometerLogs'] ?? [])
        ));

        return self::SUCCESS;
    }

    private function dateOrNull(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return substr((string) $value, 0, 10);
    }

    private function intOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function decimalOrNull(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return number_format((float) $value, 2, '.', '');
    }
}
