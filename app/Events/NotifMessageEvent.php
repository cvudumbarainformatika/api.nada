<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotifMessageEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public $message;
    public $user;
    public $chn;
    public function __construct($message, $chn, User $user)
    {
        $this->message = $message;
        $this->user = $user;
        $this->chn = $chn;
        // $this->dontBroadcastToCurrentUser();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return [
            new PrivateChannel("private.notif.{$this->chn}"),
        ];
    }

    public function broadcastAs()
    {
        return 'notif-message';
    }

    public function broadcastWith()
    {
        return [
            'message' => $this->message,
            'sender' => $this->user->only(['pegawai_id', 'nama', 'kdpegsimrs'])
        ];
    }
}
