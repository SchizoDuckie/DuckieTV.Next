<?php

use App\Models\Jackett;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a jackett indexer', function () {
    $jackett = Jackett::create([
        'name' => 'Test Indexer',
        'torznab' => 'http://localhost:9117/api/v2.0/indexers/test/results/torznab/',
        'enabled' => 0,
        'torznabEnabled' => 1,
        'apiKey' => 'abc123',
    ]);

    expect($jackett->name)->toBe('Test Indexer')
        ->and($jackett->isEnabled())->toBeFalse();
});

it('enables and disables', function () {
    $jackett = Jackett::create([
        'name' => 'Test',
        'enabled' => 0,
    ]);

    $jackett->setEnabled();
    $jackett->refresh();
    expect($jackett->isEnabled())->toBeTrue();

    $jackett->setDisabled();
    $jackett->refresh();
    expect($jackett->isEnabled())->toBeFalse();
});

it('stores json as array', function () {
    $jackett = Jackett::create([
        'name' => 'Test',
        'json' => ['caps' => ['search' => true]],
    ]);

    $jackett->refresh();
    expect($jackett->json)->toBeArray()
        ->and($jackett->json['caps']['search'])->toBeTrue();
});
