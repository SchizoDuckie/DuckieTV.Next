<?php

namespace Tests\Feature\Controllers;

use App\Jobs\RestoreBackupJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_restore_endpoint_dispatches_job()
    {
        Bus::fake();

        $file = UploadedFile::fake()->createWithContent('backup.json', '{}');
        
        $response = $this->postJson(route('settings.restore'), [
            'backup_file' => $file
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        Bus::assertDispatched(RestoreBackupJob::class);
    }

    public function test_restore_progress_endpoint_returns_json()
    {
        Cache::put('backup_progress', ['percent' => 50, 'status' => 'running']);

        $response = $this->getJson(route('settings.restore-progress'));

        $response->assertStatus(200)
            ->assertJson(['percent' => 50, 'status' => 'running']);
    }
}
