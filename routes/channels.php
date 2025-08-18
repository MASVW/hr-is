<?php
use Illuminate\Support\Facades\Broadcast;

Broadcast::routes(['middleware' => ['web', 'auth']]);


Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (string) $user->getKey() === (string) $id;
});
