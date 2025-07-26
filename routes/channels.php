<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('presence.chat.{roomId}', function ($user, $roomId) {
    return $user;
});
Broadcast::channel('private.notif.{roomId}', function ($user, $roomId) {
    // return $user;
    return true;
});

Broadcast::channel('qrcode', function () {
    return true;
});
