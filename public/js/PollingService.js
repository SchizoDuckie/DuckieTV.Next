
/**
 * PollingService.js
 * 
 * Handles periodic polling of the torrent client status.
 * Replaces the WebSocket/Reverb implementation.
 */
class PollingService {
    constructor(interval = 2000, translations = {}) {
        this.interval = interval;
        this.translations = translations;
        this.timer = null;
        this.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    }

    start() {
        if (this.timer) return;
        this.poll();
        this.timer = setInterval(() => this.poll(), this.interval);
        console.log('PollingService started.');
    }

    stop() {
        if (this.timer) {
            clearInterval(this.timer);
            this.timer = null;
        }
        console.log('PollingService stopped.');
    }

    async poll() {
        try {
            const response = await fetch('/torrents/status', {
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            this.handleStatusUpdate(data);

        } catch (error) {
            console.warn('PollingService error:', error);
            this.handleError(error);
        }
    }

    handleStatusUpdate(data) {
        const icon = document.querySelector('#actionbar_torrent a');
        if (!icon) return;

        const panel = document.querySelector('.torrent-client');

        if (data.connected) {
            // Only update status color. Icon class is handled by Blade.
            // Explicitly set the client class if it's different from the initial load? 
            // The user wanted Blade to handle it. If we switch clients, a page reload might be expected 
            // or we could update the class here if absolutely necessary, but for now we stick to just status.
            // Actually, if the user switches clients in settings, the page usually reloads.
            // So we can assume the class on the icon is correct for the active client.

            icon.style.color = '#5cb85c'; // Bootstrap success green
            const connectedText = this.translations.connected || 'Connected to';
            icon.title = `${connectedText} ${data.client} (${data.active_count})`;

            // If we were showing a connecting message, and now we are connected, refresh the whole panel
            // Or if we were showing a "no torrents" message but now we have torrents, refresh.
            if (panel) {
                const isConnecting = panel.querySelector('.connecting-message');
                const isEmpty = panel.querySelector('.no-torrents-message');

                if (data.connected && (isConnecting || (isEmpty && data.active_count > 0))) {
                    const url = panel.dataset.url;
                    if (url && window.SidePanel) {
                        window.SidePanel.update(url);
                        return; // update will handle the new content
                    }
                }
            }

            // Update torrent list if visible
            const list = panel ? panel.querySelector('.torrent-list') : null;
            if (list && data.torrents) {
                data.torrents.forEach(torrent => {
                    const torrentEl = list.querySelector(`[data-sidepanel-expand*="${torrent.infoHash}"]`);
                    if (torrentEl) {
                        const progressContainer = torrentEl.closest('.torrent').querySelector('.progress-bar');
                        if (progressContainer) {
                            progressContainer.style.width = torrent.progress + '%';
                            progressContainer.querySelector('span').textContent = torrent.progress + '%';

                            // Update color based on status
                            progressContainer.className = 'progress-bar ' +
                                (!torrent.isStarted && torrent.progress < 100 ? 'progress-bar-danger' :
                                    (torrent.isStarted && torrent.progress < 100 ? 'progress-bar-info' :
                                        (!torrent.isStarted && torrent.progress == 100 ? 'progress-bar-success' : 'progress-bar-warning')));
                        }
                    }
                });
            }

            // Update torrent details if visible
            const details = document.querySelector('.torrent-details');
            if (details && data.torrents) {
                const currentHash = details.dataset.infoHash;
                const torrent = data.torrents.find(t => t.infoHash === currentHash);
                if (torrent) {
                    if (window.DialGauge) {
                        window.DialGauge.update(details.querySelector('#gauge1'), Math.floor((torrent.downloadSpeed / 1000) * 10) / 10);
                        window.DialGauge.update(details.querySelector('#gauge2'), torrent.progress);
                    }
                }
            }

            // Dispatch a custom event for other components (e.g. TorrentDialog)
            const event = new CustomEvent('torrent-status-update', { detail: data });
            document.dispatchEvent(event);
        } else {
            // Disconnected state
            // We do NOT reset the icon class/image here as per user request to avoid "bullshit default icon"
            // We just indicate error state via color/title

            icon.style.color = '#d9534f'; // Bootstrap danger red
            icon.title = this.translations.disconnected || 'Torrent Client Disconnected';

            // Show error in panel if it's open and showing connecting
            if (panel && panel.querySelector('.connecting-message') && data.error) {
                const msg = panel.querySelector('.connecting-message strong');
                if (msg) msg.innerHTML = `<span class="text-danger">${data.error}</span>`;
            }
        }
    }

    handleError(error) {
        const icon = document.querySelector('#actionbar_torrent a');
        if (icon) {
            icon.style.color = '#f0ad4e'; // Bootstrap warning orange
            icon.title = this.translations.error || 'Polling Error';
        }
    }
}
