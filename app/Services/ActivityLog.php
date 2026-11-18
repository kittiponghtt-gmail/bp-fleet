<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ActivityLog
{
    public function write(string $action, string $subjectType, ?string $subjectId, string $detail, ?string $category, array $changes): void
    {
        if (! DB::getSchemaBuilder()->hasTable('activity_logs')) {
            return;
        }

        $user = Auth::user();
        DB::table('activity_logs')->insert([
            'user_id' => $user?->id,
            'actor_name' => $user?->display_name ?: ($user?->username ?: 'System'),
            'actor_username' => $user?->username ?: 'system',
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'detail' => $detail !== '' ? mb_substr($detail, 0, 180) : null,
            'category' => $category !== null && $category !== '' ? mb_substr($category, 0, 120) : null,
            'changes' => json_encode(array_values($changes), JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
        ]);
    }

    public function recent(int $limit = 300): array
    {
        if (! DB::getSchemaBuilder()->hasTable('activity_logs')) {
            return [];
        }

        return DB::table('activity_logs')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                $changes = json_decode($row->changes ?: '[]', true);

                return [
                    'id' => (int) $row->id,
                    'at' => Carbon::parse($row->created_at)->toIso8601String(),
                    'name' => $row->actor_name,
                    'username' => $row->actor_username,
                    'action' => $row->action,
                    'subjectType' => $row->subject_type,
                    'subjectId' => $row->subject_id,
                    'detail' => $row->detail,
                    'category' => $row->category,
                    'changes' => is_array($changes) ? $changes : [],
                ];
            })
            ->all();
    }

    public function diff(array $before, array $after, array $labels): array
    {
        $changes = [];
        foreach ($labels as $key => $label) {
            $from = $this->norm($before[$key] ?? null);
            $to = $this->norm($after[$key] ?? null);
            if ($from !== $to) {
                $changes[] = ['field' => $label, 'from' => $from, 'to' => $to];
            }
        }

        return $changes;
    }

    private function norm(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        $text = trim(is_string($value) ? $value : (string) $value);
        if ($text === '') {
            return null;
        }
        if (mb_strlen($text) > 300) {
            return mb_substr($text, 0, 300).'…';
        }

        return $text;
    }
}
