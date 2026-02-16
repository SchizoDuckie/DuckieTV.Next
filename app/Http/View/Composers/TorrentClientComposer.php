<?php

namespace App\Http\View\Composers;

use App\Services\TorrentClientService;
use Illuminate\View\View;

class TorrentClientComposer
{
    protected $torrentService;

    public function __construct(TorrentClientService $torrentService)
    {
        $this->torrentService = $torrentService;
    }

    /**
     * Bind data to the view.
     *
     * @param  View  $view
     * @return void
     */
    public function compose(View $view)
    {
        $activeClient = $this->torrentService->getActiveClient();
        $clientClass = 'none';

        if ($activeClient) {
            $name = strtolower($activeClient->getName());
            if (str_contains($name, 'utorrent')) $clientClass = 'utorrent';
            elseif (str_contains($name, 'qbittorrent')) $clientClass = 'qbittorrent';
            elseif (str_contains($name, 'transmission')) $clientClass = 'transmission';
            elseif (str_contains($name, 'deluge')) $clientClass = 'deluge';
            elseif (str_contains($name, 'vuze')) $clientClass = 'vuze';
            elseif (str_contains($name, 'biglybt')) $clientClass = 'biglybt';
            elseif (str_contains($name, 'tixati')) $clientClass = 'tixati';
            elseif (str_contains($name, 'rtorrent')) $clientClass = 'rtorrent';
            elseif (str_contains($name, 'ktorrent')) $clientClass = 'ktorrent';
            elseif (str_contains($name, 'aria2')) $clientClass = 'aria2';
            elseif (str_contains($name, 'ttorrent')) $clientClass = 'ttorrent';
        }

        $view->with('activeClient', $activeClient);
        $view->with('clientClass', $clientClass);
    }
}
