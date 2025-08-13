<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ScadaDataReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $scadaData;
    public $timestamp;
    public $channel;
    public $batchId;

    public function __construct($scadaData, $channel = null, $batchId = null)
    {
        $this->scadaData = $scadaData;
        $this->timestamp = now();
        $this->channel = $channel ?? 'scada-data';
        $this->batchId = $batchId ?? uniqid();
    }

    public function broadcastOn()
    {
        return new Channel($this->channel);
    }

    public function broadcastAs()
    {
        return 'scada.data.received';
    }

    public function broadcastWith()
    {
        return [
            'data' => $this->scadaData,
            'timestamp' => $this->timestamp->toISOString(),
            'channel' => $this->channel,
            'batch_id' => $this->batchId,
            'event_type' => 'scada.data.received'
        ];
    }
}
