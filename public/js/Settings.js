
window.Settings = {
    save: function (section) {
        const form = document.querySelector(`form[data-section="${section}"]`);
        if (!form) {
            console.error(`Form for section ${section} not found.`);
            return;
        }

        const formData = new FormData(form);
        const data = {};

        // Convert FormData to JSON object, handling booleans and checkboxes
        formData.forEach((value, key) => {
            // Check if it's a checkbox
            const input = form.querySelector(`[name="${key}"]`);
            if (input && input.type === 'checkbox') {
                data[key] = input.checked;
            } else {
                data[key] = value;
            }
        });

        // specific fix for unchecked checkboxes not being in FormData
        form.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            if (!data.hasOwnProperty(checkbox.name)) {
                data[checkbox.name] = checkbox.checked;
            }
        });

        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        return fetch(`/settings/${section}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => Promise.reject(err));
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    const btn = form.querySelector('.btn-save');
                    if (btn) {
                        const originalText = btn.innerHTML;
                        btn.innerHTML = '<i class="glyphicon glyphicon-ok"></i> Saved!';
                        btn.classList.add('btn-success');
                        setTimeout(() => {
                            btn.innerHTML = originalText;
                            btn.classList.remove('btn-success');
                        }, 2000);
                    }
                    return data;
                }
            })
            .catch(error => {
                console.error('Error saving settings:', error);
                alert('Error saving settings: ' + (error.message || 'Unknown error'));
            });
    },

    test: function (section) {
        const form = document.querySelector(`form[data-section="${section}"]`);
        if (!form) return;

        const btn = form.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="glyphicon glyphicon-refresh spin"></i> Testing...';
        btn.disabled = true;

        const formData = new FormData(form);
        const data = { test: 1 };

        formData.forEach((value, key) => {
            const input = form.querySelector(`[name="${key}"]`);
            if (input && input.type === 'checkbox') {
                data[key] = input.checked;
            } else {
                data[key] = value;
            }
        });

        // Ensure checkboxes are included if unchecked
        form.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            if (!data.hasOwnProperty(checkbox.name)) {
                data[checkbox.name] = checkbox.checked;
            }
        });

        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch(`/settings/${section}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => Promise.reject(err));
                }
                return response.json();
            })
            .then(data => {
                btn.innerHTML = originalText;
                btn.disabled = false;

                const statusEl = document.getElementById('connection-status');
                if (statusEl) {
                    // Hide all states first
                    statusEl.querySelectorAll('p').forEach(p => p.style.display = 'none');

                    if (data.connection_success) {
                        const successEl = statusEl.querySelector('.status-connected');
                        if (successEl) {
                            successEl.style.display = 'block';
                            const infoEl = successEl.querySelector('.server-info');
                            if (infoEl) infoEl.textContent = `${data.server || ''}:${data.port || ''}`;
                        }
                    } else {
                        const errorEl = statusEl.querySelector('.status-error');
                        if (errorEl) {
                            errorEl.style.display = 'block';
                            const msgEl = errorEl.querySelector('.error-message');
                            if (msgEl) msgEl.textContent = data.connection_error || 'Unknown error';
                        }
                    }
                } else {
                    alert(data.connection_success ? 'Connected!' : 'Connection Failed: ' + data.connection_error);
                }
            })
            .catch(error => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                console.error('Error testing settings:', error);
                alert('Error testing: ' + (error.message || 'Unknown error'));
            });
    }
};

// Global functions for Torrent Settings (accessed via inline onclick)
window.updateTorrentSetting = function (key, value) {
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    return fetch('/settings/torrent', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ [key]: value })
    }).then(response => {
        if (!response.ok) {
            return response.json().then(err => Promise.reject(err));
        }
        return response.json();
    });
}

window.toggleTorrent = function () {
    console.error("Use toggleTorrentSetting(newValue) instead.");
};

window.toggleTorrentSetting = function (key, newValue) {
    window.updateTorrentSetting(key, newValue).then(() => {
        window.location.reload();
    }).catch(error => {
        console.error('Failed to update setting', error);
        alert('Failed to update setting: ' + (error.message || 'Unknown error'));
    });
}

window.setTorrentClient = function (clientKey) {
    window.updateTorrentSetting('torrenting.client', clientKey).then(() => {
        // Update UI without reload
        document.querySelectorAll('.choose-client').forEach(btn => {
            btn.classList.remove('btn-success');
            if (btn.getAttribute('data-client-key') === clientKey) {
                btn.classList.add('btn-success');
            }
        });

        // Trigger sidebar refresh via SidePanel
        if (window.SidePanel) {
            window.SidePanel.update('/settings');
        } else {
            console.error('SidePanel not initialized, falling back to reload');
            window.location.reload();
        }
    }).catch(error => {
        console.error('Failed to set torrent client', error);
        alert('Failed to set torrent client: ' + (error.message || 'Unknown error'));
    });
};

window.updateSidebarClient = function (newClientName) {
    // Deprecated: now handled by SidePanel.update('/settings') in setTorrentClient
    if (window.SidePanel) {
        window.SidePanel.update('/settings');
    }
}

// Global functions for Torrent Search Settings (moved from blade template)
window.setSearchProvider = function (provider) {
    window.updateTorrentSetting('torrenting.searchprovider', provider).then(() => {
        // Manually update UI to avoid reloading panel (and cache issues)
        const container = document.querySelector('[data-section="torrent-search"]');
        if (container) {
            const buttons = container.querySelectorAll('a[onclick^="setSearchProvider"]');
            buttons.forEach(btn => {
                const isMatch = btn.getAttribute('onclick').includes(`'${provider}'`);

                // Update class
                if (isMatch) {
                    btn.classList.add('btn-success');
                } else {
                    btn.classList.remove('btn-success');
                }

                // Update Icon
                const existingIcon = btn.querySelector('.glyphicon');
                if (existingIcon) existingIcon.remove();

                if (isMatch) {
                    const icon = document.createElement('i');
                    icon.className = 'glyphicon glyphicon-ok';
                    btn.insertBefore(icon, btn.firstChild);
                    // Verify strong positioning if needed, but CSS might handle it? 
                    // The blade template used inline styles for positioning 'absolute', which is messy to replicate perfectly without more logic.
                    // Let's just fix the class and icon presence 90% of the way.
                    // The blade has: <strong style='position: {{ $currentProvider == $provider ? "absolute; left: 60px" : "" }}'>
                    const strong = btn.querySelector('strong');
                    if (strong) strong.style.cssText = 'position: absolute; left: 60px';
                } else {
                    const strong = btn.querySelector('strong');
                    if (strong) strong.style.cssText = '';
                }
            });
        }
    }).catch(error => {
        console.error('Failed to set search provider:', error);
        alert('Failed to set search provider: ' + (error.message || 'Unknown error'));
    });
}

window.setSearchQuality = function (quality) {
    window.updateTorrentSetting('torrenting.searchquality', quality).then(() => {
        const container = document.querySelector('[data-section="torrent-search"]');
        if (container) {
            const buttons = container.querySelectorAll('a[onclick^="setSearchQuality"]');
            buttons.forEach(btn => {
                // quality can be empty string for 'All'
                // onclick="setSearchQuality('')" vs onclick="setSearchQuality('FullHD')"
                const isMatch = btn.getAttribute('onclick') === `setSearchQuality('${quality}')`;

                if (isMatch) {
                    btn.classList.add('btn-success');
                } else {
                    btn.classList.remove('btn-success');
                }

                const existingIcon = btn.querySelector('.glyphicon');
                if (existingIcon) existingIcon.remove();

                const strong = btn.querySelector('strong');

                if (isMatch) {
                    const icon = document.createElement('i');
                    icon.className = 'glyphicon glyphicon-ok';
                    btn.insertBefore(icon, btn.firstChild);
                    if (strong) strong.style.paddingLeft = '30px';
                } else {
                    if (strong) strong.style.paddingLeft = '0';
                }
            });
        }
    }).catch(error => {
        console.error('Failed to set search quality:', error);
        alert('Failed to set search quality: ' + (error.message || 'Unknown error'));
    });
}

// Reuse toggleSetting for generic settings if needed, but here we specifically map to torrent settings for now
// or use a generic saveSetting if available. The blade template used 'saveSetting' locally defined.
// taking 'toggleSetting' from the blade:
window.toggleSetting = function (key, value) {
    // The blade template used saveSetting('torrenting.requirekeywordsmode', ...) which calls /settings/torrent-search
    // accessible via updateTorrentSetting (which posts to /settings/torrent -> TorrentController updates via SettingsService)
    // Wait, the blade posted to /settings/torrent-search.
    // Let's see if updateTorrentSetting posts to /settings/torrent.
    // The blade's saveSetting posted to /settings/torrent-search.
    // The SettingsController maps /settings/{section} to update().
    // So posting to /settings/torrent-search updates settings passed in body.

    // We can use Settings.save() style or just fetch directly.
    // Let's use a generic helper consistent with updateTorrentSetting but targeting the section if needed.
    // Actually updateTorrentSetting targets /settings/torrent.
    // The search settings are in 'torrent-search' section but stored in same SettingsService.
    // So /settings/torrent or /settings/torrent-search both work if they use SettingsService.

    window.updateTorrentSetting(key, value).then(() => {
        if (window.SidePanel && document.querySelector('[data-section="torrent-search"]')) {
            window.SidePanel.expand('/settings/torrent-search');
        } else {
            window.location.reload();
        }
    }).catch(error => {
        console.error('Failed to toggle setting:', error);
        alert('Failed to toggle setting: ' + (error.message || 'Unknown error'));
    });
}

// Also map saveSetting used in blade to updateTorrentSetting for consistency
window.saveSetting = function (key, value) {
    return window.updateTorrentSetting(key, value).then(() => {
        if (window.SidePanel && document.querySelector('[data-section="torrent-search"]')) {
            window.SidePanel.expand('/settings/torrent-search');
        }
    }).catch(error => {
        console.error('Failed to save setting:', error);
        alert('Failed to save setting: ' + (error.message || 'Unknown error'));
    });
}
