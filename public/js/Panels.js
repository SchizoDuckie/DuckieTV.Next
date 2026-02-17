/**
 * Panels Component
 * 
 * Manages the large slide-out panels for Favorites (Library) and Search/Adding.
 * Binds directly to Action Bar elements and scopes delegation to the series list container.
 */
class Panels {
    constructor(options = {}) {
        console.log('Panels: constructor');
        this.libraryRoute = options.libraryRoute;
        this.currentUrl = null;

        this.init();
    }

    init() {
        // Scoped delegation on action-bar, capture phase so it fires before WebView native navigation
        const actionBar = document.querySelector('action-bar');
        if (actionBar) {
            actionBar.addEventListener('click', (e) => {
                const serieslist = e.target.closest('[data-serieslist-show]');
                if (serieslist) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.toggle('serieslist', serieslist.dataset.serieslistShow);
                    return;
                }
                const seriesadding = e.target.closest('[data-seriesadding-show]');
                if (seriesadding) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.toggle('seriesadding', seriesadding.dataset.seriesaddingShow);
                    return;
                }
                if (e.target.closest('.close-panel')) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.hide();
                }
            }, true);
        }

        // Dropdown handler scoped to series-list (no bootstrap.js)
        const seriesList = document.querySelector('series-list');
        if (seriesList) {
            seriesList.addEventListener('click', (e) => {
                if (e.target.dataset.toggle === 'dropdown') {
                    e.preventDefault();
                    const parent = e.target.parentElement;
                    const isOpen = parent.classList.contains('open');
                    seriesList.querySelectorAll('.open').forEach(el => el.classList.remove('open'));
                    if (!isOpen) parent.classList.add('open');
                    return;
                }
                seriesList.querySelectorAll('.open').forEach(el => el.classList.remove('open'));
            });
        }

        // Close on escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') this.hide();
        });
    }

    toggle(type, url) {
        const bodyClass = type === 'serieslist' ? 'serieslistActive' : 'seriesaddingActive';
        if (document.body.classList.contains(bodyClass) && this.currentUrl === url) {
            this.hide();
        } else {
            this.show(type, url);
        }
    }

    async show(type, url) {
        if (!url) return;

        const listPanel = document.querySelector('series-list[ui-view="favorites"]') || document.querySelector('series-list');
        if (!listPanel) {
            console.error('Panels: <series-list> not found.');
            return;
        }

        const container = listPanel.querySelector('.series-list');
        if (!container) {
            console.error('Panels: .series-list container not found.');
            return;
        }

        this.hide(false);
        this.currentUrl = url;
        document.body.classList.add(type === 'serieslist' ? 'serieslistActive' : 'seriesaddingActive');

        try {
            const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            container.innerHTML = await response.text();
            this.reinitPanelContent(type);
        } catch (error) {
            console.error('Panels: error', error);
            container.innerHTML = '<div style="padding: 100px; text-align: center; color: red;"><h3>Error loading content</h3></div>';
        }
    }

    hide(resetUrl = true) {
        document.body.classList.remove('serieslistActive', 'seriesaddingActive');
        if (resetUrl) this.currentUrl = null;
    }

    reinitPanelContent(type) {
        if (type === 'serieslist') {
            const grid = document.querySelector('.series-grid');
            if (grid) {
                const items = Array.from(grid.querySelectorAll('serieheader'));
                const filterItems = () => {
                    const activeStatuses = Array.from(document.querySelectorAll('.status-filter:checked')).map(el => el.dataset.status);
                    const activeGenres = Array.from(document.querySelectorAll('.genre-filter:checked')).map(el => el.dataset.genre);
                    items.forEach(item => {
                        const status = item.getAttribute('data-status') ? item.getAttribute('data-status').toLowerCase() : '';
                        const genres = item.getAttribute('data-genre') ? item.getAttribute('data-genre').toLowerCase().split(' ') : [];
                        const statusMatch = activeStatuses.length === 0 || activeStatuses.includes(status);
                        const genreMatch = activeGenres.length === 0 || activeGenres.some(g => genres.includes(g));
                        item.style.display = (statusMatch && genreMatch) ? 'inline-block' : 'none';
                    });
                };
                document.querySelectorAll('.status-filter, .genre-filter').forEach(el => el.addEventListener('change', filterItems));
            }
        }
    }
}
