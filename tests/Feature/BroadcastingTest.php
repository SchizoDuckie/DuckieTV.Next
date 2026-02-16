<?php

use App\Events\TorrentConnectionStatus;
use Illuminate\Support\Facades\Event;

test('it broadcasts torrent connection status', function () {
    Event::fake();

    TorrentConnectionStatus::dispatch('connecting', 'uTorrent', 'Connecting...');

    Event::assertDispatched(TorrentConnectionStatus::class, function ($event) {
        return $event->status === 'connecting'
            && $event->clientName === 'uTorrent'
            && $event->message === 'Connecting...';
    });
});
