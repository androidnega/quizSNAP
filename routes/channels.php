<?php

use App\Models\User;
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
