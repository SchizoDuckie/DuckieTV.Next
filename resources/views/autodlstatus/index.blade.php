<div class="autodlstatus-container" style="padding: 20px;">
    <style>
        .autodlstatus-container {
            color: #ddd;
        }
        .autodlstatus-container .table {
            background-color: rgba(0, 0, 0, 0.2);
            color: #ddd;
        }
        .autodlstatus-container .table > thead > tr > th,
        .autodlstatus-container .table > tbody > tr > th,
        .autodlstatus-container .table > tfoot > tr > th,
        .autodlstatus-container .table > thead > tr > td,
        .autodlstatus-container .table > tbody > tr > td,
        .autodlstatus-container .table > tfoot > tr > td {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        .autodlstatus-container .table-striped > tbody > tr:nth-of-type(odd) {
            background-color: rgba(255, 255, 255, 0.05);
        }
        .autodlstatus-container .table-hover > tbody > tr:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        .autodlstatus-container small.text-muted {
            color: #999;
        }
        /* Status colors */
        .status-success { color: #5cb85c; }
        .status-info { color: #5bc0de; }
        .status-warning { color: #f0ad4e; }
        .status-danger { color: #d9534f; }
    </style>

    <h1>{{ __('COMMON/auto-download-status/hdr') }}</h1>
    
    <div class="status-content" style="margin-bottom: 20px;">
        <h3>
            {{ __('AUTODLSTATUS/last-run/hdr') }}: 
            <span id="last-run">{{ $lastRun ? \Carbon\Carbon::parse($lastRun)->diffForHumans() : 'Never' }}</span>
        </h3>
        
        @if($status === 'active')
            <div class="alert alert-success" style="background-color: rgba(92, 184, 92, 0.2); border-color: #5cb85c; color: #ddd;">
                <i class="glyphicon glyphicon-ok"></i> {{ __('AUTODLSTATUSCTRLjs/active/lbl') }}
            </div>
        @else
            <div class="alert alert-warning" style="background-color: rgba(240, 173, 78, 0.2); border-color: #f0ad4e; color: #ddd;">
                <i class="glyphicon glyphicon-pause"></i> {{ __('AUTODLSTATUSCTRLjs/inactive/lbl') }}
            </div>
        @endif
    </div>

    <table class="table table-striped table-hover table-condensed">
        <thead>
            <tr>
                <th>{{ __('AUTODLSTATUS/last-run/hdr') }}</th>
                <th>{{ __('COMMON/title/hdr') }}</th>
                <th>{{ __('COMMON/episodes/lbl') }}</th>
                <th>{{ __('AUTODLSTATUS/search-engine/hdr') }}</th>
                <th>{{ __('COMMON/status/hdr') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($activityList as $activity)
                <tr>
                    <td>{{ \Carbon\Carbon::createFromTimestamp($activity->timestamp)->format('Y-m-d H:i:s') }}</td>
                    <td><strong>{{ $activity->serie_name }}</strong></td>
                    <td>{{ $activity->episode_formatted }}</td>
                    <td>
                        {{ $activity->search }} 
                        <small class="text-muted">{{ $activity->search_provider }} {{ $activity->search_extra }}</small>
                    </td>
                    <td>
                        @switch($activity->status)
                            @case(0) <span class="text-muted">AutoDL Disabled</span> @break
                            @case(1) <span class="status-warning">Nothing Found</span> @break
                            @case(2) <span class="text-muted">Filtered Out</span> @break
                            @case(3) <span class="status-warning">Not Enough Seeders</span> @break
                            @case(4) <span class="status-info">On Air Delay</span> @break
                            @case(5) <span class="text-muted">Searching...</span> @break
                            @case(6) <span class="status-success"><strong>Has Magnet</strong></span> @break
                            @case(7) <span class="status-danger">TVDB ID Missing</span> @break
                            @case(8) <span class="status-success">Download Initiated</span> @break
                            @case(9) <span class="status-success">Download Success</span> @break
                            @default {{ $activity->status }}
                        @endswitch
                        @if($activity->extra)
                        <small class="text-muted">{{ $activity->extra }}</small>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">{{ __('AUTODLSTATUSCTRLjs/no-activity/lbl') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
