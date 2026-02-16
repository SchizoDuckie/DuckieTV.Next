<?php

namespace App\Services\TorrentClients;

use App\Services\SettingsService;

/**
 * Vuze Client Implementation.
 * 
 * Vuze uses the Transmission RPC API, so we inherit all logic 
 * from TransmissionClient and simply override the configuration mapping.
 * 
 * @see Vuze.js in DuckieTV-angular.
 */
class VuzeClient extends TransmissionClient
{
    public function __construct(SettingsService $settings)
    {
        parent::__construct($settings);
        $this->name = 'Vuze';
        $this->id = 'vuze';
    }

    public function getValidationRules(): array
    {
        return [
            'vuze.server' => 'nullable|url',
            'vuze.port' => 'nullable|integer',
            'vuze.path' => 'nullable|string',
            'vuze.use_auth' => 'boolean',
            'vuze.username' => 'nullable|string',
            'vuze.password' => 'nullable|string',
            'vuze.progressX100' => 'boolean',
        ];
    }

    /**
     * Map configuration to Vuze specific settings.
     * 
     * @return array
     */
    protected function getConfigMappings(): array
    {
        return [
            'server'       => 'vuze.server',
            'port'         => 'vuze.port',
            'path'         => 'vuze.path',
            'username'     => 'vuze.username',
            'password'     => 'vuze.password',
            'use_auth'     => 'vuze.use_auth',
            'progressX100' => 'vuze.progressX100',
        ];
    }
}
