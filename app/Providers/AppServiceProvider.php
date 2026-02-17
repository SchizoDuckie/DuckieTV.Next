<?php

namespace App\Providers;

use App\Services\CalendarService;
use App\Services\FavoritesService;
use App\Services\SeriesMetaTranslations;
use App\Services\SettingsService;
use App\Services\TraktService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SettingsService::class);
        $this->app->singleton(TraktService::class);
        $this->app->singleton(FavoritesService::class);
        $this->app->singleton(CalendarService::class);
        $this->app->singleton(SeriesMetaTranslations::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Fix SQLite concurrency for NativePHP's internal database.
        //
        // NativePHP uses a separate 'nativephp' SQLite connection for the queue
        // jobs table and job_batches. Both the main PHP process and the queue
        // worker child process write to this database simultaneously.
        // Without WAL mode and a busy_timeout, this causes "database is locked"
        // errors (SQLITE_BUSY / error 5).
        //
        // WAL mode allows concurrent reads + one writer without blocking.
        // busy_timeout tells SQLite to wait and retry instead of failing immediately.
        $this->configureNativephpDatabase();
    }

    /**
     * Configure the NativePHP SQLite database for better concurrency.
     */
    private function configureNativephpDatabase(): void
    {
        try {
            if (config('database.connections.nativephp')) {
                $connection = DB::connection('nativephp');
                $pdo = $connection->getPdo();

                // Enable WAL mode - allows concurrent readers + writer
                $pdo->exec('PRAGMA journal_mode = WAL');

                // Wait up to 5 seconds for locks to clear before failing
                $pdo->exec('PRAGMA busy_timeout = 5000');

                // NORMAL synchronous is safe with WAL and much faster
                $pdo->exec('PRAGMA synchronous = NORMAL');
            }
        } catch (\Throwable $e) {
            // Don't crash the app if nativephp connection isn't available yet
            // (e.g. during initial setup or artisan commands)
            Log::debug('Could not configure nativephp database: '.$e->getMessage());
        }
    }
}
