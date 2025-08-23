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

    /**
     * Create a new event instance.
     */
    public function __construct($scadaData, $channel = 'scada-channel')
    {
        $this->scadaData = $scadaData;
        $this->timestamp = now();
        $this->channel = $channel;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel($this->channel),
            new Channel('scada-realtime'),
            new Channel('scada-analysis'),
        ];
    }

    /**
     * Get the event name for broadcasting.
     */
    public function broadcastAs(): string
    {
        return 'scada.data.received';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'data' => $this->scadaData,
            'timestamp' => $this->timestamp->toISOString(),
            'channel' => $this->channel,
            'event_type' => 'scada_data',
            'source' => 'scada_system'
        ];
    }

    /**
     * Determine if this event should broadcast.
     */
    public function broadcastWhen(): bool
    {
        // Only broadcast if we have valid data
        return !empty($this->scadaData) && is_array($this->scadaData);
    }
}
