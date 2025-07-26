<?php

namespace App\Websockets\SocketHandler;

use BeyondCode\LaravelWebSockets\Apps\App;
use BeyondCode\LaravelWebSockets\QueryParameters;
use BeyondCode\LaravelWebSockets\WebSockets\Exceptions\UnknownAppKey;
use Exception;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Ratchet\WebSocket\MessageComponentInterface;

abstract class BaseSocketHandler implements MessageComponentInterface
{
    protected function verifyAppKey(ConnectionInterface $connection)
    {
        $appKey = QueryParameters::create($connection->httpRequest)->get('appKey');

        if (!$app = App::findByKey($appKey)) {
            throw new UnknownAppKey($appKey);
        }

        $connection->app = $app;

        return $this;
    }
    function onOpen(ConnectionInterface $conn)
    {
        // return response()->json('onOpen');
        $this->verifyAppKey($conn);
    }
    function onClose(ConnectionInterface $conn)
    {
        dump('closed');
    }
    function onError(ConnectionInterface $conn, \Exception $e)
    {
        dump($e);
    }
}
