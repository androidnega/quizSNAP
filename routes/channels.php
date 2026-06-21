<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('quizsnap-monitoring', function ($user) {
    return $user instanceof User && $user->canAccessMonitoring();
});

Broadcast::channel('quizsnap-operations', function ($user) {
    return $user instanceof User && $user->canAccessOperations();
});

Broadcast::channel('quizsnap-intelligence', function ($user) {
    return $user instanceof User && $user->canAccessIntelligence();
});
