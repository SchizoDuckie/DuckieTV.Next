<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class BuildWindowsDistribution extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'duckietv:build-win';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build the DuckieTV.Next Windows binary for distribution using NativePHP';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Windows build for DuckieTV.Next...');
  
        // Ensure we are in the right directory or environment
        // Running native:build win
        $this->info('Running php artisan native:build win...');
        
        // We use system call or Process to run the command to ensure output is streamed or handled
        // Simple call:
        $this->call('native:build', ['os' => 'win']);

        $this->info('Build command finished. Checking output...');

        // Check for dist/ folder
        if (is_dir(base_path('dist'))) {
            $this->info('Build output located in dist/ folder.');
            $this->info('You can find the setup executable in dist/win-unpacked or dist/ (depending on electron-builder config).');
        } else {
            $this->error('dist/ folder not found. Build might have failed.');
        }

        return Command::SUCCESS;
    }
}
