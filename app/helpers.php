<?php

use App\Services\SettingsService;

if (!function_exists('settings')) {
    /**
     * Get or set a setting value.
     *
     * settings('key')          → get value
     * settings('key', 'value') → set value
     * settings()               → get SettingsService instance
     */
    function settings(?string $key = null, mixed $value = null): mixed
    {
        $service = app(SettingsService::class);

        if ($key === null) {
            return $service;
        }

        if ($value !== null) {
            $service->set($key, $value);
            return $value;
        }

        return $service->get($key);
    }
}
