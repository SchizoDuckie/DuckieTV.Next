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
    constructor() {
        console.log('SidePanel: constructor');
        this.el = document.querySelector('sidepanel');
        if (!this.el) {
            console.error('SidePanel: <sidepanel> element not found!');
            return;
        }

        this.panel = this.el.querySelector('.sidepanel');
        this.leftPanel = this.el.querySelector('.leftpanel');
        this.rightPanel = this.el.querySelector('.rightpanel');
        this.closeBtn = this.el.querySelector('.close');

        console.log('SidePanel: initialized', this.el);
        this.init();
    }

    /**
     * Binds click and keydown events for panel interactions.
     * Uses delegation for elements loaded via AJAX.
     */
    init() {
        if (this.closeBtn) {
            this.closeBtn.addEventListener('click', () => this.hide());
        }

        // Global click listener for sidepanel triggers
        document.addEventListener('click', (e) => {
            const showTrigger = e.target.closest('[data-sidepanel-show]');
            const expandTrigger = e.target.closest('[data-sidepanel-expand]');
            const updateTrigger = e.target.closest('[data-sidepanel-update]');

            if (updateTrigger) {
                e.preventDefault();
                const url = updateTrigger.dataset.sidepanelUpdate || updateTrigger.getAttribute('href');
                if (url) this.update(url);
            } else if (showTrigger) {
                e.preventDefault();
                const url = showTrigger.dataset.sidepanelShow || showTrigger.getAttribute('href');
                if (url) this.show(url);
            } else if (expandTrigger) {
                e.preventDefault();
                const url = expandTrigger.dataset.sidepanelExpand || expandTrigger.getAttribute('href');
                if (url) this.expand(url);
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
        document.body.classList.add('sidepanelActive');
        document.body.classList.remove('sidepanelExpanded');

        // Automatic 'settings' class if URL contains /settings
        if (url.includes('/settings')) {
            options.leftClass = 'settings';
        }

        try {
            const html = await this.fetch(url);
            this.setContent(html, 'left', options);
        } catch (error) {
            if (this.leftPanel) {
                this.leftPanel.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
            }
        }
    }

    /**
     * Shows the panel in its expanded state (both panels).
     * Typically used for detailed metadata or seasonal episode lists.
     * @param {string} url The AJAX endpoint to load.
     */
    async expand(url, options = {}) {
        console.log('SidePanel: expand', url);
        document.body.classList.add('sidepanelActive', 'sidepanelExpanded');

        if (url.includes('/settings')) {
            options.rightClass = 'settings';
        }

        try {
            const html = await this.fetch(url);
            this.setContent(html, 'right', options);
        } catch (error) {
            if (this.rightPanel) {
                this.rightPanel.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
            }
        }
    }

    /**
     * Updates the left panel content without changing expansion state.
     * Used for master-detail navigation (e.g., clicking an episode
     * in the right panel updates the left panel while keeping the
     * episodes list visible on the right).
     * @param {string} url The AJAX endpoint to load.
     */
    async update(url, options = {}) {
        console.log('SidePanel: update', url);
        document.body.classList.add('sidepanelActive');

        if (url.includes('/settings')) {
            options.leftClass = 'settings';
        }

        try {
            const html = await this.fetch(url);
            this.setContent(html, 'left', options);
        } catch (error) {
            if (this.leftPanel) {
                this.leftPanel.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
            }
        }
    }

    /**
     * Hides the panel and clears content after animation completes.
     */
    hide() {
        document.body.classList.remove('sidepanelActive', 'sidepanelExpanded');
        setTimeout(() => {
            if (!document.body.classList.contains('sidepanelActive')) {
                if (this.leftPanel) this.leftPanel.innerHTML = '';
                if (this.rightPanel) this.rightPanel.innerHTML = '';
            }
        }, 350);
    }

    /**
     * Updates the HTML content of a specific panel.
     * If html contains both panels, it updates both and manages expansion state.
     * @param {string} html The raw HTML content.
     * @param {string} target The target panel ('left' or 'right').
     */
    setContent(html, target = 'left', options = {}) {
        const temp = document.createElement('div');
        temp.innerHTML = html;

        const leftContent = temp.querySelector('.leftpanel');
        const rightContent = temp.querySelector('.rightpanel');

        // Helper to sync classes from options
        const syncClasses = () => {
            if (options.leftClass && this.leftPanel) {
                this.leftPanel.classList.add(options.leftClass);
            }
            if (options.rightClass && this.rightPanel) {
                this.rightPanel.classList.add(options.rightClass);
            }
        };

        // If the fragment contains explicit panel wrappers, sync them
        if (leftContent || rightContent) {
            if (leftContent && this.leftPanel) {
                this.leftPanel.className = leftContent.className;
                this.leftPanel.innerHTML = leftContent.innerHTML;
            }
            if (rightContent && this.rightPanel) {
                this.rightPanel.className = rightContent.className;
                this.rightPanel.innerHTML = rightContent.innerHTML;
                if (rightContent.innerHTML.trim() !== '') {
                    document.body.classList.add('sidepanelExpanded');
                } else {
                    document.body.classList.remove('sidepanelExpanded');
                }
            }
        } else {
            // Otherwise inject into the intended target panel
            const panel = (target === 'left') ? this.leftPanel : this.rightPanel;
            if (panel) {
                panel.innerHTML = html;
                syncClasses();
            }

            // If we injected into left and are NOT expanded, clear the right panel.
            if (target === 'left' && !document.body.classList.contains('sidepanelExpanded')) {
                if (this.rightPanel) {
                    this.rightPanel.innerHTML = '';
                    this.rightPanel.className = 'rightpanel'; // Reset
                }
            }
        }
    }
}



