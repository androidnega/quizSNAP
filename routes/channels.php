<?php

use App\Models\User;
use App\Models\SupportSession;
use App\Support\EnterpriseBroadcasting;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    $resolved = $user instanceof User ? $user : EnterpriseBroadcasting::resolveUser();

    return $resolved instanceof User && (int) $resolved->id === (int) $id;
});

Broadcast::channel('quizsnap-monitoring', function () {
    return EnterpriseBroadcasting::authorize();
});

Broadcast::channel('quizsnap-operations', function () {
    return EnterpriseBroadcasting::authorize();
});

Broadcast::channel('quizsnap-intelligence', function () {
    return EnterpriseBroadcasting::authorize();
});

Broadcast::channel('support-inbox', function ($user) {
    $resolved = $user instanceof User ? $user : null;

    return $resolved instanceof User && $resolved->isSuperAdmin()
        ? ['id' => $resolved->id, 'role' => 'admin']
        : false;
});

Broadcast::channel('support-session.{uuid}', function ($user, string $uuid) {
    if ($user instanceof User && $user->isSuperAdmin()) {
        return ['id' => 'admin:'.$user->id, 'role' => 'admin'];
    }

    $token = request()->header('X-Support-Session-Token') ?: request()->input('session_token');
    $session = SupportSession::where('uuid', $uuid)->where('status', '!=', SupportSession::STATUS_CLOSED)->first();
    if ($session && $token && hash_equals($session->client_token, $token)) {
        return ['id' => 'student:'.$session->id, 'role' => 'student'];
    }

    return false;
});
