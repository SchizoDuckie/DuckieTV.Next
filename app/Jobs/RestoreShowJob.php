<?php

namespace App\Jobs;

use App\Services\BackupService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RestoreShowJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 180; // 3 minutes per show should be plenty

    /**
     * Retry up to 3 times with exponential backoff.
     * SQLite contention can cause transient failures, especially when
     * multiple jobs are being processed and the queue worker is busy.
     */
    public $tries = 3;

    public $backoff = [5, 15]; // seconds between retries

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected string $seriesId,
        protected array $backupData
    ) {}

    /**
     * Execute the job.
     */
    public function handle(BackupService $backupService): void
    {
        if ($this->batch()->cancelled()) {

            return;
        }

        try {
            $backupService->restoreShow($this->seriesId, $this->backupData, function ($percent, $message) {
                // Update Cache via Batch Progress Proxy?
                // Actually, updating the main cache key directly is risky with race conditions if we blast it.
                // But since we are running potentially in parallel (if workers > 1), we should be careful.
                // However, for now, let's assume one worker or acceptable race for logs.

                // Fetch current state
                // We utilize the cache key 'backup_progress' as the source of truth for the frontend

                $data = Cache::get('backup_progress', ['logs' => []]);

                // Calculate Global Progress based on Batch
                // The batch progress is the most accurate global indicator
                $batchProgress = $this->batch()->progress();
                $data['percent'] = $batchProgress;

                // Append Logs
                $msgText = is_array($message) ? ($message['message'] ?? null) : $message;
                if ($msgText) {
                    $data['logs'][] = date('H:i:s').' - '.$msgText;
                    if (count($data['logs']) > 500) {
                        array_shift($data['logs']);
                    }
                }

                // Show Specific Progress (Ephemeral)
                if (is_array($message)) {
                    unset($message['logs']);
                    $data = array_merge($data, $message);
                } else {
                    $data['message'] = $message;
                }

                $data['status'] = 'running';

                Cache::put('backup_progress', $data);
            });

        } catch (\App\Exceptions\RateLimitException $e) {
            Log::info("RestoreShowJob hit Trakt rate limit for ID {$this->seriesId}, releasing back to queue for {$e->retryAfter}s");
            $this->release($e->retryAfter);
        } catch (\Throwable $e) {
            Log::error("RestoreShowJob failed for ID {$this->seriesId}: ".$e->getMessage());

            // Record failure in global state
            $data = Cache::get('backup_progress', ['logs' => [], 'failed_series' => []]);
            $data['failed_series'][] = [
                'id' => $this->seriesId,
                'error' => $e->getMessage(),
                'time' => date('H:i:s'),
            ];
            $data['logs'][] = date('H:i:s')." - ERROR: Failed to restore series ID {$this->seriesId}: ".$e->getMessage();
            Cache::put('backup_progress', $data);

            throw $e;
        }
    }
}
