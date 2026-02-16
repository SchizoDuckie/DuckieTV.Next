<div class="buttons">
    <form data-section="display">
        {{-- Top Sites --}}
        @if(settings('display.has_top_sites', false))
            <h2>
                <span title="{{ settings('display.top_sites') ? __('COMMON/enabled/lbl') : __('COMMON/disabled/lbl') }}">
                    <i class="glyphicon {{ settings('display.top_sites') ? 'glyphicon-ok' : 'glyphicon-remove' }}" id="icon_display_top_sites"></i>
                </span>
                {{ __('SETTINGS/DISPLAY/topsites/hdr') }}
            </h2>
            <p>{{ settings('display.top_sites') ? __('SETTINGS/DISPLAY/topsites-hide/desc') : __('SETTINGS/DISPLAY/topsites-show/desc') }}</p>
            <p><strong>{{ __('COMMON/current-setting/hdr') }}</strong> <span id="text_display_top_sites">{{ settings('display.top_sites') ? __('COMMON/enabled/lbl') : __('COMMON/disabled/lbl') }}</span></p>
            
            <input type="checkbox" name="display.top_sites" id="input_display_top_sites" {{ settings('display.top_sites') ? 'checked' : '' }} style="display:none" onchange="Settings.save('display')">
            <a href="#" onclick="document.getElementById('input_display_top_sites').click(); return false;" class="btn btn-{{ settings('display.top_sites') ? 'danger' : 'success' }}" id="btn_display_top_sites">
                <i class="glyphicon glyphicon-{{ settings('display.top_sites') ? 'ban-circle' : 'th' }}"></i> 
                {{ settings('display.top_sites') ? __('SETTINGS/DISPLAY/topsites-hide/btn') : __('SETTINGS/DISPLAY/topsites-show/btn') }}
            </a>

            @if(settings('display.top_sites'))
                <hr class="setting-divider">
                <h2>
                    <span title="{{ settings('display.top_sites_mode') == 'onhover' ? __('SETTINGS/DISPLAY/topsites-mode-hover/tooltip') : __('SETTINGS/DISPLAY/topsites-mode-click/tooltip') }}">
                        <i class="glyphicon {{ settings('display.top_sites_mode') == 'onhover' ? 'glyphicon-download-alt' : 'glyphicon-screenshot' }}"></i>
                    </span>
                    {{ __('SETTINGS/DISPLAY/topsites-activation/hdr') }}
                </h2>
                <p>{{ settings('display.top_sites_mode') == 'onhover' ? __('SETTINGS/DISPLAY/topsites-mode-hover/desc') : __('SETTINGS/DISPLAY/topsites-mode-click/desc') }}</p>
                
                <input type="hidden" name="display.top_sites_mode" id="input_display_top_sites_mode" value="{{ settings('display.top_sites_mode') }}" onchange="Settings.save('display')">
                <a href="#" onclick="const el = document.getElementById('input_display_top_sites_mode'); el.value = el.value === 'onhover' ? 'onclick' : 'onhover'; Settings.save('display'); return false;" class="btn btn-{{ settings('display.top_sites_mode') == 'onhover' ? 'info' : 'success' }}">
                    <i class="glyphicon glyphicon-{{ settings('display.top_sites_mode') == 'onhover' ? 'glyphicon-screenshot' : 'glyphicon-download-alt' }}"></i>
                    {{ settings('display.top_sites_mode') == 'onhover' ? __('SETTINGS/DISPLAY/topsites-mode-click/btn') : __('SETTINGS/DISPLAY/topsites-mode-hover/btn') }}
                </a>
            @endif
            <hr class="setting-divider">
        @endif

        {{-- Download Ratings --}}
        <h2>
            <span title="{{ settings('display.show_ratings', true) ? __('COMMON/enabled/lbl') : __('COMMON/disabled/lbl') }}">
                <i class="glyphicon {{ settings('display.show_ratings', true) ? 'glyphicon-ok' : 'glyphicon-remove' }}"></i>
            </span>
            {{ __('SETTINGS/DISPLAY/download-ratings/hdr') }}
        </h2>
        <p>{{ __('SETTINGS/DISPLAY/download-ratings/desc') }}</p>
        <p><strong>{{ __('COMMON/current-setting/hdr') }}</strong> {{ settings('display.show_ratings', true) ? __('COMMON/enabled/lbl') : __('COMMON/disabled/lbl') }}</p>
        
        <input type="checkbox" name="display.show_ratings" id="input_display_show_ratings" {{ settings('display.show_ratings', true) ? 'checked' : '' }} style="display:none" onchange="Settings.save('display')">
        <a href="#" onclick="document.getElementById('input_display_show_ratings').click(); return false;" class="btn btn-{{ settings('display.show_ratings', true) ? 'danger' : 'success' }}">
            <i class="glyphicon glyphicon-{{ settings('display.show_ratings', true) ? 'remove' : 'ok' }}"></i> 
            {{ settings('display.show_ratings', true) ? __('COMMON/disable/btn') : __('COMMON/enable/btn') }}
        </a>

        <hr class="setting-divider">

        {{-- Sidepanel Episodes Button Mode --}}
        <h2>
            <span title="{{ settings('display.not_watched_eps_btn', false) ? __('COMMON/enabled/lbl') : __('COMMON/disabled/lbl') }}">
                <i class="glyphicon {{ settings('display.not_watched_eps_btn', false) ? 'glyphicon-ok' : 'glyphicon-remove' }}"></i>
            </span>
            {{ __('SETTINGS/DISPLAY/notWatchedEpsBtn/hdr') }}
        </h2>
        <p>{{ __('SETTINGS/DISPLAY/notWatchedEpsBtn/desc') }}</p>
        <p><strong>{{ __('COMMON/current-setting/hdr') }}</strong> {{ settings('display.not_watched_eps_btn', false) ? __('COMMON/enabled/lbl') : __('COMMON/disabled/lbl') }}</p>

        <input type="checkbox" name="display.not_watched_eps_btn" id="input_display_not_watched_eps_btn" {{ settings('display.not_watched_eps_btn', false) ? 'checked' : '' }} style="display:none" onchange="Settings.save('display')">
        <a href="#" onclick="document.getElementById('input_display_not_watched_eps_btn').click(); return false;" class="btn btn-{{ settings('display.not_watched_eps_btn', false) ? 'danger' : 'success' }}">
            <i class="glyphicon glyphicon-{{ settings('display.not_watched_eps_btn', false) ? 'remove' : 'ok' }}"></i> 
            {{ settings('display.not_watched_eps_btn', false) ? __('COMMON/disable/btn') : __('COMMON/enable/btn') }}
        </a>

        <hr class="setting-divider">

        {{-- Sexy Poster Transitions --}}
        <h2>
            <span title="{{ settings('display.transitions', true) ? __('COMMON/enabled/lbl') : __('COMMON/disabled/lbl') }}">
                <i class="glyphicon {{ settings('display.transitions', true) ? 'glyphicon-ok' : 'glyphicon-remove' }}"></i>
            </span>
            {{ __('SETTINGS/DISPLAY/transitions/hdr') }}
        </h2>
        <p>{{ __('SETTINGS/DISPLAY/transitions/desc') }}</p>
        <p><strong>{{ __('COMMON/current-setting/hdr') }}</strong> {{ settings('display.transitions', true) ? __('COMMON/enabled/lbl') : __('COMMON/disabled/lbl') }}</p>

        <input type="checkbox" name="display.transitions" id="input_display_transitions" {{ settings('display.transitions', true) ? 'checked' : '' }} style="display:none" onchange="Settings.save('display')">
        <a href="#" onclick="document.getElementById('input_display_transitions').click(); return false;" class="btn btn-{{ settings('display.transitions', true) ? 'danger' : 'success' }}">
            <i class="glyphicon glyphicon-{{ settings('display.transitions', true) ? 'remove' : 'ok' }}"></i> 
            {{ settings('display.transitions', true) ? __('COMMON/disable/btn') : __('COMMON/enable/btn') }}
        </a>

        <hr class="setting-divider">

        {{-- Background Opacity --}}
        <h2>{{ __('SETTINGS/DISPLAY/background-opacity/hdr') }}</h2>
        <ul class="list-unstyled btns">
            <li>
                <p>{{ __('SETTINGS/DISPLAY/background-opacity/desc') }}</p> <span>{{ number_format(settings('display.bg_opacity', 0.8) * 100, 0) }}%</span>
                <input type="range" name="display.bg_opacity" value="{{ settings('display.bg_opacity', 0.8) }}" min="0" max="1" step="0.05" onchange="Settings.save('display')" />
                <strong style="float:left">0%</strong> <strong style='float:right'>100%</strong>
            </li>
        </ul>
        <br>

        {{-- Notifications --}}
        @if(settings('display.has_notifications', true))
            <hr class="setting-divider">
            <h2>
                <span title="{{ settings('notifications.enabled', true) ? __('COMMON/enabled/lbl') : __('COMMON/disabled/lbl') }}">
                    <i class="glyphicon {{ settings('notifications.enabled', true) ? 'glyphicon-ok' : 'glyphicon-remove' }}"></i>
                </span>
                {{ __('SETTINGS/DISPLAY/notifications/hdr') }}
            </h2>
            <p>{{ __('SETTINGS/DISPLAY/notifications/desc') }}</p>
            <p><strong>{{ __('COMMON/current-setting/hdr') }}</strong> {{ settings('notifications.enabled', true) ? __('COMMON/enabled/lbl') : __('COMMON/disabled/lbl') }}</p>

            <input type="checkbox" name="notifications.enabled" id="input_notifications_enabled" {{ settings('notifications.enabled', true) ? 'checked' : '' }} style="display:none" onchange="Settings.save('display')">
            <a href="#" onclick="document.getElementById('input_notifications_enabled').click(); return false;" class="btn btn-{{ settings('notifications.enabled', true) ? 'info' : 'success' }}">
                <i class="glyphicon glyphicon-{{ settings('notifications.enabled', true) ? 'remove' : 'ok' }}"></i> 
                {{ settings('notifications.enabled', true) ? __('COMMON/disable/btn') : __('COMMON/enable/btn') }}
            </a>
        @endif

        <hr class="setting-divider">

        {{-- Mixed case Font --}}
        <h2>
            <span title="{{ settings('display.mixed_case', false) ? __('COMMON/enabled/lbl') : __('COMMON/disabled/lbl') }}">
                <i class="glyphicon {{ settings('display.mixed_case', false) ? 'glyphicon-ok' : 'glyphicon-remove' }}"></i>
            </span>
            {{ __('SETTINGS/DISPLAY/mixedcase/hdr') }}
        </h2>
        <p>{{ __('SETTINGS/DISPLAY/mixedcase/desc') }}</p>
        <p><strong>{{ __('COMMON/current-setting/hdr') }}</strong> {{ settings('display.mixed_case', false) ? __('COMMON/enabled/lbl') : __('COMMON/disabled/lbl') }}</p>

        <input type="checkbox" name="display.mixed_case" id="input_display_mixed_case" {{ settings('display.mixed_case', false) ? 'checked' : '' }} style="display:none" onchange="Settings.save('display')">
        <a href="#" onclick="document.getElementById('input_display_mixed_case').click(); return false;" class="btn btn-{{ settings('display.mixed_case', false) ? 'info' : 'success' }}">
            <i class="glyphicon glyphicon-{{ settings('display.mixed_case', false) ? 'remove' : 'ok' }}"></i> 
            {{ settings('display.mixed_case', false) ? __('COMMON/disable/btn') : __('COMMON/enable/btn') }}
        </a>

        <hr class="setting-divider">
        
        {{-- Permanent Cheatmode --}}
        <h2>
            <span title="{{ settings('kc.always', false) ? __('COMMON/enabled/lbl') : __('COMMON/disabled/lbl') }}">
                <i class="glyphicon {{ settings('kc.always', false) ? 'glyphicon-ok' : 'glyphicon-remove' }}"></i>
            </span>
            {{ __('SETTINGS/DISPLAY/cheatmode/hdr') }}
        </h2>
        <p>{{ __('SETTINGS/DISPLAY/cheatmode/desc') }}</p>
        <p><strong>{{ __('COMMON/current-setting/hdr') }}</strong> {{ settings('kc.always', false) ? __('COMMON/enabled/lbl') : __('COMMON/disabled/lbl') }}</p>

        <input type="checkbox" name="kc.always" id="input_kc_always" {{ settings('kc.always', false) ? 'checked' : '' }} style="display:none" onchange="Settings.save('display')">
        <a href="#" onclick="document.getElementById('input_kc_always').click(); return false;" class="btn btn-{{ settings('kc.always', false) ? 'info' : 'success' }}">
            <i class="glyphicon glyphicon-{{ settings('kc.always', false) ? 'remove' : 'ok' }}"></i> 
            {{ settings('kc.always', false) ? __('COMMON/disable/btn') : __('COMMON/enable/btn') }}
        </a>
    </form>
</div>
