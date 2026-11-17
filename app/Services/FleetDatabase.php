<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FleetDatabase
{
    public function snapshot(): array
    {
        $vehicles = DB::table('vehicles')->orderBy('id')->get()->map(fn ($row) => [
            'id' => $row->id,
            'plate' => $row->plate,
            'status' => $row->status,
            'make' => $row->make,
            'model' => $row->model,
            'year' => $row->year === null ? null : (int) $row->year,
            'vin' => $row->vin,
            'type' => $row->type,
            'driver' => $row->driver,
            'branch' => $row->branch,
            'department' => $row->department,
            'insuranceExpiry' => $row->insurance_expiry,
            'taxExpiry' => $row->tax_expiry,
            'registrationExpiry' => $row->registration_expiry,
            'inspectionExpiry' => $row->inspection_expiry,
            'currentOdometer' => $row->current_odometer === null ? null : (int) $row->current_odometer,
            'odometerDate' => $row->odometer_date,
            'engineServiceDate' => $row->engine_service_date,
            'engineServiceOdometer' => $row->engine_service_odometer === null ? null : (int) $row->engine_service_odometer,
            'majorServiceDate' => $row->major_service_date,
            'majorServiceOdometer' => $row->major_service_odometer === null ? null : (int) $row->major_service_odometer,
            'notes' => $row->notes,
            'sourceRow' => $row->source_row === null ? null : (int) $row->source_row,
        ])->all();

        $services = DB::table('services')->orderBy('service_date')->orderBy('id')->get()->map(fn ($row) => [
            'id' => $row->id,
            'vehicleId' => $row->vehicle_id,
            'category' => $row->category,
            'date' => $row->service_date,
            'odometer' => $row->odometer === null ? null : (int) $row->odometer,
            'cost' => $row->cost === null ? null : (float) $row->cost,
            'vendor' => $row->vendor,
            'description' => $row->description,
            'receipt' => $row->receipt,
            'downtimeDays' => (int) $row->downtime_days,
            'verified' => (int) $row->verified === 1,
            'source' => $row->source,
        ])->all();

        $rules = DB::table('rules')->orderBy('category')->get()->map(fn ($row) => [
            'id' => $row->id,
            'category' => $row->category,
            'kmInterval' => $row->km_interval === null ? null : (int) $row->km_interval,
            'daysInterval' => $row->days_interval === null ? null : (int) $row->days_interval,
            'alertKm' => (int) $row->alert_km,
            'alertDays' => (int) $row->alert_days,
            'active' => (int) $row->active === 1,
        ])->all();

        $logs = DB::table('odometer_logs')->orderBy('reading_date')->orderBy('id')->get()->map(fn ($row) => [
            'id' => $row->id,
            'vehicleId' => $row->vehicle_id,
            'date' => $row->reading_date,
            'odometer' => (int) $row->odometer,
            'notes' => $row->notes,
            'source' => $row->source,
        ])->all();

        $downtime = DB::table('downtime')->orderBy('start_date')->orderBy('id')->get()->map(fn ($row) => [
            'id' => $row->id,
            'vehicleId' => $row->vehicle_id,
            'start' => $row->start_date,
            'end' => $row->end_date,
            'reason' => $row->reason,
            'description' => $row->description,
        ])->all();

        $issues = DB::table('issues')->orderBy('id')->get()->map(fn ($row) => [
            'id' => (int) $row->id,
            'vehicleId' => $row->vehicle_id,
            'field' => $row->field_name,
            'issue' => $row->issue,
            'value' => $row->value_text,
            'resolved' => (int) $row->resolved === 1,
        ])->all();

        $config = DB::table('app_config')->where('id', 1)->first();
        $metadata = [];
        if ($config && isset($config->metadata) && $config->metadata) {
            $metadata = json_decode($config->metadata, true) ?: [];
        }

        return [
            'vehicles' => $vehicles,
            'services' => $services,
            'rules' => $rules,
            'downtime' => $downtime,
            'issues' => $issues,
            'odometerLogs' => $logs,
            'metadata' => $metadata,
            'config' => ['trackingStart' => $config->tracking_start ?? null],
        ];
    }

    public function replace(array $data): void
    {
        foreach (['vehicles', 'services', 'rules'] as $key) {
            if (! isset($data[$key]) || ! is_array($data[$key])) {
                abort(422, 'The backup is missing '.$key.'.');
            }
        }

        DB::transaction(function () use ($data) {
            DB::table('issues')->delete();
            DB::table('downtime')->delete();
            DB::table('odometer_logs')->delete();
            DB::table('services')->delete();
            DB::table('rules')->delete();
            DB::table('vehicles')->delete();

            $this->insertChunks('vehicles', array_map(fn (array $row) => [
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

            $this->insertChunks('rules', array_map(fn (array $row) => [
                'id' => $row['id'],
                'category' => $row['category'],
                'km_interval' => $this->intOrNull($row['kmInterval'] ?? null),
                'days_interval' => $this->intOrNull($row['daysInterval'] ?? null),
                'alert_km' => $this->intOrNull($row['alertKm'] ?? 0) ?? 0,
                'alert_days' => $this->intOrNull($row['alertDays'] ?? 0) ?? 0,
                'active' => ($row['active'] ?? true) ? 1 : 0,
            ], $data['rules']));

            $this->insertChunks('services', array_map(fn (array $row) => [
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

            $this->insertChunks('odometer_logs', array_map(fn (array $row) => [
                'id' => $row['id'],
                'vehicle_id' => $row['vehicleId'],
                'reading_date' => $this->dateOrNull($row['date'] ?? null),
                'odometer' => $this->intOrNull($row['odometer'] ?? 0) ?? 0,
                'notes' => $row['notes'] ?? null,
                'source' => $row['source'] ?? 'Manual',
            ], $data['odometerLogs'] ?? []));

            $this->insertChunks('downtime', array_map(fn (array $row) => [
                'id' => $row['id'],
                'vehicle_id' => $row['vehicleId'],
                'start_date' => $this->dateOrNull($row['start'] ?? null),
                'end_date' => $this->dateOrNull($row['end'] ?? null),
                'reason' => $row['reason'] ?? '',
                'description' => $row['description'] ?? null,
            ], $data['downtime'] ?? []));

            $this->insertChunks('issues', array_map(fn (array $row) => [
                'vehicle_id' => $row['vehicleId'] ?? null,
                'field_name' => $row['field'] ?? '',
                'issue' => $row['issue'] ?? '',
                'value_text' => (string) ($row['value'] ?? ''),
                'resolved' => ($row['resolved'] ?? false) ? 1 : 0,
            ], $data['issues'] ?? []));

            $values = ['tracking_start' => $this->dateOrNull($data['config']['trackingStart'] ?? null)];
            if ($this->hasMetadataColumn()) {
                $values['metadata'] = json_encode($data['metadata'] ?? []);
            }
            DB::table('app_config')->updateOrInsert(['id' => 1], $values);
        });
    }

    public function saveVehicle(array $input, ?string $existingId): void
    {
        $id = strtoupper(trim((string) ($input['id'] ?? '')));
        $plate = trim((string) ($input['plate'] ?? ''));
        if ($id === '' || $plate === '') {
            abort(422, 'Vehicle ID and plate are required.');
        }

        $existing = $existingId ? DB::table('vehicles')->where('id', $existingId)->first() : null;
        if (! $existing && DB::table('vehicles')->where('id', $id)->exists()) {
            abort(422, 'That vehicle ID already exists.');
        }
        if ($existing && $existing->id !== $id && DB::table('vehicles')->where('id', $id)->exists()) {
            abort(422, 'That vehicle ID already exists.');
        }

        $odometer = $this->intOrNull($input['currentOdometer'] ?? null);
        $odometerDate = $this->dateOrNull($input['odometerDate'] ?? null);
        $row = [
            'id' => $id,
            'plate' => $plate,
            'status' => $input['status'] ?? 'active',
            'make' => $input['make'] ?? '',
            'model' => $input['model'] ?? '',
            'year' => $this->intOrNull($input['year'] ?? null),
            'vin' => $input['vin'] ?? '',
            'type' => $input['type'] ?? '',
            'driver' => $input['driver'] ?? '',
            'branch' => $input['branch'] ?? '',
            'department' => $input['department'] ?? '',
            'insurance_expiry' => $this->dateOrNull($input['insuranceExpiry'] ?? null),
            'tax_expiry' => $this->dateOrNull($input['taxExpiry'] ?? null),
            'registration_expiry' => $this->dateOrNull($input['registrationExpiry'] ?? null),
            'inspection_expiry' => $this->dateOrNull($input['inspectionExpiry'] ?? null),
            'current_odometer' => $odometer,
            'odometer_date' => $odometerDate,
            'notes' => ($input['notes'] ?? '') !== '' ? $input['notes'] : null,
        ];

        DB::transaction(function () use ($existing, $row, $odometer, $odometerDate) {
            if ($existing) {
                DB::table('vehicles')->where('id', $existing->id)->update($row);
            } else {
                DB::table('vehicles')->insert($row + [
                    'engine_service_date' => null,
                    'engine_service_odometer' => null,
                    'major_service_date' => null,
                    'major_service_odometer' => null,
                    'source_row' => null,
                ]);
            }

            $previousOdo = $existing && $existing->current_odometer !== null ? (int) $existing->current_odometer : null;
            $previousDate = $existing ? $this->dateOrNull($existing->odometer_date) : null;
            if ($odometer !== null && (! $existing || $previousOdo !== $odometer || $previousDate !== $odometerDate) && $odometerDate) {
                DB::table('odometer_logs')->insert([
                    'id' => $this->newId('odo'),
                    'vehicle_id' => $row['id'],
                    'reading_date' => $odometerDate,
                    'odometer' => $odometer,
                    'notes' => null,
                    'source' => 'Vehicle profile',
                ]);
            }
        });
    }

    public function saveService(array $input, ?string $existingId): void
    {
        $vehicleId = (string) ($input['vehicleId'] ?? '');
        $date = $this->dateOrNull($input['date'] ?? null);
        $category = trim((string) ($input['category'] ?? ''));
        if ($vehicleId === '' || $date === null || $category === '') {
            abort(422, 'Choose a vehicle, category, and service date.');
        }
        if (! DB::table('vehicles')->where('id', $vehicleId)->exists()) {
            abort(422, 'That vehicle does not exist.');
        }

        if ($existingId && ! DB::table('services')->where('id', $existingId)->exists()) {
            abort(404, 'Maintenance record not found.');
        }

        $id = $existingId ?: (string) ($input['id'] ?? $this->newId('evt'));
        $odometer = $this->intOrNull($input['odometer'] ?? null);
        $row = [
            'id' => $id,
            'vehicle_id' => $vehicleId,
            'category' => $category,
            'service_date' => $date,
            'odometer' => $odometer,
            'cost' => $this->decimalOrNull($input['cost'] ?? null),
            'vendor' => $input['vendor'] ?? '',
            'description' => ($input['description'] ?? '') !== '' ? $input['description'] : null,
            'receipt' => $input['receipt'] ?? '',
            'downtime_days' => $this->intOrNull($input['downtimeDays'] ?? 0) ?? 0,
            'verified' => ($input['verified'] ?? true) ? 1 : 0,
            'source' => $input['source'] ?? 'Manual',
        ];

        DB::transaction(function () use ($existingId, $row, $vehicleId, $odometer, $date, $category) {
            if ($existingId) {
                DB::table('services')->where('id', $existingId)->update($row);
            } else {
                DB::table('services')->insert($row);
            }

            $vehicle = DB::table('vehicles')->where('id', $vehicleId)->first();
            $current = $vehicle->current_odometer === null ? null : (int) $vehicle->current_odometer;
            $currentDate = $this->dateOrNull($vehicle->odometer_date);
            if ($odometer !== null && ($current === null || $odometer > $current) && ($currentDate === null || $date >= $currentDate)) {
                DB::table('vehicles')->where('id', $vehicleId)->update([
                    'current_odometer' => $odometer,
                    'odometer_date' => $date,
                ]);
                DB::table('odometer_logs')->insert([
                    'id' => $this->newId('odo'),
                    'vehicle_id' => $vehicleId,
                    'reading_date' => $date,
                    'odometer' => $odometer,
                    'notes' => null,
                    'source' => 'Maintenance record',
                ]);
            }

            $this->syncLatest($vehicleId, $category);
        });
    }

    public function deleteService(string $id): void
    {
        $service = DB::table('services')->where('id', $id)->first();
        if (! $service) {
            abort(404, 'Maintenance record not found.');
        }

        DB::transaction(function () use ($service) {
            DB::table('services')->where('id', $service->id)->delete();
            $this->syncLatest($service->vehicle_id, $service->category);
        });
    }

    public function saveRule(array $input, ?string $existingId): void
    {
        $category = trim((string) ($input['category'] ?? ''));
        if ($category === '') {
            abort(422, 'Category is required.');
        }
        $duplicate = DB::table('rules')
            ->whereRaw('LOWER(category) = ?', [mb_strtolower($category)])
            ->when($existingId, fn ($query) => $query->where('id', '!=', $existingId))
            ->exists();
        if ($duplicate) {
            abort(422, 'That category already exists.');
        }
        if ($existingId && ! DB::table('rules')->where('id', $existingId)->exists()) {
            abort(404, 'Rule not found.');
        }

        $row = [
            'category' => $category,
            'km_interval' => $this->intOrNull($input['kmInterval'] ?? null),
            'days_interval' => $this->intOrNull($input['daysInterval'] ?? null),
            'alert_km' => $this->intOrNull($input['alertKm'] ?? 0) ?? 0,
            'alert_days' => $this->intOrNull($input['alertDays'] ?? 0) ?? 0,
            'active' => array_key_exists('active', $input) ? (($input['active'] ?? true) ? 1 : 0) : 1,
        ];

        if ($existingId) {
            DB::table('rules')->where('id', $existingId)->update($row);
        } else {
            DB::table('rules')->insert($row + ['id' => $input['id'] ?? $this->newId('rule')]);
        }
    }

    public function setRuleActive(string $id, bool $active): void
    {
        if (! DB::table('rules')->where('id', $id)->exists()) {
            abort(404, 'Rule not found.');
        }
        DB::table('rules')->where('id', $id)->update(['active' => $active ? 1 : 0]);
    }

    public function saveOdometer(array $input): void
    {
        $vehicleId = (string) ($input['vehicleId'] ?? '');
        $date = $this->dateOrNull($input['date'] ?? null);
        $odometer = $this->intOrNull($input['odometer'] ?? null);
        if ($vehicleId === '' || $date === null || $odometer === null) {
            abort(422, 'Choose a vehicle, date, and odometer.');
        }
        $vehicle = DB::table('vehicles')->where('id', $vehicleId)->first();
        if (! $vehicle) {
            abort(422, 'That vehicle does not exist.');
        }

        DB::transaction(function () use ($input, $vehicle, $vehicleId, $date, $odometer) {
            DB::table('odometer_logs')->insert([
                'id' => $input['id'] ?? $this->newId('odo'),
                'vehicle_id' => $vehicleId,
                'reading_date' => $date,
                'odometer' => $odometer,
                'notes' => ($input['notes'] ?? '') !== '' ? $input['notes'] : null,
                'source' => $input['source'] ?? 'Manual',
            ]);

            $currentDate = $this->dateOrNull($vehicle->odometer_date);
            if ($currentDate === null || $date >= $currentDate) {
                DB::table('vehicles')->where('id', $vehicleId)->update([
                    'current_odometer' => $odometer,
                    'odometer_date' => $date,
                ]);
            }
        });
    }

    public function deleteOdometer(string $id): void
    {
        $log = DB::table('odometer_logs')->where('id', $id)->first();
        if (! $log) {
            abort(404, 'Mileage reading not found.');
        }

        DB::transaction(function () use ($log) {
            DB::table('odometer_logs')->where('id', $log->id)->delete();
            $remaining = DB::table('odometer_logs')->where('vehicle_id', $log->vehicle_id)->get();
            if ($remaining->isEmpty()) {
                return;
            }
            $latest = $remaining->sortBy([
                ['reading_date', 'desc'],
                ['odometer', 'desc'],
            ])->first();
            DB::table('vehicles')->where('id', $log->vehicle_id)->update([
                'current_odometer' => (int) $latest->odometer,
                'odometer_date' => $latest->reading_date,
            ]);
        });
    }

    public function saveDowntime(array $input): void
    {
        $vehicleId = (string) ($input['vehicleId'] ?? '');
        $start = $this->dateOrNull($input['start'] ?? null);
        $end = $this->dateOrNull($input['end'] ?? null);
        $reason = trim((string) ($input['reason'] ?? ''));
        if ($vehicleId === '' || $start === null || $end === null || $reason === '') {
            abort(422, 'Vehicle, start, end, and reason are required.');
        }
        if ($end < $start) {
            abort(422, 'The end date is before the start date.');
        }
        if (! DB::table('vehicles')->where('id', $vehicleId)->exists()) {
            abort(422, 'That vehicle does not exist.');
        }

        $overlap = DB::table('downtime')
            ->where('vehicle_id', $vehicleId)
            ->where('start_date', '<=', $end)
            ->where('end_date', '>=', $start)
            ->exists();
        if ($overlap) {
            abort(422, 'That period overlaps an existing downtime record.');
        }

        DB::table('downtime')->insert([
            'id' => $input['id'] ?? $this->newId('down'),
            'vehicle_id' => $vehicleId,
            'start_date' => $start,
            'end_date' => $end,
            'reason' => $reason,
            'description' => ($input['description'] ?? '') !== '' ? $input['description'] : null,
        ]);
    }

    public function deleteDowntime(string $id): void
    {
        if (! DB::table('downtime')->where('id', $id)->delete()) {
            abort(404, 'Downtime record not found.');
        }
    }

    public function resolveIssue(int $id): void
    {
        if (! DB::table('issues')->where('id', $id)->update(['resolved' => 1])) {
            abort(404, 'Issue not found.');
        }
    }

    public function setTrackingStart(?string $date): void
    {
        DB::table('app_config')->updateOrInsert(
            ['id' => 1],
            ['tracking_start' => $this->dateOrNull($date)]
        );
    }

    private function syncLatest(string $vehicleId, string $category): void
    {
        if (! in_array($category, ['Engine oil', 'Major fluid service'], true)) {
            return;
        }

        $latest = DB::table('services')
            ->where('vehicle_id', $vehicleId)
            ->where('category', $category)
            ->where('verified', 1)
            ->orderByDesc('odometer')
            ->orderByDesc('service_date')
            ->first();

        if ($category === 'Engine oil') {
            DB::table('vehicles')->where('id', $vehicleId)->update([
                'engine_service_date' => $latest->service_date ?? null,
                'engine_service_odometer' => $latest->odometer ?? null,
            ]);
        } else {
            DB::table('vehicles')->where('id', $vehicleId)->update([
                'major_service_date' => $latest->service_date ?? null,
                'major_service_odometer' => $latest->odometer ?? null,
            ]);
        }
    }

    private function insertChunks(string $table, array $rows): void
    {
        foreach (array_chunk($rows, 200) as $chunk) {
            if ($chunk !== []) {
                DB::table($table)->insert($chunk);
            }
        }
    }

    private function hasMetadataColumn(): bool
    {
        return DB::getSchemaBuilder()->hasColumn('app_config', 'metadata');
    }

    private function newId(string $prefix): string
    {
        return $prefix.'-'.base_convert((string) (int) (microtime(true) * 1000), 10, 36).Str::lower(Str::random(4));
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
