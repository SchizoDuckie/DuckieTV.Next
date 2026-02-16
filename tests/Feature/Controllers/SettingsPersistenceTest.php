<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\Setting;
use App\Services\SettingsService;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SettingsPersistenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->singleton(SettingsService::class);
    }

    #[Test]
    public function it_persists_torrent_client_settings()
    {
        $response = $this->postJson(route('settings.update', 'torrent'), [
            'torrenting.client' => 'Transmission',
            'transmission.server' => 'http://192.168.1.100',
            'transmission.port' => 9091,
            'transmission.use_auth' => true,
            'transmission.username' => 'nasuser',
            'transmission.password' => 'naspass'
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Check if settings are actually in the database
        $this->assertEquals('http://192.168.1.100', settings('transmission.server'));
        $this->assertEquals(9091, settings('transmission.port'));
        $this->assertEquals('nasuser', settings('transmission.username'));
        
        // Check database directly to be sure
        $this->assertDatabaseHas('settings', [
            'key' => 'transmission.server',
            'value' => 'http://192.168.1.100'
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'transmission.port',
            'value' => '9091'
        ]);
    }
    
    #[Test]
    public function it_handles_unchecked_checkboxes()
    {
        // First set to true
        settings('transmission.use_auth', true);
        $this->assertTrue(settings('transmission.use_auth'));

        // Submit form without the checkbox
        $response = $this->postJson(route('settings.update', 'torrent'), [
            'torrenting.client' => 'Transmission',
            'transmission.server' => 'http://localhost',
            'transmission.port' => 9091,
            // transmission.use_auth is MISSING
        ]);

        $response->assertStatus(200);
        
        // It SHOULD be false now
        $this->assertFalse(settings('transmission.use_auth'), "Unchecked checkbox should persist as false");
    }

    #[Test]
    public function it_allows_partial_settings_updates()
    {
        // Set initial state
        settings('torrenting.client', 'Transmission');
        settings('torrenting.enabled', true);
        
        // Update ONLY one setting, MISSING torrenting.client
        $response = $this->postJson(route('settings.update', 'torrent'), [
            'transmission.server' => 'http://new-server'
        ]);

        $response->assertStatus(200);
        $this->assertEquals('http://new-server', settings('transmission.server'));
        
        // CRITICAL: torrenting.enabled should STILL be true, not defaulted to false!
        $this->assertTrue(settings('torrenting.enabled'), "Partial update should not reset unrelated booleans");
    }
}
