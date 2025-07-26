<?php

namespace App\Websockets\SocketHandler;

use Exception;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Ratchet\WebSocket\MessageComponentInterface;

class UpdatePostSocketHandler extends BaseSocketHandler implements MessageComponentInterface
{

    function onMessage(ConnectionInterface $from, MessageInterface $msg)
    {
        //TODO: update post

    }
}
