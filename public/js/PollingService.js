
/**
 * PollingService.js
 * 
 * Handles periodic polling of the torrent client status.
 * Replaces the WebSocket/Reverb implementation.
 */
class PollingService {
    constructor(interval = 2000) {
        this.interval = interval;
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
            icon.style.color = '#5cb85c'; // Bootstrap success green
            icon.title = `Connected to ${data.client} (${data.active_count} active)`;

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
                    this.updateGauge(details.querySelector('#gauge1'), Math.floor((torrent.downloadSpeed / 1000) * 10) / 10);
                    this.updateGauge(details.querySelector('#gauge2'), torrent.progress);
                }
            }

            // Dispatch a custom event for other components (e.g. TorrentDialog)
            const event = new CustomEvent('torrent-status-update', { detail: data });
            document.dispatchEvent(event);
        } else {
            icon.style.color = '#d9534f'; // Bootstrap danger red
            icon.title = 'Torrent Client Disconnected';

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
            icon.title = 'Polling Error';
        }
    }

    /**
     * Updates an SVG DialGauge element.
     * @param {HTMLElement} gaugeEl 
     * @param {number} value 
     */
    updateGauge(gaugeEl, value) {
        if (!gaugeEl) return;
        const min = parseFloat(gaugeEl.dataset.min || 0);
        let max = parseFloat(gaugeEl.dataset.max || 100);
        const relative = gaugeEl.dataset.relative === 'true';
        const color = gaugeEl.dataset.color || '#00FF00';
        const colorEnd = gaugeEl.dataset.colorEnd;

        if (relative) {
            while (value > max && max < 1000000000) {
                max *= 10;
            }
            gaugeEl.dataset.max = max;
        }

        const percent = (max > min) ? (value - min) / (max - min) : 0;
        const clampedPercent = Math.max(0, Math.min(1, percent));
        const dasharray = 232.478; // pi * 74 (original gauge radius)
        const dashoffset = dasharray * (1 - clampedPercent);

        const arc = gaugeEl.querySelector('.gauge-arc');
        if (arc) {
            arc.style.strokeDashoffset = dashoffset;
            arc.style.opacity = (value > 0 || !colorEnd) ? '1' : '0.1';

            if (colorEnd) {
                const c1 = this.hexToRgb(color);
                const c2 = this.hexToRgb(colorEnd);
                if (c1 && c2) {
                    const r = Math.round(c1.r + (c2.r - c1.r) * clampedPercent);
                    const g = Math.round(c1.g + (c2.g - c1.g) * clampedPercent);
                    const b = Math.round(c1.b + (c2.b - c1.b) * clampedPercent);
                    arc.style.stroke = `rgb(${r},${g},${b})`;
                }
            }
        }

        const valueEl = gaugeEl.querySelector('.dialgauge-value');
        if (valueEl) {
            this.tweenValue(valueEl, value, gaugeEl.dataset.prevValue || 0, colorEnd ? clampedPercent : null, color, colorEnd);
        }
        gaugeEl.dataset.prevValue = value;
    }

    /**
     * Smoothly animate a value change
     */
    tweenValue(el, target, start, percent, color, colorEnd) {
        const duration = 1500; // Animation duration in ms
        const startTime = performance.now();
        const startVal = parseFloat(start);
        const diff = target - startVal;

        // Cancel previous animation if running
        if (el.dataset.animId) {
            cancelAnimationFrame(parseInt(el.dataset.animId));
        }

        const parent = el.closest('ng-dial-gauge');
        const max = parent ? parseFloat(parent.dataset.max || 100) : 100;
        const unitEl = parent ? parent.querySelector('.dialgauge-unit') : null;
        const titleEl = parent ? parent.querySelector('.dialgauge-title') : null;

        const animate = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const currentVal = startVal + (diff * progress);

            el.textContent = currentVal.toLocaleString(undefined, { minimumFractionDigits: 1, maximumFractionDigits: 1 });

            // Update label colors to match interpolated bar color
            if (colorEnd) {
                const c1 = this.hexToRgb(color);
                const c2 = this.hexToRgb(colorEnd);
                if (c1 && c2) {
                    const currentPercent = currentVal / max;
                    const clampedP = Math.max(0, Math.min(1, currentPercent));
                    const r = Math.round(c1.r + (c2.r - c1.r) * clampedP);
                    const g = Math.round(c1.g + (c2.g - c1.g) * clampedP);
                    const b = Math.round(c1.b + (c2.b - c1.b) * clampedP);
                    const rgb = `rgb(${r},${g},${b})`;
                    if (unitEl) unitEl.style.fill = rgb;
                    if (titleEl) titleEl.style.fill = rgb;
                }
            }

            if (progress < 1) {
                el.dataset.animId = requestAnimationFrame(animate);
            } else {
                delete el.dataset.animId;
            }
        };

        el.dataset.animId = requestAnimationFrame(animate);
    }

    /**
     * Helper to convert HEX to RGB
     */
    hexToRgb(hex) {
        const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        return result ? {
            r: parseInt(result[1], 16),
            g: parseInt(result[2], 16),
            b: parseInt(result[3], 16)
        } : null;
    }
}
