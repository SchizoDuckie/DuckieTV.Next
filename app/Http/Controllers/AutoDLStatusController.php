<?php

namespace App\Http\Controllers;

use App\Services\AutoDownloadService;
use Illuminate\Http\Request;

class AutoDLStatusController extends Controller
{
    protected AutoDownloadService $autoDownload;

    public function __construct(AutoDownloadService $autoDownload)
    {
        $this->autoDownload = $autoDownload;
    }

    /**
     * Display the auto-download status.
     */
    public function index(Request $request)
    {
        $activityList = $this->autoDownload->getActivityList();
        
        if ($request->ajax()) {
            return view('autodlstatus.index', [
                'activityList' => $activityList,
                'status' => $this->autoDownload->isEnabled() ? 'active' : 'inactive',
                'lastRun' => $this->autoDownload->getLastRun(),
            ]);
        }

        return view('layouts.app', [
            'title' => 'Auto-Download Status',
            'sidePanelUrl' => route('autodlstatus.index'),
            'activityList' => $activityList
        ]);
    }
}
