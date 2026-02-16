/**
 * TraktTrending Component
 * 
 * Manages the overlay panel for Trakt Trending shows.
 * Replicates the original "large panel drops down from the top" behavior.
 */
class TraktTrending {
    constructor() {
        this.trigger = document.querySelector('#actionbar_trakt a');
        this.overlay = document.querySelector('#trakt-trending-overlay');
        this.content = this.overlay ? this.overlay.querySelector('.content') : null;
        this.closeBtn = this.overlay ? this.overlay.querySelector('.close-overlay') : null;

        if (!this.trigger || !this.overlay) return;

        this.init();
    }

    init() {
        this.trigger.addEventListener('click', (e) => {
            e.preventDefault();
            this.toggle();
        });

        if (this.closeBtn) {
            this.closeBtn.addEventListener('click', () => this.hide());
        }

        // Close on escape
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
        return this.overlay.classList.contains('active');
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
        this.overlay.classList.add('active');
        document.body.style.overflow = 'hidden'; // Prevent background scrolling
    }

    hide() {
        this.overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    async load() {
        this.content.innerHTML = '<div class="loading-spinner"><i class="glyphicon glyphicon-refresh spinning"></i> Loading...</div>';

        try {
            const url = this.trigger.getAttribute('href');
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (!response.ok) throw new Error('Failed to load trending shows');

            const html = await response.text();
            this.content.innerHTML = html;
        } catch (error) {
            this.content.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
        }
    }
}

// Initialize on load
document.addEventListener('DOMContentLoaded', () => {
    window.TraktTrending = new TraktTrending();
});
