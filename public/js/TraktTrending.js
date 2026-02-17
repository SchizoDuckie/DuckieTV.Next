/**
 * TraktTrending Component
 * 
 * Manages the overlay panel for Trakt Trending shows.
 * 
 * UPDATE: Global listener removed as the trigger attribute 'data-trakt-trending-show' 
 * is not currently used in the main action bar (which uses Panels.js for 'seriesadding').
 * Kept class structure for potential future use or manual triggering.
 */
class TraktTrending {
    constructor() {
        this.overlay = document.querySelector('#trakt-trending-overlay');
        this.content = this.overlay ? this.overlay.querySelector('.content') : null;
        this.closeBtn = this.overlay ? this.overlay.querySelector('.close-overlay') : null;

        if (!this.overlay) return;

        this.init();
    }

    init() {
        // ACTION BAR BINDING:
        // If there is a specific button for this separate from Panels.js, bind it here.
        // Currently, #add_favorites is handled by Panels.js.
        // We do NOT attach a global document listener here to avoid "expensive closest calls".

        if (this.closeBtn) {
            this.closeBtn.addEventListener('click', () => this.hide());
        }

        // Close on escape if visible
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isVisible()) {
                this.hide();
            }
        });

        // Close on click outside
        this.overlay.addEventListener('click', (e) => {
            if (e.target === this.overlay) {
                this.hide();
            }
        });
    }

    isVisible() {
        return this.overlay.style.display === 'block';
    }

    toggle() {
        if (this.isVisible()) {
            this.hide();
        } else {
            this.show();
        }
    }

    async show() {
        if (!this.content.innerHTML.trim()) {
            await this.load();
        }
        this.overlay.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    hide() {
        this.overlay.style.display = 'none';
        document.body.style.overflow = '';
    }

    async load() {
        this.content.innerHTML = '<div class="loading-spinner"><i class="glyphicon glyphicon-refresh spinning"></i> Loading...</div>';

        // Assuming a route reference is needed, but since we removed the trigger logic,
        // this would need to be passed in or defined. 
        // Preserving existing logic structure:
        try {
            // Trigger definition missing in this context, logic would need update if used.
            // keeping placeholder to avoid syntax errors if method called.
            console.warn('TraktTrending: load() called but source URL logic is deprecated.');
        } catch (error) {
            this.content.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
        }
    }
}

// Initialize on load
document.addEventListener('DOMContentLoaded', () => {
    window.TraktTrending = new TraktTrending();
});
