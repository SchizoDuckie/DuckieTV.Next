<?php

namespace App\Http\Controllers;

use App\Http\Requests\Settings\ShowSettingsRequest;
use App\Services\TorrentClientService;
use App\Services\TranslationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    protected $translationService;

    protected $torrentClientService;

    protected $backupService;

    public function __construct(
        TranslationService $translationService,
        TorrentClientService $torrentClientService,
        \App\Services\BackupService $backupService
    ) {
        $this->translationService = $translationService;
        $this->torrentClientService = $torrentClientService;
        $this->backupService = $backupService;
    }

    /**
     * Display the settings menu (Left Panel).
     */
    public function index(Request $request): View
    {
        if (! $request->ajax()) {
            return view('layouts.app', [
                'title' => 'Settings',
                'sidePanelUrl' => route('settings.index')
            ]);
        }

        return view('settings.index', [
            'locales' => $this->translationService->getAvailableLocales(),
            'supportedClients' => $this->getSupportedClients(),
        ]);
    }

    private function getSupportedClients(): array
    {
        return collect($this->torrentClientService->getAvailableClients())->mapWithKeys(function ($clientName) {
            $client = $this->torrentClientService->getClient($clientName);
            $presenter = new \App\Presenters\TorrentClientPresenter($client);

            return [
                $clientName => [
                    'id' => $presenter->getId(),
                    'name' => $presenter->getName(),
                    'icon' => $presenter->getIcon(),
                    'css_class' => $presenter->getCssClass(),
                ],
            ];
        })->toArray();
    }

    /**
     * Display a specific settings section (Right Panel).
     */
    public function show(ShowSettingsRequest $request, string $section): View
    {
        // Validation is handled by ShowSettingsRequest

        $data = [];
        if (in_array($section, ['language', 'subtitles'])) {
            $data['locales'] = $this->translationService->getAvailableLocales();
        }

        if ($section === 'torrent') {
            $data['supportedClients'] = $this->getSupportedClients();
        }

        $data['section'] = $section;

        return view("settings.$section", $data);
    }

    /**
     * Update settings for a specific section.
     */
    public function update(Request $request, string $section)
    {
        $allowed = [
            'display' => \App\Http\Requests\Settings\UpdateDisplaySettingsRequest::class,
            'language' => \App\Http\Requests\Settings\UpdateLanguageSettingsRequest::class,
            'backup' => \App\Http\Requests\Settings\UpdateBackupSettingsRequest::class,
            'calendar' => \App\Http\Requests\Settings\UpdateCalendarSettingsRequest::class,
            'torrent-search' => \App\Http\Requests\Settings\UpdateTorrentSearchSettingsRequest::class,
            'subtitles' => \App\Http\Requests\Settings\UpdateSubtitlesSettingsRequest::class,
            'torrent' => \App\Http\Requests\Settings\UpdateTorrentSettingsRequest::class,
            'auto-download' => \App\Http\Requests\Settings\UpdateAutoDownloadSettingsRequest::class,
            'trakttv' => \App\Http\Requests\Settings\UpdateTraktSettingsRequest::class,
        ];

        if (! array_key_exists($section, $allowed)) {
            abort(404);
        }

        // Expand dot-notated keys (e.g. "torrenting.client") into nested arrays
        // because Laravel validation expects nesting for dot-notation rules.
        $data = $request->all(); // Works for JSON and form data
        $expanded = [];
        foreach ($data as $key => $value) {
            if (str_contains($key, '.')) {
                data_set($expanded, $key, $value);
            } else {
                $expanded[$key] = $value;
            }
        }
        $request->merge($expanded);

        // Validate using the specific FormRequest rules but manually
        $formRequest = app($allowed[$section]);
        $rules = $formRequest->rules();

        $validator = \Illuminate\Support\Facades\Validator::make($expanded, $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        // Ensure booleans are included even if missing from request (unchecked checkboxes)
        // We use a heuristic: only default to false if other fields with the same prefix are present.
        // This avoids resetting unrelated settings during partial updates (e.g. toggling a single global switch).
        $rawData = $request->all();
        foreach ($rules as $key => $rule) {
            if ($rule === 'boolean' || (is_array($rule) && in_array('boolean', $rule))) {
                if (! \Illuminate\Support\Arr::has($validated, $key)) {
                    $prefix = str_contains($key, '.') ? explode('.', $key)[0] : $key;
                    // Check if there are other fields in the same configuration group present
                    $otherFieldsInGroup = collect($rawData)->keys()
                        ->filter(fn ($k) => str_starts_with($k, $prefix.'.'))
                        ->count();

                    if ($otherFieldsInGroup > 0) {
                        \Illuminate\Support\Arr::set($validated, $key, false);
                    }
                }
            }
        }

        // validated() returns nested arrays corresponding to dot rules.
        // We need to flatten them back to dot notation for storage.
        $flattened = \Illuminate\Support\Arr::dot($validated);

        foreach ($flattened as $key => $value) {
            settings($key, $value);
        }

        $res = ['success' => true, 'message' => 'Settings saved successfully.'];

        if ($request->has('test') && $section === 'torrent') {
            $client = $this->torrentClientService->getActiveClient();
            if ($client) {
                // Refresh config from settings store before testing
                $client->readConfig();
                try {
                    $connected = $client->connect();
                    $res['connection_success'] = $connected;
                    if ($connected) {
                        $res['message'] = "Connected to {$client->getName()} successfully!";
                    } else {
                        $res['connection_error'] = "Failed to connect to {$client->getName()} for unknown reasons. Check your settings and server status.";
                    }
                } catch (\Exception $e) {
                    $res['connection_success'] = false;
                    $res['connection_error'] = "Connection to {$client->getName()} failed: ".$e->getMessage();
                }
            }
        }

        return response()->json($res);
    }

    /**
     * Restore backup from file.
     */
    public function restore(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'backup_file' => 'required|file|mimetypes:application/json,text/plain|max:10240', // 10MB max
            'wipe' => 'sometimes|boolean',
        ]);

        try {
            $file = $request->file('backup_file');
            $json = file_get_contents($file->getRealPath());
            $data = json_decode($json, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid JSON file: '.json_last_error_msg(),
                ], 422);
            }

            // Optional: Wipe database logic here if requested
            if ($request->boolean('wipe')) {
                // TODO: Implement wipe logic (Series::truncate(), Episode::truncate(), etc.)
                // For now, we just proceed with restore which upserts/overwrites.
            }

            // Delegate to BackupService via Job for async processing
            \App\Jobs\RestoreBackupJob::dispatch($data);

            return response()->json([
                'success' => true,
                'message' => 'Restore started in background. Please wait...',
                'status' => 'started',
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Restore failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Restore failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get the current progress of the restore job.
     */
    public function restoreProgress()
    {
        // Default to idle if no progress is found
        $progress = \Illuminate\Support\Facades\Cache::get('backup_progress', [
            'percent' => 0,
            'status' => 'idle',
            'message' => 'Waiting for start...',
            'logs' => [],
        ]);

        return response()->json($progress);
    }

    /**
     * Cancel the current running restore batch.
     */
    public function cancelRestore()
    {
        $progress = \Illuminate\Support\Facades\Cache::get('backup_progress');

        if (isset($progress['batch_id'])) {
            $batchId = $progress['batch_id'];
            $batch = \Illuminate\Support\Facades\Bus::findBatch($batchId);

            if ($batch) {
                $batch->cancel();

                $progress['status'] = 'cancelled';
                $progress['message'] = 'Cancellation requested...';
                $progress['logs'][] = date('H:i:s').' - User requested cancellation.';
                \Illuminate\Support\Facades\Cache::put('backup_progress', $progress);

                return response()->json(['success' => true, 'message' => 'Cancellation requested.']);
            }
        }

        return response()->json(['success' => false, 'message' => 'No active batch found to cancel.'], 404);
    }
}
