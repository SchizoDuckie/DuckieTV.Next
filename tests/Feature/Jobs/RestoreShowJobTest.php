<?php

namespace Tests\Feature\Jobs;

use App\Jobs\RestoreShowJob;
use App\Services\BackupService;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class RestoreShowJobTest extends TestCase
{
    public function test_handle_calls_service_and_updates_cache()
    {
        // Mock Batch
        $batch = Mockery::mock(Batch::class);
        $batch->shouldReceive('cancelled')->andReturn(false);
        $batch->shouldReceive('progress')->andReturn(50);

        // Mock BackupService
        $service = Mockery::mock(BackupService::class);
        $service->shouldReceive('restoreShow')
            ->with('123', [], Mockery::type('callable'))
            ->once()
            ->andReturnUsing(function ($id, $data, $callback) {
                $callback(100, 'Done');

                return true;
            });

        // Mock Cache
        Cache::shouldReceive('get')->andReturn(['logs' => []]);
        Cache::shouldReceive('put')->once()->with('backup_progress', Mockery::on(function ($data) {
            return $data['percent'] === 50 && $data['message'] === 'Done';
        }));

        // Use Test Double
        $job = new TestRestoreShowJob('123', []);
        $job->setBatch($batch);

        $job->handle($service);

        $this->assertTrue(true);
    }

    public function test_handle_aborts_if_cancelled()
    {
        $batch = Mockery::mock(Batch::class);
        $batch->shouldReceive('cancelled')->andReturn(true);

        $service = Mockery::mock(BackupService::class);
        $service->shouldReceive('restoreShow')->never();

        $job = new TestRestoreShowJob('123', []);
        $job->setBatch($batch);

        $job->handle($service);

        $this->assertTrue(true);
    }
}

class TestRestoreShowJob extends RestoreShowJob
{
    protected $testBatch;

    public function setBatch($batch)
    {
        $this->testBatch = $batch;
    }

    public function batch()
    {
        return $this->testBatch;
    }
}
