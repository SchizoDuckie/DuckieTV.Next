<div id="subtitles-dialog-root" class="subtitles-overlay">
    <!-- dialogs/subtitle.html -->
    <button type="button" class="close pull-right" onclick="Subtitles.close()">&times;</button>
    <h1 style="margin-top: 10px">
      <span>{{ __('COMMON/find-subtitle/lbl') }}</span>
      <img src="{{ asset('img/opensubtitles.png') }}" title="{{ __('SUBTITLEDIALOG/powered-by/tooltip') }}OpenSubtitles.org" style='float:right; margin-top: -10px; margin-right: 16px; opacity: 0.7'>
    </h1>
    <input type="text" id="subtitles-query" onkeyup="if(event.keyCode==13) Subtitles.search(this.value)" class="form-control" placeholder="{{ __('COMMON/type-your-search/lbl') }}" value="{{ $query ?? '' }}">
    <a href="#" data-sidepanel-show="{{ route('settings.show', ['section' => 'subtitles']) }}" onclick="Subtitles.close()" style="float:right"> 
        <i class="glyphicon glyphicon-cog"></i> <span>{{ __('SUBTITLEDIALOG/settings/glyph') }}</span>
    </a>
    <i id="subtitles-loading-icon" style="display:none" class="glyphicon glyphicon-refresh"></i>

    <table class="torrents table table-condensed white">
      <thead>
        <tr id="subtitles-searching-row" style="display:none">
          <td>
            <div class="loading-spinner" style="margin:0 auto; display:block; width:50px; height:50px; position:relative;">
                <div></div><div></div>
            </div>
            <p style='text-align:center; padding:10px;'><span >{{ __('COMMON/searching/lbl') }}</span> <span >{{ __('COMMON/searching-please-wait/lbl') }}</span>.</p>
          </td>
        </tr>
        <tr id="subtitles-no-results-row" style="display:none">
          <td>
            <p>{{ __('COMMON/no-results/lbl') }}</p>
            <strong id="subtitles-query-display"></strong>
          </td>
        </tr>
      </thead>
      <tbody id="subtitles-results-header" style="display:none">
        <tr>
          <th>
            <strong>S/E</strong>
          </th>
          <th>
            <strong>{{ __('COMMON/language/hdr') }}</strong>
          </th>
          <th>
            <strong>{{ __('COMMON/title/hdr') }}</strong>
          </th>
          <th>
            <strong>{{ __('SUBTITLEDIALOG/downloads/lbl') }}</strong>
          </th>
        </tr>
      </tbody>
      <tbody id="subtitles-results-body">
          {{-- Rows will be injected here via AJAX/Subtitles.js --}}
      </tbody>
    </table>
</div>
