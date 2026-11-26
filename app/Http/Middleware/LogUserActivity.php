<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\ActivityLogger;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class LogUserActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $actor */
        $actor = $request->user();
        $routeName = $request->route()?->getName();
        [$subjectType, $subjectId] = $this->subject($request);

        $response = $next($request);

        if ($actor && $routeName && $response->getStatusCode() < 400) {
            [$action, $description] = $this->activity($request->method(), $routeName);
            ActivityLogger::record(
                $actor,
                $action,
                $description,
                $routeName,
                $request->method(),
                $request->path(),
                $subjectType,
                $subjectId,
            );
        }

        return $response;
    }

    private function activity(string $method, string $routeName): array
    {
        $known = [
            'logout' => ['logout', 'Signed out of the clinic system'],
            'appointments.verify' => ['verify', 'Verified an appointment confirmation code'],
            'appointments.arrive' => ['arrival', 'Recorded a patient arrival'],
            'appointments.reschedule' => ['reschedule', 'Rescheduled an appointment'],
            'appointments.cancel' => ['cancel', 'Cancelled an appointment'],
            'appointments.no-show' => ['no_show', 'Marked an appointment as No-show'],
            'appointments.resend-confirmation' => ['notification', 'Requested another appointment confirmation'],
            'visits.vital-signs' => ['vital_signs', 'Recorded or updated vital signs'],
            'visits.consultation' => ['consultation', 'Saved or completed a consultation'],
            'users.deactivate' => ['deactivate', 'Deactivated a staff account'],
            'physicians.deactivate' => ['deactivate', 'Deactivated a physician record'],
        ];
        if (isset($known[$routeName])) {
            return $known[$routeName];
        }

        $section = Str::headline(str_replace(['.', '-'], ' ', $routeName));
        return match (strtoupper($method)) {
            'GET', 'HEAD' => ['view', "Viewed {$section}"],
            'POST' => ['create', "Created or submitted {$section}"],
            'PUT', 'PATCH' => ['update', "Updated {$section}"],
            'DELETE' => ['delete', "Deleted {$section}"],
            default => ['action', "Accessed {$section}"],
        };
    }

    private function subject(Request $request): array
    {
        foreach ($request->route()?->parameters() ?? [] as $name => $value) {
            if ($value instanceof Model) {
                return [class_basename($value), $value->getKey()];
            }
            if (is_numeric($value)) {
                return [Str::studly(Str::singular($name)), (int) $value];
            }
        }

        return [null, null];
    }
}
