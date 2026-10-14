<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('recinto', fn ($user) => $user->activo && in_array($user->rol?->slug, ['root', 'presidente', 'secretaria', 'autoridades', 'legisladores'], true));
