<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;

class ActivityLogger
{
    public static function record(
        User $actor,
        string $action,
        string $description,
        ?string $routeName = null,
        string $method = 'SYSTEM',
        string $path = '',
        ?string $subjectType = null,
        int|string|null $subjectId = null,
    ): ActivityLog {
        return ActivityLog::create([
            'user_id' => $actor->id,
            'actor_name' => $actor->name,
            'actor_username' => $actor->username,
            'actor_role' => $actor->role,
            'action' => $action,
            'route_name' => $routeName,
            'method' => strtoupper($method),
            'path' => $path,
            'subject_type' => $subjectType,
            'subject_id' => is_numeric($subjectId) ? (int) $subjectId : null,
            'description' => $description,
        ]);
    }
}
