<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;

final class UserSessionManager
{
    public function enforceLimit(User $user, ?string $currentSessionId = null): void
    {
        $this->clearRememberMe($user);

        if (config('session.driver') !== 'database') {
            return;
        }

        $connection = config('session.connection');
        $table = (string) config('session.table', 'sessions');
        $lifetime = max(1, (int) config('session.lifetime', 259200));
        $maxDevices = max(1, (int) config('session.max_devices', 4));
        $expiredBefore = now()->subMinutes($lifetime)->getTimestamp();

        DB::connection($connection)
            ->table($table)
            ->where('user_id', $user->id)
            ->where('last_activity', '<', $expiredBefore)
            ->delete();

        $query = DB::connection($connection)
            ->table($table)
            ->where('user_id', $user->id)
            ->orderByDesc('last_activity');

        if ($currentSessionId !== null && $currentSessionId !== '') {
            $query->where('id', '!=', $currentSessionId);
        }

        $otherSessionIds = $query->pluck('id')->all();
        $allowedOthers = max(0, $maxDevices - 1);
        $sessionsToDelete = array_slice($otherSessionIds, $allowedOthers);

        if ($sessionsToDelete !== []) {
            DB::connection($connection)
                ->table($table)
                ->whereIn('id', $sessionsToDelete)
                ->delete();
        }
    }

    public function revokeOtherSessions(User $user, ?string $currentSessionId = null): void
    {
        $this->clearRememberMe($user);

        if (config('session.driver') !== 'database') {
            return;
        }

        $connection = config('session.connection');
        $table = (string) config('session.table', 'sessions');

        $query = DB::connection($connection)
            ->table($table)
            ->where('user_id', $user->id);

        if ($currentSessionId !== null && $currentSessionId !== '') {
            $query->where('id', '!=', $currentSessionId);
        }

        $query->delete();
    }

    private function clearRememberMe(User $user): void
    {
        if ($user->getRememberToken() !== null) {
            $user->setRememberToken(null);
            $user->saveQuietly();
        }

        $guard = Auth::guard('web');

        if (method_exists($guard, 'getRecallerName')) {
            Cookie::queue(Cookie::forget(
                $guard->getRecallerName(),
                (string) config('session.path', '/'),
                config('session.domain'),
            ));
        }
    }
}
