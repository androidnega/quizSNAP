<?php

namespace App\Http\Middleware;

use App\Models\SupportSession;
use App\Models\User;
use App\Support\LiveSupportAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureBroadcastingAuthenticated
{
    /**
     * Authenticate staff or live-support clients for /broadcasting/auth (Pusher expects JSON).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $channelName = (string) $request->input('channel_name', '');

        if ($this->isSupportChannel($channelName)) {
            return $this->authorizeSupport($request, $next);
        }

        if (! session('admin_authenticated', false) || ! session('admin_user_id')) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user = User::find(session('admin_user_id'));
        if (! $user instanceof User || ! $user->canAccessEnterpriseBroadcasting()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        session(['admin_role' => $user->role]);

        Auth::setUser($user);
        $request->setUserResolver(static fn () => $user);

        return $next($request);
    }

    private function isSupportChannel(string $channelName): bool
    {
        return str_contains($channelName, 'support-session.')
            || str_contains($channelName, 'support-inbox');
    }

    private function authorizeSupport(Request $request, Closure $next): Response
    {
        if (session('admin_authenticated', false)) {
            $user = User::find(session('admin_user_id'));
            if ($user instanceof User && LiveSupportAccess::canRespond($user)) {
                Auth::setUser($user);
                $request->setUserResolver(static fn () => $user);

                return $next($request);
            }
        }

        $channelName = (string) $request->input('channel_name', '');
        if (preg_match('/private-support-session\.([0-9a-f-]{36})/i', $channelName, $matches)) {
            $token = $request->header('X-Support-Session-Token') ?: $request->input('session_token');
            $session = SupportSession::where('uuid', $matches[1])->first();
            if ($session && $token && hash_equals($session->client_token, $token)) {
                return $next($request);
            }
        }

        return response()->json(['message' => 'Forbidden.'], 403);
    }
}
