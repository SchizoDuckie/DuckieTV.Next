<?php

namespace App\Services;

use App\Models\Setting;

class SettingsService
{
    private array $cache = [];
    private bool $loaded = false;

    /**
     * All default settings, ported 1:1 from SettingsService.js lines 84-262.
     */
    private array $defaults = [
        // ─── Torrent Search Mirrors ─────────────────────────────
        'ThePirateBay.mirror' => 'https://thepiratebay0.org/',
        'mirror.ThePirateBay' => 'https://thepiratebay0.org/',
        'mirror.1337x' => 'https://1337x.to',
        'mirror.ETag' => 'https://extratorrent.st',
        'mirror.FileMood' => 'https://filemood.com',
        'mirror.Idope' => 'https://idope.me',
        'mirror.IsoHunt2' => 'https://isohunt.sh',
        'mirror.KATws' => 'https://kickass.ws',
        'mirror.Knaben' => 'https://knaben.org',
        'mirror.LimeTorrents' => 'https://www.limetorrents.fun',
        'mirror.Nyaa' => 'https://nyaa.si',
        'mirror.PiratesParadise' => 'https://piratesparadise.org',
        'mirror.ShowRSS' => 'https://showrss.info',
        'mirror.theRARBG' => 'https://therarbg.to',
        'mirror.TorrentDownloads' => 'https://www.torrentdownloads.pro',
        'mirror.Uindex' => 'https://uindex.org',

        // ─── Application ────────────────────────────────────────
        'application.language' => null,
        'application.locale' => 'en_us',

        // ─── Aria2 ─────────────────────────────────────────────
        'aria2.port' => 6800,
        'aria2.server' => 'http://localhost',
        'aria2.token' => '',

        // ─── Auto-backup ───────────────────────────────────────
        'autobackup.period' => 'monthly',

        // ─── Auto-download ─────────────────────────────────────
        'autodownload.delay' => 15,
        'autodownload.multiSE' => [
            'ThePirateBay' => true,
            '1337x' => true,
            'ETag' => true,
            'FileMood' => true,
            'Idope' => true,
            'IsoHunt2' => true,
            'KATws' => true,
            'Knaben' => true,
            'LimeTorrents' => true,
            'Nyaa' => true,
            'PiratesParadise' => true,
            'ShowRSS' => true,
            'theRARBG' => true,
            'TorrentDownloads' => true,
            'Uindex' => true,
        ],
        'autodownload.multiSE.enabled' => false,
        'autodownload.period' => 1,

        // ─── Display ───────────────────────────────────────────
        'background-rotator.opacity' => 0.4,
        'font.bebas.enabled' => true,
        'series.displaymode' => 'poster',
        'series.not-watched-eps-btn' => false,
        'library.order.by' => 'getSortName()',
        'library.order.reverseList' => [true, false, true, true],
        'library.seriesgrid' => true,
        'library.smallposters' => true,
        'main.viewmode' => 'calendar',

        // ─── BiglyBT ───────────────────────────────────────────
        'biglybt.password' => '',
        'biglybt.path' => '/transmission/rpc',
        'biglybt.port' => 9091,
        'biglybt.progressX100' => true,
        'biglybt.server' => 'http://localhost',
        'biglybt.use_auth' => true,
        'biglybt.username' => 'biglybt',

        // ─── Calendar ──────────────────────────────────────────
        'calendar.mode' => 'date',
        'calendar.show-downloaded' => true,
        'calendar.show-episode-numbers' => false,
        'calendar.show-specials' => true,
        'calendar.startSunday' => true,

        // ─── Locale ────────────────────────────────────────────
        'client.determinedlocale' => null,

        // ─── Deluge ────────────────────────────────────────────
        'deluge.password' => 'deluge',
        'deluge.port' => 8112,
        'deluge.server' => 'http://localhost',
        'deluge.use_auth' => true,

        // ─── Download ──────────────────────────────────────────
        'download.ratings' => true,
        'episode.watched-downloaded.pairing' => true,

        // ─── Konami code ───────────────────────────────────────
        'kc.always' => false,

        // ─── KTorrent ──────────────────────────────────────────
        'ktorrent.password' => 'ktorrent',
        'ktorrent.port' => 8080,
        'ktorrent.server' => 'http://localhost',
        'ktorrent.use_auth' => true,
        'ktorrent.username' => 'ktorrent',

        // ─── Sync ──────────────────────────────────────────────
        'lastSync' => -1,
        'storage.sync' => false,
        'sync.progress' => true,

        // ─── Notifications ─────────────────────────────────────
        'notifications.enabled' => true,

        // ─── qBittorrent ───────────────────────────────────────
        'qbittorrent32plus.password' => 'admin',
        'qbittorrent32plus.port' => 8080,
        'qbittorrent32plus.server' => 'http://localhost',
        'qbittorrent32plus.use_auth' => true,
        'qbittorrent32plus.username' => 'admin',

        // ─── rTorrent ──────────────────────────────────────────
        'rtorrent.path' => '/RPC2',
        'rtorrent.port' => 80,
        'rtorrent.server' => 'http://localhost',
        'rtorrent.use_auth' => false,

        // ─── Subtitles ─────────────────────────────────────────
        'subtitles.languages' => ['eng'],

        // ─── Synology ──────────────────────────────────────────
        'synology.enabled' => false,
        'synology.ip' => '192.168.x.x',
        'synology.password' => 'password',
        'synology.playback_devices' => [],
        'synology.port' => 5000,
        'synology.protocol' => 'http',
        'synology.username' => 'admin',

        // ─── Tixati ────────────────────────────────────────────
        'tixati.password' => 'admin',
        'tixati.port' => 8888,
        'tixati.server' => 'http://localhost',
        'tixati.use_auth' => true,
        'tixati.username' => 'admin',

        // ─── Top Sites ─────────────────────────────────────────
        'topSites.enabled' => false,
        'topSites.mode' => 'onhover',

        // ─── Torrent Dialog ────────────────────────────────────
        'torrentDialog.2.activeSE' => [
            'ThePirateBay' => true,
            '1337x' => true,
            'ETag' => true,
            'FileMood' => true,
            'Idope' => true,
            'IsoHunt2' => true,
            'KATws' => true,
            'Knaben' => true,
            'LimeTorrents' => true,
            'Nyaa' => true,
            'PiratesParadise' => true,
            'ShowRSS' => true,
            'theRARBG' => true,
            'TorrentDownloads' => true,
            'Uindex' => true,
        ],
        'torrentDialog.2.enabled' => false,
        'torrentDialog.2.sortBy' => '+engine',
        'torrentDialog.showAdvanced.enabled' => true,

        // ─── Torrenting ────────────────────────────────────────
        'torrenting.autodownload' => false,
        'torrenting.autostop' => true,
        'torrenting.autostop_all' => false,
        'torrenting.client' => 'uTorrent',
        'torrenting.directory' => true,
        'torrenting.enabled' => true,
        'torrenting.global_size_max' => null,
        'torrenting.global_size_max_enabled' => true,
        'torrenting.global_size_min' => null,
        'torrenting.global_size_min_enabled' => true,
        'torrenting.ignore_keywords' => '',
        'torrenting.ignore_keywords_enabled' => true,
        'torrenting.label' => false,
        'torrenting.launch_via_chromium' => false,
        'torrenting.min_seeders' => 50,
        'torrenting.min_seeders_enabled' => false,
        'torrenting.progress' => true,
        'torrenting.require_keywords' => '',
        'torrenting.require_keywords_enabled' => true,
        'torrenting.require_keywords_mode_or' => true,
        'torrenting.searchprovider' => 'ThePirateBay',
        'torrenting.searchquality' => '',
        'torrenting.searchqualitylist' => ['HDTV', 'WEB', '720p', '1080p', '2160p', 'x265'],
        'torrenting.streaming' => true,

        // ─── Trakt ─────────────────────────────────────────────
        'trakt-update.period' => 1,
        'trakttv.passwordHash' => null,
        'trakttv.sync' => false,
        'trakttv.sync-downloaded' => true,
        'trakttv.username' => null,

        // ─── Transmission ──────────────────────────────────────
        'transmission.password' => 'admin',
        'transmission.path' => '/transmission/rpc',
        'transmission.port' => 9091,
        'transmission.progressX100' => true,
        'transmission.server' => 'http://localhost',
        'transmission.use_auth' => true,
        'transmission.username' => 'admin',

        // ─── tTorrent ──────────────────────────────────────────
        'ttorrent.password' => '',
        'ttorrent.port' => 1080,
        'ttorrent.server' => 'http://localhost',
        'ttorrent.use_auth' => true,
        'ttorrent.username' => 'admin',

        // ─── uTorrent WebUI ────────────────────────────────────
        'utorrentwebui.password' => '',
        'utorrentwebui.port' => 8080,
        'utorrentwebui.server' => 'http://localhost',
        'utorrentwebui.use_auth' => true,
        'utorrentwebui.username' => 'admin',

        // ─── Vuze ──────────────────────────────────────────────
        'vuze.password' => '',
        'vuze.path' => '/transmission/rpc',
        'vuze.port' => 9091,
        'vuze.progressX100' => true,
        'vuze.server' => 'http://localhost',
        'vuze.use_auth' => true,
        'vuze.username' => 'vuze',
    ];

    /**
     * Get a setting value by key.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->ensureLoaded();

        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        if (array_key_exists($key, $this->defaults)) {
            return $this->defaults[$key];
        }

        return $default;
    }

    /**
     * Set a setting value by key, persisting immediately to the database.
     */
    public function set(string $key, mixed $value): void
    {
        $this->ensureLoaded();
        $this->cache[$key] = $value;

        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => is_scalar($value) || is_null($value) ? $value : json_encode($value)]
        );
    }

    /**
     * Load all settings from database into cache.
     */
    public function restore(): void
    {
        $this->cache = [];

        Setting::all()->each(function (Setting $setting) {
            $key = $setting->key;
            $value = $setting->value;

            // If the default is an array/object, decode the stored JSON
            if (array_key_exists($key, $this->defaults) && is_array($this->defaults[$key])) {
                $decoded = json_decode($value, true);
                $this->cache[$key] = $decoded !== null ? $decoded : $value;
            } elseif (array_key_exists($key, $this->defaults) && is_bool($this->defaults[$key])) {
                $this->cache[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            } elseif (array_key_exists($key, $this->defaults) && is_int($this->defaults[$key])) {
                $this->cache[$key] = (int) $value;
            } elseif (array_key_exists($key, $this->defaults) && is_float($this->defaults[$key])) {
                $this->cache[$key] = (float) $value;
            } else {
                $this->cache[$key] = $value;
            }
        });

        $this->loaded = true;
    }

    /**
     * Persist entire cache to the database (batch write).
     */
    public function persist(): void
    {
        foreach ($this->cache as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => is_scalar($value) || is_null($value) ? $value : json_encode($value)]
            );
        }
    }

    /**
     * Get all settings as an array (cache merged over defaults).
     */
    public function all(): array
    {
        $this->ensureLoaded();

        return array_merge($this->defaults, $this->cache);
    }

    /**
     * Get the defaults array.
     */
    public function getDefaults(): array
    {
        return $this->defaults;
    }

    private function ensureLoaded(): void
    {
        if (!$this->loaded) {
            $this->restore();
        }
    }

    /**
     * Restore settings from a backup array.
     * Matches logic from BackupCtrl.js importBackup().
     */
    public function restoreSettings(array $settings): void
    {
        foreach ($settings as $key => $value) {
            // Skip invalid or legacy keys
            if ($key === 'utorrent.token') continue;
            if ($key === 'database.version') continue;
            
            // Handle Jackett specially (legacy format) - skipping for now as per plan
            if ($key === 'jackett') continue;

            // Handle useTrakt_id flag (logic handled in caller usually, but we can store it or ignore it)
            if ($key === 'useTrakt_id') continue;

            $this->set($key, $value);
        }
    }
}
