<?php

namespace App\Console\Commands\DuckieTV;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use ZipArchive;

class StartCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'duckietv:start {--no-queue} {--migrate} {--seed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start DuckieTV (automatically detects WSL and launches Windows native using bundled PHP)';

    /**
     * WSL-accessible path to the Windows-native database copy (for copy-back on exit).
     */
    protected ?string $wslDbPath = null;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isWsl = $this->isWsl();

        if ($isWsl) {
            $this->info('WSL detected. Preparing Windows-native startup...');

            return $this->launchInWindows();
        }

        if ($this->option('migrate')) {
            $this->info('Running migrations...');
            $this->call('native:migrate', ['--seed' => $this->option('seed')]);
        } elseif ($this->option('seed')) {
            $this->info('Seeding database...');
            $this->call('native:seed');
        }

        $this->info('Launching NativePHP...');

        return $this->call('native:run', [
            '--no-queue' => $this->option('no-queue'),
        ]);
    }

    /**
     * Detect if running inside WSL.
     */
    protected function isWsl(): bool
    {
        if (PHP_OS_FAMILY !== 'Linux') {
            return false;
        }

        $uname = @shell_exec('uname -a');

        return $uname && str_contains(strtolower($uname), 'microsoft');
    }

    /**
     * Launch the app using a Windows-native PHP binary.
     */
    protected function launchInWindows()
    {
        // 1. Resolve the current path to the best possible Windows path
        $winPath = $this->resolveWinPath();
        $this->comment("Project Windows path: {$winPath}");

        // 2. Try to find php.exe on the Windows side
        $phpPath = trim(shell_exec('powershell.exe -Command "Get-Command php.exe | Select-Object -ExpandProperty Source" 2>/dev/null'));

        if (! $phpPath) {
            $this->warn('Could not find php.exe in Windows PATH. Looking for bundled binary...');
            $phpPath = $this->getBundledPhpPath($winPath);
        }

        if (! $phpPath) {
            $this->error('Could not find or extract a Windows PHP binary.');

            return 1;
        }

        $this->info("Using Windows PHP: {$phpPath}");

        // 3. Copy SQLite database to a Windows-native NTFS path.
        // SQLite file locking does NOT work over WSL2's 9P filesystem (Z: drive / UNC paths).
        // The database must live on a real NTFS volume for proper locking.
        $this->ensureDatabaseExists();
        $winDbPath = $this->copyDatabaseToWindows();

        if (! $winDbPath) {
            $this->error('Failed to set up Windows-native database path.');

            return 1;
        }

        $this->info("Database path: {$winDbPath}");

        $envOverrides = "\$env:DB_CONNECTION='sqlite'; \$env:DB_DATABASE='{$winDbPath}';";

        if ($this->option('migrate')) {
            $this->info('Running migrations in Windows...');
            $migrateCmd = "{$envOverrides} cd '{$winPath}'; & '{$phpPath}' artisan native:migrate".($this->option('seed') ? ' --seed' : '');
            $this->executeWindowsCommand($migrateCmd);
        } elseif ($this->option('seed')) {
            $this->info('Seeding database in Windows...');
            $seedCmd = "{$envOverrides} cd '{$winPath}'; & '{$phpPath}' artisan native:seed";
            $this->executeWindowsCommand($seedCmd);
        }

        // Build the command to run artisan native:run in the Windows environment
        $this->info('Launching DuckieTV in Windows...');
        $runCmd = "{$envOverrides} cd '{$winPath}'; & '{$phpPath}' artisan native:run".($this->option('no-queue') ? ' --no-queue' : '');

        $result = $this->executeWindowsCommand($runCmd, true);

        // Copy database back to WSL after the app exits so changes persist
        $this->copyDatabaseFromWindows($winDbPath);

        return $result;
    }

    /**
     * Resolve the current WSL path to a Windows path, preferring drive mappings.
     */
    protected function resolveWinPath(): string
    {
        $uncPath = trim(shell_exec('wslpath -w .'));

        // Check if Z:\ maps to / and if we are in a WSL home directory
        // In the user's case, Z: maps to /
        $currentPath = getcwd();
        if (str_starts_with($currentPath, '/home/')) {
            $driveZ = trim(shell_exec('powershell.exe -Command "if (Test-Path Z:) { Get-PSDrive Z | Select-Object -ExpandProperty Root }" 2>/dev/null'));
            if ($driveZ === 'Z:\\') {
                return 'Z:'.str_replace('/', '\\', $currentPath);
            }
        }

        return $uncPath;
    }

    /**
     * Ensure the nativephp.sqlite database exists.
     */
    protected function ensureDatabaseExists(): void
    {
        $dbPath = database_path('nativephp.sqlite');
        if (! file_exists($dbPath)) {
            $this->info('Creating nativephp.sqlite database...');
            touch($dbPath);
        }
    }

    /**
     * Copy the SQLite database to a Windows-native NTFS path.
     *
     * Returns the Windows path to the copied database, or null on failure.
     * We use %LOCALAPPDATA%\DuckieTV\ so the DB lives on a real NTFS volume
     * where SQLite file locking works correctly.
     */
    protected function copyDatabaseToWindows(): ?string
    {
        // Get the Windows LOCALAPPDATA path
        $localAppData = trim(shell_exec('powershell.exe -Command "echo \\$env:LOCALAPPDATA" 2>/dev/null'));

        if (! $localAppData) {
            $this->warn('Could not resolve %LOCALAPPDATA%. Falling back to Z: drive path.');
            $winPath = $this->resolveWinPath();

            return $winPath.'\\database\\nativephp.sqlite';
        }

        $winDbDir = $localAppData.'\\DuckieTV.Next';
        $winDbPath = $winDbDir.'\\nativephp.sqlite';

        // Ensure the directory exists on Windows
        $this->executeWindowsCommand("New-Item -ItemType Directory -Force -Path '{$winDbDir}' | Out-Null");

        // Convert the Windows LOCALAPPDATA path to a WSL-accessible path
        // e.g. C:\Users\foo\AppData\Local -> /mnt/c/Users/foo/AppData/Local
        $wslDbDir = trim(shell_exec("wslpath -u '{$winDbDir}' 2>/dev/null"));

        if (! $wslDbDir || ! is_dir($wslDbDir)) {
            $this->warn("Cannot access Windows path from WSL ({$wslDbDir}). Falling back to Z: drive path.");
            $winPath = $this->resolveWinPath();

            return $winPath.'\\database\\nativephp.sqlite';
        }

        $wslDbPath = $wslDbDir.'/nativephp.sqlite';
        $sourceDb = database_path('nativephp.sqlite');

        // Copy the database file (and WAL/SHM files if they exist)
        copy($sourceDb, $wslDbPath);
        $this->comment("Copied database to: {$winDbPath}");

        foreach (['-wal', '-shm'] as $suffix) {
            $src = $sourceDb.$suffix;
            if (file_exists($src)) {
                copy($src, $wslDbPath.$suffix);
            }
        }

        // Store the WSL path for later copy-back
        $this->wslDbPath = $wslDbPath;

        return $winDbPath;
    }

    /**
     * Copy the database back from Windows-native path to the WSL project.
     */
    protected function copyDatabaseFromWindows(string $winDbPath): void
    {
        $wslDbPath = $this->wslDbPath ?? null;

        if (! $wslDbPath || ! file_exists($wslDbPath)) {
            return;
        }

        $destDb = database_path('nativephp.sqlite');
        copy($wslDbPath, $destDb);
        $this->comment('Copied database back to WSL project.');

        foreach (['-wal', '-shm'] as $suffix) {
            $src = $wslDbPath.$suffix;
            if (file_exists($src)) {
                copy($src, $destDb.$suffix);
            } else {
                // Clean up stale WAL/SHM files if they don't exist on the Windows side
                $dest = $destDb.$suffix;
                if (file_exists($dest)) {
                    unlink($dest);
                }
            }
        }
    }

    /**
     * Locate or extract the bundled Windows PHP binary.
     */
    protected function getBundledPhpPath(string $winPath): ?string
    {
        $storagePath = storage_path('nativephp/bin-win');
        $phpExeLinux = "{$storagePath}/php.exe";

        if (str_starts_with($winPath, 'Z:')) {
            $phpExeWin = $winPath.'\\storage\\nativephp\\bin-win\\php.exe';
        } else {
            $phpExeWin = $winPath.'\storage\nativephp\bin-win\php.exe';
        }

        if (file_exists($phpExeLinux)) {
            return $phpExeWin;
        }

        // Try to find the zip
        $zipPath = base_path('vendor/nativephp/php-bin/bin/win/x64/php-8.4.zip');
        if (! file_exists($zipPath)) {
            $zipPath = base_path('vendor/nativephp/php-bin/bin/win/x64/php-8.3.zip');
        }

        if (! file_exists($zipPath)) {
            $this->error('Bundled PHP zip not found in vendor.');

            return null;
        }

        $this->info('Extracting bundled Windows PHP binary...');
        if (! is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath) === true) {
            $zip->extractTo($storagePath);
            $zip->close();

            if (file_exists($phpExeLinux)) {
                return $phpExeWin;
            }
        }

        $this->error('Failed to extract bundled PHP binary.');

        return null;
    }

    /**
     * Execute a command in Windows via PowerShell.
     */
    protected function executeWindowsCommand(string $powershellCommand, bool $isInteractive = false)
    {
        $command = [
            'powershell.exe',
            '-Command',
            $powershellCommand,
        ];

        $process = new Process($command);
        $process->setTimeout(null); // Disable timeout for all Windows operations

        if ($isInteractive) {
            $process->setTty(true);
        }

        return $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });
    }
}
