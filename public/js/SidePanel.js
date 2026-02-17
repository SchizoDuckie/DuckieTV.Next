/**
 * SidePanel Component
 *
 * Manages the slide-out navigation panel for series and episode details.
 * Uses direct binding for Action Bar triggers and scoped delegation for content.
 */
class SidePanel {
    constructor() {
        console.log('SidePanel: constructor');
        this.el = document.querySelector('sidepanel');
        this.seriesList = document.querySelector('series-list');

        if (!this.el) {
            console.error('SidePanel: <sidepanel> element not found!');
            return;
        }

        this.panel = this.el.querySelector('.sidepanel');
        this.leftPanel = this.el.querySelector('.leftpanel');
        this.rightPanel = this.el.querySelector('.rightpanel');
        this.abortController = null;

        this.init();
    }

    init() {
        // 1. SCOPED DELEGATION for Action Bar sidepanel triggers
        const actionBar = document.querySelector('action-bar');
        if (actionBar) {
            actionBar.addEventListener('click', (e) => {
                const trigger = e.target.closest('[data-sidepanel-show]');
                if (!trigger) return;
                e.preventDefault();
                e.stopPropagation();
                const url = trigger.dataset.sidepanelShow;
                if (!url) return;
                window.SidePanelTriggerUrl = url;
                this.show(url);
            }, true); // capture phase: fires before native WebView link navigation
        }


        // 2. SCOPED DELEGATION: Listeners attached only to specific containers
        //    instead of the global document.

        const handleDelegatedClick = (e) => {
            const showTrigger = e.target.closest('[data-sidepanel-show]');
            const expandTrigger = e.target.closest('[data-sidepanel-expand]');
            const updateTrigger = e.target.closest('[data-sidepanel-update]');

            if (updateTrigger) {
                e.preventDefault();
                e.stopPropagation();
                const url = updateTrigger.dataset.sidepanelUpdate || updateTrigger.getAttribute('href');
                if (url) {
                    window.SidePanelUpdateUrl = url;
                    this.update(url);
                }
                return false;
            } else if (showTrigger) {
                e.preventDefault();
                e.stopPropagation();
                const url = showTrigger.dataset.sidepanelShow || showTrigger.getAttribute('href');
                if (url) {
                    window.SidePanelTriggerUrl = url;
                    this.show(url);
                }
                return false;
            } else if (expandTrigger) {
                e.preventDefault();
                e.stopPropagation();
                const url = expandTrigger.dataset.sidepanelExpand || expandTrigger.getAttribute('href');
                if (url) {
                    window.SidePanelTriggerUrl = url;
                    this.expand(url);
                }
                return false;
            }
        };

        // Attach to SidePanel itself (for items inside it)
        this.el.addEventListener('click', (e) => {
            // Also handle close button here since we are listening anyway
            if (e.target.closest('.close')) {
                this.hide();
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
            handleDelegatedClick(e);
        });

        // Attach to Series List (for items in the main grid)
        if (this.seriesList) {
            this.seriesList.addEventListener('click', handleDelegatedClick);
        } else {
            // Fallback if series-list isn't there yet (unlikely in this structure but safe)
            document.addEventListener('DOMContentLoaded', () => {
                const sl = document.querySelector('series-list');
                if (sl) sl.addEventListener('click', handleDelegatedClick);
            });
        }

        // Close on escape is the only global one needed
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') this.hide();
        });
    }

    async fetch(url) {
        if (this.abortController) this.abortController.abort();
        this.abortController = new AbortController();

        try {
            const response = await fetch(url, {
                signal: this.abortController.signal,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!response.ok) throw new Error('Failed to load sidepanel content');
            return await response.text();
        } catch (error) {
            if (error.name === 'AbortError') return null;
            console.error(error);
            throw error;
        }
    }

    async show(url, options = {}) {
        if (url.includes('/settings')) options.leftClass = 'settings';
        if (url.includes('/about') || url.includes('torrents') || url.includes('autodlstatus')) {
            return this.expand(url, { ...options, fullWidth: true });
        }

        document.body.classList.add('sidepanelActive');
        if (!options.preserveState) document.body.classList.remove('sidepanelExpanded');

        try {
            const html = await this.fetch(url);
            if (html === null) return;
            this.setContent(html, 'left', options);
        } catch (error) {
            this.panel.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
        }
    }

    async expand(url, options = {}) {
        document.body.classList.add('sidepanelActive');
        document.body.classList.add('sidepanelExpanded');
        try {
            const html = await this.fetch(url);
            if (html === null) return;
            this.setContent(html, 'right', options);
        } catch (error) {
            this.panel.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
        }
    }

    async update(url, options = {}) {
        this.show(url, { ...options, preserveState: true });
    }

    hide() {
        if (this.abortController) {
            this.abortController.abort();
            this.abortController = null;
        }
        document.body.classList.remove('sidepanelActive', 'sidepanelExpanded');
        setTimeout(() => {
            if (!document.body.classList.contains('sidepanelActive')) {
                this.panel.innerHTML = '';
            }
        }, 350);
    }

    setContent(html, target = 'left', options = {}) {
        if (options.fullWidth) {
            this.panel.innerHTML = html;
            return;
        }
        const temp = document.createElement('div');
        temp.innerHTML = html;
        const newLeft = temp.querySelector('.leftpanel');

        this.leftPanel = this.panel.querySelector('.leftpanel');
        this.rightPanel = this.panel.querySelector('.rightpanel');

        if (newLeft) {
            if ((!this.leftPanel && !this.rightPanel) || target === 'left') {
                if (options.preserveState && this.rightPanel && newLeft) {
                    if (this.leftPanel) this.leftPanel.replaceWith(newLeft);
                    else this.panel.prepend(newLeft);
                } else {
                    this.panel.innerHTML = html;
                }
            }
        } else {
            if (target === 'left' && this.leftPanel) this.leftPanel.innerHTML = html;
            else if (target === 'right') {
                if (!this.rightPanel) {
                    this.rightPanel = document.createElement('div');
                    this.rightPanel.className = 'rightpanel';
                    this.panel.appendChild(this.rightPanel);
                }
                this.rightPanel.innerHTML = html;
            } else {
                this.panel.innerHTML = html;
            }
        }
    }

    /**
     * Trigger auto-download for an episode
     * @param {number} episodeId 
     */
    async autoDownload(episodeId) {
        console.log('SidePanel: autoDownload', episodeId);
        window.Toast.info('Triggering automated search and download...');
        try {
            const response = await fetch(`/episodes/${episodeId}/auto-download`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const data = await response.json();
            if (data.success) {
                window.Toast.success(data.message);
            } else {
                window.Toast.error(data.message);
            }
        } catch (error) {
            console.error('AutoDownload Error:', error);
            window.Toast.error('Failed to trigger auto-download.');
        }
    }

    /**
     * Toggle watched status for an episode via AJAX
     * @param {number} episodeId 
     * @param {HTMLElement} el The anchor element that was clicked
     */
    async toggleEpisodeWatched(episodeId, el) {
        try {
            const response = await fetch(`/episodes/${episodeId}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ action: 'toggle_watched' })
            });
            if (response.ok) {
                const data = await response.json();
                const icons = el.querySelectorAll('.glyphicon');
                if (icons.length > 0) {
                    icons.forEach(icon => {
                        icon.classList.toggle('glyphicon-eye-open');
                        icon.classList.toggle('glyphicon-eye-close');
                    });
                } else if (el.classList.contains('glyphicon')) {
                    el.classList.toggle('glyphicon-eye-open');
                    el.classList.toggle('glyphicon-eye-close');
                }
                window.Toast.success('Watched status updated');
            }
        } catch (error) {
            console.error('Toggle Watched Error:', error);
            window.Toast.error('Failed to update watched status');
        }
    }

    /**
     * Toggle downloaded status for an episode via AJAX
     * @param {number} episodeId 
     * @param {HTMLElement} el The anchor element that was clicked
     */
    async toggleEpisodeDownloaded(episodeId, el) {
        try {
            const response = await fetch(`/episodes/${episodeId}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ action: 'toggle_download' })
            });
            if (response.ok) {
                const data = await response.json();
                const icons = el.querySelectorAll('.glyphicon');
                if (icons.length > 0) {
                    icons.forEach(icon => {
                        icon.classList.toggle('glyphicon-floppy-saved');
                        icon.classList.toggle('glyphicon-floppy-disk');
                    });
                } else if (el.classList.contains('glyphicon')) {
                    el.classList.toggle('glyphicon-floppy-saved');
                    el.classList.toggle('glyphicon-floppy-disk');
                }
                window.Toast.success('Downloaded status updated');
            }
        } catch (error) {
            console.error('Toggle Downloaded Error:', error);
            window.Toast.error('Failed to update downloaded status');
        }
    }

    /**
     * Toggle leaked status for an episode via AJAX
     * @param {number} episodeId 
     */
    async toggleEpisodeLeaked(episodeId) {
        try {
            const response = await fetch(`/episodes/${episodeId}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ action: 'toggle_leaked' })
            });
            if (response.ok) {
                const data = await response.json();
                window.Toast.success(data.message || 'Leaked status updated');
                if (window.SidePanelTriggerUrl) {
                    this.expand(window.SidePanelTriggerUrl, { updateOnly: true });
                }
            } else {
                window.Toast.error('Failed to update leaked status');
            }
        } catch (error) {
            console.error('Toggle Leaked Error:', error);
            window.Toast.error('Failed to update leaked status');
        }
    }

    /**
     * Mark entire season as watched via AJAX
     */
    async markSeasonWatched(serieId, seasonId) {
        try {
            const response = await fetch(`/series/${serieId}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ action: 'mark_season_watched', season_id: seasonId })
            });
            if (response.ok) {
                window.Toast.success('Season marked as watched');
                this.expand(window.SidePanelTriggerUrl, { updateOnly: true });
            }
        } catch (error) {
            console.error('Mark Season Watched Error:', error);
            window.Toast.error('Failed to mark season as watched');
        }
    }

    /**
     * Mark entire season as downloaded via AJAX
     */
    async markSeasonDownloaded(serieId, seasonId) {
        try {
            const response = await fetch(`/series/${serieId}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ action: 'mark_season_downloaded', season_id: seasonId })
            });
            if (response.ok) {
                window.Toast.success('Season marked as downloaded');
                this.expand(window.SidePanelTriggerUrl, { updateOnly: true });
            }
        } catch (error) {
            console.error('Mark Season Downloaded Error:', error);
            window.Toast.error('Failed to mark season as downloaded');
        }
    }

    /**
     * Open torrent settings for a series
     * @param {Object} serie 
     */
    torrentSettings(serie) {
        console.log('SidePanel: torrentSettings', serie.name);
        window.Toast.info('Series settings modal not yet implemented. Coming soon!');
    }

    /**
     * Trigger auto-download for all episodes in the current view
     */
    autoDownloadAll() {
        console.log('SidePanel: autoDownloadAll');
        const buttons = document.querySelectorAll('.active-season-episode .auto-download-episode');
        if (buttons.length === 0) {
            window.Toast.info('No episodes found to download.');
            return;
        }

        window.Toast.info(`Starting auto-download for ${buttons.length} episodes...`);
        Array.from(buttons).reverse().forEach((btn, idx) => {
            setTimeout(() => btn.click(), (idx + 1) * 500);
        });
    }
}
