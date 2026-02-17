/**
 * SidePanel Component
 *
 * Manages the slide-out navigation panel for series and episode details.
 * Supports a dual-panel system:
 * - Contracted: Shows only the left panel (450px)
 * - Expanded: Shows both left and right panels (840px)
 *
 * Automatically discoverable via <sidepanel> element.
 */
class SidePanel {
    /**
     * Finds required DOM elements and initializes event listeners.
     */
    /**
     * Finds required DOM elements and initializes event listeners.
     */
    constructor() {
        console.log('SidePanel: constructor');
        this.el = document.querySelector('sidepanel');
        if (!this.el) {
            console.error('SidePanel: <sidepanel> element not found!');
            return;
        }

        this.panel = this.el.querySelector('.sidepanel');
        // Initial attempt to find panels, but they might not exist yet
        this.leftPanel = this.el.querySelector('.leftpanel');
        this.rightPanel = this.el.querySelector('.rightpanel');
        this.closeBtn = this.el.querySelector('.close'); // This might be inside a panel, so might be null initially

        console.log('SidePanel: initialized', this.el);
        this.init();
    }

    /**
     * Binds click and keydown events for panel interactions.
     * Uses delegation for elements loaded via AJAX.
     */
    init() {
        // Global listener for close buttons (since they are dynamic)
        this.el.addEventListener('click', (e) => {
            if (e.target.closest('.close')) {
                this.hide();
            }
        });

        // Global click listener for sidepanel triggers
        document.addEventListener('click', (e) => {
            const showTrigger = e.target.closest('[data-sidepanel-show]');
            const expandTrigger = e.target.closest('[data-sidepanel-expand]');
            const updateTrigger = e.target.closest('[data-sidepanel-update]');

            if (updateTrigger) {
                e.preventDefault();
                const url = updateTrigger.dataset.sidepanelUpdate || updateTrigger.getAttribute('href');
                if (url) {
                    window.SidePanelUpdateUrl = url;
                    this.update(url);
                }
            } else if (showTrigger) {
                e.preventDefault();
                const url = showTrigger.dataset.sidepanelShow || showTrigger.getAttribute('href');
                if (url) {
                    window.SidePanelTriggerUrl = url;
                    this.show(url);
                }
            } else if (expandTrigger) {
                e.preventDefault();
                const url = expandTrigger.dataset.sidepanelExpand || expandTrigger.getAttribute('href');
                if (url) {
                    window.SidePanelTriggerUrl = url;
                    this.expand(url);
                }
            }
        });

        // Close on escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.hide();
            }
        });
    }

    /**
     * AJAX fetch helper with proper headers for Laravel.
     * @param {string} url The endpoint to fetch.
     * @returns {Promise<string>} HTML response body.
     */
    async fetch(url) {
        try {
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            if (!response.ok) throw new Error('Failed to load sidepanel content');
            return await response.text();
        } catch (error) {
            console.error(error);
            throw error;
        }
    }

    /**
     * Shows the panel in its contracted state (left panel only).
     * @param {string} url The AJAX endpoint to load.
     */
    async show(url, options = {}) {
        console.log('SidePanel: show', url);

        // Automatic 'settings' class if URL contains /settings
        if (url.includes('/settings')) {
            options.leftClass = 'settings';
        }

        if (url.includes('/about') || url.includes('torrents') || url.includes('autodlstatus')) {
            return this.expand(url, { ...options, fullWidth: true });
        }

        document.body.classList.add('sidepanelActive');
        if (!options.preserveState) {
            document.body.classList.remove('sidepanelExpanded');
        }

        try {
            const html = await this.fetch(url);
            this.setContent(html, 'left', options);
        } catch (error) {
            this.panel.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
        }
    }

    /**
     * Shows the panel in its expanded state (both panels).
     * @param {string} url The AJAX endpoint to load.
     */
    async expand(url, options = {}) {
        console.log('SidePanel: expand', url);
        document.body.classList.add('sidepanelActive');
        // We add sidepanelExpanded, but the CSS translation also depends on content structure
        document.body.classList.add('sidepanelExpanded');

        if (url.includes('/settings')) {
            options.rightClass = 'settings';
        }

        if (!options.fullWidth && (url.includes('/about') || url.includes('torrents') || url.includes('autodlstatus'))) {
            options.fullWidth = true;
        }

        try {
            const html = await this.fetch(url);
            this.setContent(html, 'right', options);
        } catch (error) {
            this.panel.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
        }
    }

    /**
     * Updates the left panel content without changing expansion state.
     */
    async update(url, options = {}) {
        // Same as show but keep the expanded state if active
        this.show(url, { ...options, preserveState: true });
    }

    /**
     * Hides the panel and clears content after animation completes.
     */
    hide() {
        document.body.classList.remove('sidepanelActive', 'sidepanelExpanded');
        setTimeout(() => {
            if (!document.body.classList.contains('sidepanelActive')) {
                this.panel.innerHTML = '';
                this.leftPanel = null;
                this.rightPanel = null;
            }
        }, 350);
    }

    /**
     * Updates the HTML content of the panel.
     * Intelligently handles full-structure injection vs partial injection.
     * @param {string} html The raw HTML content.
     * @param {string} target The target panel ('left' or 'right').
     */
    setContent(html, target = 'left', options = {}) {
        // Special Case: Full Width override (for About, Torrents, etc.)
        if (options.fullWidth) {
            this.panel.innerHTML = html;
            // Update references just in case the new content HAS structure
            this.leftPanel = this.panel.querySelector('.leftpanel');
            this.rightPanel = this.panel.querySelector('.rightpanel');
            return;
        }

        const temp = document.createElement('div');
        temp.innerHTML = html;

        const newLeft = temp.querySelector('.leftpanel');
        const newRight = temp.querySelector('.rightpanel');

        // Refresh references in case they were added manually or via previous calls
        this.leftPanel = this.panel.querySelector('.leftpanel');
        this.rightPanel = this.panel.querySelector('.rightpanel');

        // Case 1: Incoming content has structure (Left or Right panel wrapper)
        if (newLeft || newRight) {
            // If we are injecting a full structure (e.g. from scratch or replacing full view)
            // Or if we are targeting 'left' and the content HAS a left panel, we usually assume it's the main view.

            // If the DOM currently has no structure, OR if we are updating the 'left' panel (primary view),
            // convert to structured view.
            if ((!this.leftPanel && !this.rightPanel) || target === 'left') {
                // IMPROVEMENT: If preserving state, check if we can just replace the left panel
                if (options.preserveState && this.rightPanel && newLeft && (!newRight || newRight.innerHTML.trim() === '')) {
                    if (this.leftPanel) {
                        this.leftPanel.replaceWith(newLeft);
                    } else {
                        // Should not happen if rightPanel exists, but prepend just in case
                        this.panel.prepend(newLeft);
                    }
                    // Re-acquire reference
                    this.leftPanel = this.panel.querySelector('.leftpanel');
                } else {
                    this.panel.innerHTML = html;
                    this.leftPanel = this.panel.querySelector('.leftpanel');
                    this.rightPanel = this.panel.querySelector('.rightpanel');
                }
            } else {
                // We have structure, and we are updating 'right' (or strictly left?)
                // ... logic same as before (which was mostly empty)
            }
        }
        // Case 2: Incoming content works with EXISTING structure (injecting into left/right)
        else if (this.leftPanel || this.rightPanel) {
            // We have a structure in DOM, and incoming content is "bare".
            // Inject into the targeted panel.
            if (target === 'left' && this.leftPanel) {
                this.leftPanel.innerHTML = html;
                if (options.leftClass) this.leftPanel.classList.add(options.leftClass);
            } else if (target === 'right') {
                if (!this.rightPanel) {
                    console.log('SidePanel: Right panel missing for injection, creating it.');
                    this.rightPanel = document.createElement('div');
                    this.rightPanel.className = 'rightpanel';
                    this.panel.appendChild(this.rightPanel);
                }
                this.rightPanel.innerHTML = html;
                if (options.rightClass) this.rightPanel.classList.add(options.rightClass);
            } else {
                // Fallback: requested target doesn't exist?
                console.warn(`SidePanel: Requested target '${target}' but panel not found.`);
            }
        }
        // Case 3: No structure in DOM, No structure in Content => Full Width (e.g. About)
        else {
            this.panel.innerHTML = html;
            // No left/right references to set.
        }

        // Post-Injection checks
        this.leftPanel = this.panel.querySelector('.leftpanel');
        this.rightPanel = this.panel.querySelector('.rightpanel');

        // Manage cleanups
        if (target === 'left' && !document.body.classList.contains('sidepanelExpanded')) {
            if (this.rightPanel) {
                this.rightPanel.innerHTML = '';
                this.rightPanel.className = 'rightpanel'; // Reset
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
                // Optionally update UI if needed
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
                // Toggle classes locally for instant feedback
                // select all icons (left status and right action)
                const icons = el.querySelectorAll('.glyphicon');
                if (icons.length > 0) {
                    icons.forEach(icon => {
                        icon.classList.toggle('glyphicon-eye-open');
                        icon.classList.toggle('glyphicon-eye-close');
                    });
                } else if (el.classList.contains('glyphicon')) {
                    // Fallback for single icon buttons (like in list view maybe?)
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
        console.log('SidePanel: toggleEpisodeLeaked', episodeId);
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
                // Refresh the panel to update UI state (since the layout changes significantly for leaked episodes)
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
                // Refresh the list to reflect changes
                const url = window.location.href; // Or reuse the current expand/update URL if available
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
            setTimeout(() => btn.click(), (idx + 1) * 500); // Increased delay to be safer
        });
    }
}



