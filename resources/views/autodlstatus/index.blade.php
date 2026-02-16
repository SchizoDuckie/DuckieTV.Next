<div class="autodlstatus-container" style="padding: 20px;">
    <h1>{{ __('COMMON/auto-download-status/hdr') }}</h1>
    
    <div class="alert alert-info">
        <i class="glyphicon glyphicon-info-sign"></i> 
        {{ __('AUTODLSTATUSCTRLjs/no-activity/lbl') }}
        {{ __('AUTODLSTATUSCTRLjs/not-using/lbl') }}
    </div>

    <!-- Placeholder for actual status content -->
    <div class="status-content">
        <h3>{{ __('AUTODLSTATUS/last-run/hdr') }}: <span id="last-run">Never</span></h3>
        <h3>{{ __('AUTODLSTATUS/next-run/hdr') }}: <span id="next-run">Unknown</span></h3>
    </div>
</div>
