<?php

use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns default values for unset keys', function () {
    $service = app(SettingsService::class);

    expect($service->get('torrenting.client'))->toBe('uTorrent')
        ->and($service->get('calendar.mode'))->toBe('date')
        ->and($service->get('calendar.startSunday'))->toBeTrue()
        ->and($service->get('autodownload.delay'))->toBe(15)
        ->and($service->get('background-rotator.opacity'))->toBe(0.4);
});

it('returns null for unknown keys with no default', function () {
    $service = app(SettingsService::class);

    expect($service->get('nonexistent.key'))->toBeNull();
});

it('returns custom default for unknown keys', function () {
    $service = app(SettingsService::class);

    expect($service->get('nonexistent.key', 'fallback'))->toBe('fallback');
});

it('persists and retrieves string settings', function () {
    $service = app(SettingsService::class);

    $service->set('torrenting.client', 'qBittorrent');

    // Verify in database
    $row = Setting::find('torrenting.client');
    expect($row)->not->toBeNull()
        ->and($row->value)->toBe('qBittorrent');

    // Verify via get
    expect($service->get('torrenting.client'))->toBe('qBittorrent');
});

it('persists and retrieves boolean settings', function () {
    $service = app(SettingsService::class);

    $service->set('torrenting.enabled', false);
    expect($service->get('torrenting.enabled'))->toBeFalse();
});

it('persists and retrieves array settings as json', function () {
    $service = app(SettingsService::class);

    $engines = ['ThePirateBay' => true, '1337x' => false];
    $service->set('autodownload.multiSE', $engines);

    // Verify it's stored as JSON in the database
    $row = Setting::find('autodownload.multiSE');
    expect($row->value)->toBeJson();

    // Verify it comes back as an array
    expect($service->get('autodownload.multiSE'))->toBe($engines);
});

it('provides all settings merged with defaults', function () {
    $service = app(SettingsService::class);
    $service->set('torrenting.client', 'Transmission');

    $all = $service->all();

    expect($all)->toBeArray()
        ->and($all['torrenting.client'])->toBe('Transmission')
        ->and($all['calendar.mode'])->toBe('date');
});

it('works via the global helper', function () {
    settings('test.custom', 'hello');
    expect(settings('test.custom'))->toBe('hello');

    // Default from SettingsService
    expect(settings('torrenting.searchprovider'))->toBe('ThePirateBay');
});

it('restores settings from database on fresh instance', function () {
    // Persist a setting
    Setting::create(['key' => 'torrenting.client', 'value' => 'Deluge']);

    // Create a fresh service instance (simulates app restart)
    $service = new SettingsService;
    expect($service->get('torrenting.client'))->toBe('Deluge');
});

it('has the correct number of default settings', function () {
    $service = app(SettingsService::class);
    $defaults = $service->getDefaults();

    // The Angular SettingsService had about 145 settings
    expect(count($defaults))->toBeGreaterThan(100);
});
