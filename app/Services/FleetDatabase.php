<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FleetDatabase
{
    public function __construct(private ActivityLog $activity) {}

    private const VEHICLE_FIELDS = [
        'id' => 'Vehicle ID',
        'plate' => 'License plate',
        'make' => 'Make / brand',
        'model' => 'Model',
        'year' => 'Year',
        'vin' => 'Vehicle identification number (VIN)',
        'type' => 'Vehicle type',
        'driver' => 'Assigned driver',
        'branch' => 'Branch / location',
        'department' => 'Department / route',
        'status' => 'Vehicle status',
        'currentOdometer' => 'Current odometer (km)',
        'odometerDate' => 'Odometer reading date',
        'insuranceExpiry' => 'Insurance expiry',
        'taxExpiry' => 'Road tax expiry',
        'inspectionExpiry' => 'Inspection expiry',
        'registrationExpiry' => 'Registration expiry',
        'notes' => 'Notes',
    ];

    private const SERVICE_FIELDS = [
        'vehicleId' => 'Vehicle',
        'category' => 'Maintenance type',
        'date' => 'Service date',
        'odometer' => 'Odometer when serviced (km)',
        'cost' => 'Actual cost (THB)',
        'vendor' => 'Service provider / garage',
        'downtimeDays' => 'Downtime (days)',
        'receipt' => 'Receipt / invoice reference',
        'description' => 'Work done / parts replaced',
        'verified' => 'Verified',
    ];

    private const RULE_FIELDS = [
        'category' => 'Service type name',
        'kmInterval' => 'Mileage interval (km)',
        'daysInterval' => 'Calendar interval (days)',
        'alertKm' => 'Early warning (km)',
        'alertDays' => 'Early warning (days)',
        'active' => 'Active',
    ];

    private const ODOMETER_FIELDS = [
        'vehicleId' => 'Vehicle',
        'date' => 'Reading date',
        'odometer' => 'Odometer (km)',
        'notes' => 'Notes',
    ];

    private const DOWNTIME_FIELDS = [
        'vehicleId' => 'Vehicle',
        'reason' => 'Reason',
        'start' => 'Unavailable from',
        'end' => 'Available again',
        'description' => 'Description',
    ];

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
            $beforeCounts = [
                'Vehicles' => DB::table('vehicles')->count(),
                'Maintenance records' => DB::table('services')->count(),
                'Reminder rules' => DB::table('rules')->count(),
                'Mileage readings' => DB::table('odometer_logs')->count(),
                'Downtime records' => DB::table('downtime')->count(),
                'Data issues' => DB::table('issues')->count(),
            ];
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

            $afterCounts = [
                'Vehicles' => count($data['vehicles']),
                'Maintenance records' => count($data['services']),
                'Reminder rules' => count($data['rules']),
                'Mileage readings' => count($data['odometerLogs'] ?? []),
                'Downtime records' => count($data['downtime'] ?? []),
                'Data issues' => count($data['issues'] ?? []),
            ];
            $changes = [];
            foreach ($beforeCounts as $label => $from) {
                $changes[] = ['field' => $label, 'from' => (string) $from, 'to' => (string) $afterCounts[$label]];
            }
            $this->record('replaced', 'fleet', null, '', null, $changes);
        });
    }

    public function saveVehicle(array $input, ?string $existingId): void
    {
        $id = strtoupper(trim((string) ($input['id'] ?? '')));
        $plate = trim((string) ($input['plate'] ?? ''));
        if ($id === '') {
            abort(422, 'Vehicle ID is required.');
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

            $this->record(
                $existing ? 'updated' : 'created',
                'vehicle',
                $row['id'],
                (string) $row['plate'],
                null,
                $this->activity->diff(
                    $existing ? $this->vehicleBefore($existing) : [],
                    $this->vehicleAfter($row),
                    self::VEHICLE_FIELDS
                )
            );
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

        $previous = $existingId ? DB::table('services')->where('id', $existingId)->first() : null;
        if ($existingId && ! $previous) {
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

        DB::transaction(function () use ($existingId, $row, $vehicleId, $odometer, $date, $category, $previous) {
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

            $this->record(
                $previous ? 'updated' : 'created',
                'service',
                $row['id'],
                $vehicleId,
                $category,
                $this->activity->diff(
                    $previous ? $this->serviceBefore($previous) : [],
                    $this->serviceAfter($row),
                    self::SERVICE_FIELDS
                )
            );
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
            $this->record(
                'deleted',
                'service',
                (string) $service->id,
                (string) $service->vehicle_id,
                (string) $service->category,
                $this->activity->diff($this->serviceBefore($service), [], self::SERVICE_FIELDS)
            );
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
        $previous = $existingId ? DB::table('rules')->where('id', $existingId)->first() : null;
        if ($existingId && ! $previous) {
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

        $id = $existingId ?: (string) ($input['id'] ?? $this->newId('rule'));
        if ($existingId) {
            DB::table('rules')->where('id', $existingId)->update($row);
        } else {
            DB::table('rules')->insert($row + ['id' => $id]);
        }

        $this->record(
            $previous ? 'updated' : 'created',
            'rule',
            $id,
            '',
            $category,
            $this->activity->diff(
                $previous ? $this->ruleBefore($previous) : [],
                $this->ruleAfter($row),
                self::RULE_FIELDS
            )
        );
    }

    public function setRuleActive(string $id, bool $active): void
    {
        $rule = DB::table('rules')->where('id', $id)->first();
        if (! $rule) {
            abort(404, 'Rule not found.');
        }
        DB::table('rules')->where('id', $id)->update(['active' => $active ? 1 : 0]);
        $after = $this->ruleBefore($rule);
        $after['active'] = $active ? 'Yes' : 'No';
        $this->record(
            'updated',
            'rule',
            $id,
            '',
            (string) $rule->category,
            $this->activity->diff($this->ruleBefore($rule), $after, self::RULE_FIELDS)
        );
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

        $id = (string) ($input['id'] ?? $this->newId('odo'));
        $notes = ($input['notes'] ?? '') !== '' ? $input['notes'] : null;
        DB::transaction(function () use ($vehicle, $vehicleId, $date, $odometer, $id, $notes, $input) {
            DB::table('odometer_logs')->insert([
                'id' => $id,
                'vehicle_id' => $vehicleId,
                'reading_date' => $date,
                'odometer' => $odometer,
                'notes' => $notes,
                'source' => $input['source'] ?? 'Manual',
            ]);

            $currentDate = $this->dateOrNull($vehicle->odometer_date);
            if ($currentDate === null || $date >= $currentDate) {
                DB::table('vehicles')->where('id', $vehicleId)->update([
                    'current_odometer' => $odometer,
                    'odometer_date' => $date,
                ]);
            }

            $this->record('created', 'odometer', $id, $vehicleId, null, $this->activity->diff([], [
                'vehicleId' => $vehicleId,
                'date' => $date,
                'odometer' => $odometer,
                'notes' => $notes,
            ], self::ODOMETER_FIELDS));
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
            if ($remaining->isNotEmpty()) {
                $latest = $remaining->sortBy([
                    ['reading_date', 'desc'],
                    ['odometer', 'desc'],
                ])->first();
                DB::table('vehicles')->where('id', $log->vehicle_id)->update([
                    'current_odometer' => (int) $latest->odometer,
                    'odometer_date' => $latest->reading_date,
                ]);
            }
            $this->record(
                'deleted',
                'odometer',
                (string) $log->id,
                (string) $log->vehicle_id,
                null,
                $this->activity->diff($this->odometerBefore($log), [], self::ODOMETER_FIELDS)
            );
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

        $id = (string) ($input['id'] ?? $this->newId('down'));
        $description = ($input['description'] ?? '') !== '' ? $input['description'] : null;
        DB::table('downtime')->insert([
            'id' => $id,
            'vehicle_id' => $vehicleId,
            'start_date' => $start,
            'end_date' => $end,
            'reason' => $reason,
            'description' => $description,
        ]);
        $this->record('created', 'downtime', $id, $vehicleId, $reason, $this->activity->diff([], [
            'vehicleId' => $vehicleId,
            'reason' => $reason,
            'start' => $start,
            'end' => $end,
            'description' => $description,
        ], self::DOWNTIME_FIELDS));
    }

    public function deleteDowntime(string $id): void
    {
        $row = DB::table('downtime')->where('id', $id)->first();
        if (! $row) {
            abort(404, 'Downtime record not found.');
        }
        DB::table('downtime')->where('id', $id)->delete();
        $this->record(
            'deleted',
            'downtime',
            (string) $row->id,
            (string) $row->vehicle_id,
            (string) $row->reason,
            $this->activity->diff([
                'vehicleId' => $row->vehicle_id,
                'reason' => $row->reason,
                'start' => $this->dateOrNull($row->start_date),
                'end' => $this->dateOrNull($row->end_date),
                'description' => $row->description,
            ], [], self::DOWNTIME_FIELDS)
        );
    }

    public function resolveIssue(int $id): void
    {
        $issue = DB::table('issues')->where('id', $id)->first();
        if (! $issue || (int) $issue->resolved === 1) {
            abort(404, 'Issue not found.');
        }
        DB::table('issues')->where('id', $id)->update(['resolved' => 1]);
        $changes = [
            ['field' => 'Data issue', 'from' => (string) $issue->issue, 'to' => 'Resolved'],
        ];
        if ($issue->vehicle_id) {
            array_unshift($changes, ['field' => 'Vehicle', 'from' => null, 'to' => $issue->vehicle_id]);
        }
        $this->record('resolved', 'issue', (string) $id, (string) $issue->issue, null, $changes);
    }

    public function setTrackingStart(?string $date): void
    {
        $current = DB::table('app_config')->where('id', 1)->value('tracking_start');
        $next = $this->dateOrNull($date);
        DB::table('app_config')->updateOrInsert(
            ['id' => 1],
            ['tracking_start' => $next]
        );
        $this->record('updated', 'config', '1', '', null, $this->activity->diff(
            ['tracking' => $this->dateOrNull($current)],
            ['tracking' => $next],
            ['tracking' => 'Tracking start']
        ));
    }

    private function record(string $action, string $type, ?string $id, string $detail, ?string $category, array $changes): void
    {
        if ($action === 'updated' && $changes === []) {
            return;
        }

        $this->activity->write($action, $type, $id, $detail, $category, $changes);
    }

    private function statusLabel(?string $status): ?string
    {
        return match ($status) {
            'active' => 'Active',
            'in_service' => 'In service',
            'unavailable' => 'Unavailable',
            'sold' => 'Sold / retired',
            null, '' => null,
            default => $status,
        };
    }

    private function vehicleAfter(array $row): array
    {
        return [
            'id' => $row['id'],
            'plate' => $row['plate'] ?? '',
            'make' => $row['make'] ?? '',
            'model' => $row['model'] ?? '',
            'year' => $row['year'] ?? null,
            'vin' => $row['vin'] ?? '',
            'type' => $row['type'] ?? '',
            'driver' => $row['driver'] ?? '',
            'branch' => $row['branch'] ?? '',
            'department' => $row['department'] ?? '',
            'status' => $this->statusLabel($row['status'] ?? null),
            'currentOdometer' => $row['current_odometer'] ?? null,
            'odometerDate' => $this->dateOrNull($row['odometer_date'] ?? null),
            'insuranceExpiry' => $this->dateOrNull($row['insurance_expiry'] ?? null),
            'taxExpiry' => $this->dateOrNull($row['tax_expiry'] ?? null),
            'inspectionExpiry' => $this->dateOrNull($row['inspection_expiry'] ?? null),
            'registrationExpiry' => $this->dateOrNull($row['registration_expiry'] ?? null),
            'notes' => $row['notes'] ?? null,
        ];
    }

    private function vehicleBefore(object $row): array
    {
        return $this->vehicleAfter([
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
            'insurance_expiry' => $row->insurance_expiry,
            'tax_expiry' => $row->tax_expiry,
            'registration_expiry' => $row->registration_expiry,
            'inspection_expiry' => $row->inspection_expiry,
            'current_odometer' => $row->current_odometer === null ? null : (int) $row->current_odometer,
            'odometer_date' => $row->odometer_date,
            'notes' => $row->notes,
        ]);
    }

    private function serviceAfter(array $row): array
    {
        return [
            'vehicleId' => (string) ($row['vehicle_id'] ?? ''),
            'category' => (string) ($row['category'] ?? ''),
            'date' => $this->dateOrNull($row['service_date'] ?? null),
            'odometer' => $this->intOrNull($row['odometer'] ?? null),
            'cost' => $this->decimalOrNull($row['cost'] ?? null),
            'vendor' => (string) ($row['vendor'] ?? ''),
            'description' => $row['description'] ?? null,
            'receipt' => (string) ($row['receipt'] ?? ''),
            'downtimeDays' => $this->intOrNull($row['downtime_days'] ?? 0) ?? 0,
            'verified' => (int) ($row['verified'] ?? 0) === 1 ? 'Yes' : 'No',
        ];
    }

    private function serviceBefore(object $row): array
    {
        return $this->serviceAfter([
            'vehicle_id' => $row->vehicle_id,
            'category' => $row->category,
            'service_date' => $row->service_date,
            'odometer' => $row->odometer,
            'cost' => $row->cost,
            'vendor' => $row->vendor,
            'description' => $row->description,
            'receipt' => $row->receipt,
            'downtime_days' => $row->downtime_days,
            'verified' => $row->verified,
        ]);
    }

    private function ruleAfter(array $row): array
    {
        return [
            'category' => (string) ($row['category'] ?? ''),
            'kmInterval' => $this->intOrNull($row['km_interval'] ?? null),
            'daysInterval' => $this->intOrNull($row['days_interval'] ?? null),
            'alertKm' => $this->intOrNull($row['alert_km'] ?? 0) ?? 0,
            'alertDays' => $this->intOrNull($row['alert_days'] ?? 0) ?? 0,
            'active' => (int) ($row['active'] ?? 0) === 1 ? 'Yes' : 'No',
        ];
    }

    private function ruleBefore(object $row): array
    {
        return $this->ruleAfter([
            'category' => $row->category,
            'km_interval' => $row->km_interval,
            'days_interval' => $row->days_interval,
            'alert_km' => $row->alert_km,
            'alert_days' => $row->alert_days,
            'active' => $row->active,
        ]);
    }

    private function odometerBefore(object $row): array
    {
        return [
            'vehicleId' => (string) $row->vehicle_id,
            'date' => $this->dateOrNull($row->reading_date),
            'odometer' => $this->intOrNull($row->odometer),
            'notes' => $row->notes,
        ];
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
