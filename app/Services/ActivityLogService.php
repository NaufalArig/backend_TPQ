<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class ActivityLogService
{
    public static function log(
        string $action,
        string $module,
        ?Model $entity = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null,
        ?int $userId = null
    ): ?ActivityLog {
        $filteredOldValues = self::filterSensitiveData($oldValues);
        $filteredNewValues = self::filterSensitiveData($newValues);

        $resolvedUserId = $userId ?? auth()->id();

        $resolvedTpqId = auth()->user()?->tpq_id;

        if (!$resolvedTpqId && $resolvedUserId) {
            $resolvedTpqId = User::whereKey($resolvedUserId)->value('tpq_id');
        }

        return ActivityLog::create([
            'tpq_id' => $resolvedTpqId,
            'user_id' => $resolvedUserId,
            'action' => $action,
            'module' => $module,
            'entity_type' => $entity ? class_basename($entity) : null,
            'entity_id' => $entity?->getKey(),
            'description' => $description ?? self::generateDescription($action, $module, $entity),
            'old_values' => $filteredOldValues,
            'new_values' => $filteredNewValues,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    private static function filterSensitiveData(?array $data): ?array
    {
        if (!$data) {
            return null;
        }

        return Arr::except($data, [
            'password',
            'remember_token',
            'email_verified_at',
            'created_at',
            'updated_at',
        ]);
    }

    private static function generateDescription(
        string $action,
        string $module,
        ?Model $entity = null
    ): string {
        $entityName = $entity?->name
            ?? $entity?->title
            ?? $entity?->asset_code
            ?? $entity?->id
            ?? '-';

        return ucfirst($action) . " data on {$module}: {$entityName}";
    }
}
