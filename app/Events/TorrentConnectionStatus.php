<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TorrentConnectionStatus implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $status, // 'connecting', 'connected', 'failed'
        public ?string $clientName = null,
        public ?string $message = null
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('torrent-connection'),
        ];
    }
}
