<?php

namespace App\Services\TorrentClients;

use App\Services\SettingsService;

/**
 * BiglyBT Client Implementation.
 * 
 * BiglyBT uses the Transmission RPC API, so we inherit all logic 
 * from TransmissionClient and simply override the configuration mapping.
 * 
 * @see BiglyBT.js in DuckieTV-angular.
 */
class BiglyBTClient extends TransmissionClient
{
    public function __construct(SettingsService $settings)
    {
        parent::__construct($settings);
        $this->name = 'BiglyBT';
        $this->id = 'biglybt';
    }

    public function getValidationRules(): array
    {
        return [
            'biglybt.server' => 'nullable|url',
            'biglybt.port' => 'nullable|integer',
            'biglybt.path' => 'nullable|string',
            'biglybt.use_auth' => 'boolean',
            'biglybt.username' => 'nullable|string',
            'biglybt.password' => 'nullable|string',
            'biglybt.progressX100' => 'boolean',
        ];
    }

    /**
     * Map configuration to BiglyBT specific settings.
     * 
     * @return array
     */
    protected function getConfigMappings(): array
    {
        return [
            'server'       => 'biglybt.server',
            'port'         => 'biglybt.port',
            'path'         => 'biglybt.path',
            'username'     => 'biglybt.username',
            'password'     => 'biglybt.password',
            'use_auth'     => 'biglybt.use_auth',
            'progressX100' => 'biglybt.progressX100',
        ];
    }
}
