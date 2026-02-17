/**
 * Window decorations for DuckieTV standalone on NativePHP.
 *
 * NativePHP handles position persistence via rememberState(),
 * so this only manages the custom titlebar button interactions.
 */
(function () {
    'use strict';

    var winState = 'normal';
    var maximize, unmaximize;

    function emitWinState(state) {
        window.dispatchEvent(new CustomEvent('winstate', { detail: state }));
    }

    // Get CSRF token from meta tag for POST requests
    function getCsrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function nativeWindowAction(action) {
        return fetch('/native/window/' + action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        }).catch(function (err) {
            console.error('Window action failed:', action, err);
        });
    }

    function init() {
        document.body.classList.add('standalone');

        // Close
        const closeBtn = document.getElementById('close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                nativeWindowAction('close');
            });
        }

        // Minimize
        const minBtn = document.getElementById('minimize');
        if (minBtn) {
            minBtn.addEventListener('click', function () {
                nativeWindowAction('minimize');
            });
        }

        // Maximize / Unmaximize toggle
        maximize = document.getElementById('maximize');
        unmaximize = document.getElementById('unmaximize');

        if (maximize) {
            maximize.addEventListener('click', function () {
                maximize.style.display = 'none';
                if (unmaximize) unmaximize.style.display = 'inline-block';
                nativeWindowAction('maximize');
                winState = 'maximized';
                emitWinState(winState);
            });
        }

        if (unmaximize) {
            unmaximize.addEventListener('click', function () {
                unmaximize.style.display = 'none';
                if (maximize) maximize.style.display = 'inline-block';
                nativeWindowAction('unmaximize');
                winState = 'normal';
                emitWinState(winState);
            });
        }
    }

    if (document.readyState === 'loading') {
        window.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
