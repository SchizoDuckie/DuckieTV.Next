<?php

namespace App\Jobs;

use App\Services\BackupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RestoreBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour timeout for slow/throttled restores

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected array $backupData
    ) {}

    /**
     * Execute the job.
     */
    public function handle(BackupService $backupService): void
    {
        // Initial Cache State

        Cache::put('backup_progress', [
            'percent' => 0, 
            'message' => 'Initializing restore batch...', 
            'logs' => [], 
            'status' => 'running',
            'show_progress' => null,
            'batch_id' => null
        ]);

        try {
            // 1. Restore Settings First (Fast, Synchronous)
            if (isset($this->backupData['settings'])) {
                $backupService->restore(['settings' => $this->backupData['settings']], function($p, $m) {
                     // specific log update if needed
                });
                
                $data = Cache::get('backup_progress', ['logs' => []]);
                $data['logs'][] = date('H:i:s') . ' - Settings restored.';
                Cache::put('backup_progress', $data);
            }

            // 2. Build Jobs via Generator/Array
            $series = $this->backupData['series'] ?? [];
            $jobs = [];
            foreach ($series as $id => $seriesData) {
                $jobs[] = new RestoreShowJob((string)$id, $seriesData);
            }
            
            if (empty($jobs)) {
                $data = Cache::get('backup_progress');
                $data['percent'] = 100;
                $data['message'] = 'Restore complete (No series found).';
                $data['status'] = 'completed';
                Cache::put('backup_progress', $data);
                return;
            }

            // 3. Dispatch Batch
            $batch = \Illuminate\Support\Facades\Bus::batch($jobs)
                ->then(function (\Illuminate\Bus\Batch $batch) {
                    // All jobs completed successfully
                    $data = Cache::get('backup_progress', ['logs' => []]);
                    $data['percent'] = 100;
                    $data['status'] = 'completed';
                    $data['message'] = 'Restore complete!';
                    $data['logs'][] = date('H:i:s') . ' - Finalizing... Restore Complete!';
                    Cache::put('backup_progress', $data);
                })
                ->catch(function (\Illuminate\Bus\Batch $batch, \Throwable $e) {
                    // First batch job failure detected
                    Log::error('Batch failed: ' . $e->getMessage());
                    $data = Cache::get('backup_progress', ['logs' => []]);
                    $data['status'] = 'failed';
                    $data['message'] = 'One or more shows failed to restore.';
                    $data['logs'][] = date('H:i:s') . ' - ERROR: Batch failure detected.';
                    Cache::put('backup_progress', $data);
                })
                ->finally(function (\Illuminate\Bus\Batch $batch) {
                    // Batch finished executing (success or fail)
                    // Cleanup if needed
                })
                ->allowFailures()
                ->name('Restoring Backup (' . count($jobs) . ' shows)')
                ->dispatch();

            // Store Batch ID for cancellation
            $data = Cache::get('backup_progress');
            $data['batch_id'] = $batch->id;
            $data['message'] = 'Batch dispatched. Processing ' . count($jobs) . ' shows...';
            Cache::put('backup_progress', $data);

        } catch (\App\Exceptions\RateLimitException $e) {
            Log::info("RestoreBackupJob hit Trakt rate limit, releasing back to queue for {$e->retryAfter}s");
            $this->release($e->retryAfter);
        } catch (\Throwable $e) {
            Log::error('RestoreBackupJob failed: ' . $e->getMessage());
            
            $data = Cache::get('backup_progress', ['logs' => []]);
            $data['status'] = 'failed';
            $data['message'] = 'Error: ' . $e->getMessage();
            $data['logs'][] = date('H:i:s') . ' - ERROR: ' . $e->getMessage();
            
            Cache::put('backup_progress', $data);
            
            throw $e;
        }
    }
}
