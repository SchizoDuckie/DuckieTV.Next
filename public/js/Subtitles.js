window.Subtitles = {
    /**
     * Global initializer for action bar triggers
     */
    init: function () {
        // DIRECT BINDING ONLY
        const btn = document.querySelector('#actionbar_subtitles a');
        if (btn) {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.search();
                return false;
            });
        }
        // Removed global document delegation as requested
    },

    /**
     * Search for subtitles for a specific episode or by query string.
     * @param {string|number|null} query Optional query string or episodeId
     */
    search: function (query = null) {
        if (typeof query === 'number') {
            return this.openByEpisode(query);
        }

        this.ensureOverlayLoaded(() => {
            const overlay = document.getElementById('subtitles-dialog-root');
            if (overlay) {
                overlay.style.display = 'block';
            }

            if (!query) {
                const searchInput = document.getElementById('subtitles-query');
                if (searchInput) {
                    searchInput.focus();
                }
                return;
            }

            this.showSearching(true, query);

            fetch('/subtitles/search-query', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ query: query })
            })
                .then(response => response.text())
                .then(html => {
                    this.updateResults(html, query);
                })
                .catch(error => {
                    console.error('Subtitle search error:', error);
                    this.showSearching(false);
                });
        });
    },

    /**
     * Search for subtitles for a specific episode
     * @param {number} episodeId 
     */
    openByEpisode: function (episodeId) {
        this.ensureOverlayLoaded(() => {
            this.showSearching(true);

            fetch('/subtitles/search', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ episode_id: episodeId })
            })
                .then(response => response.text())
                .then(html => {
                    this.updateResults(html);
                })
                .catch(error => {
                    console.error('Subtitle search error:', error);
                    this.showSearching(false);
                });
        });
    },

    ensureOverlayLoaded: function (callback) {
        if (document.getElementById('subtitles-dialog-root')) {
            callback();
            return;
        }

        fetch('/subtitles')
            .then(response => response.text())
            .then(html => {
                const div = document.createElement('div');
                div.id = 'subtitles-wrapper'; // Shell wrapper
                div.innerHTML = html;
                document.body.appendChild(div);
                callback();
            });
    },

    showSearching: function (isSearching, query = '') {
        const overlay = document.getElementById('subtitles-dialog-root');
        if (!overlay) return;
        overlay.style.display = 'block';

        const searchingRow = document.getElementById('subtitles-searching-row');
        const resultsHeader = document.getElementById('subtitles-results-header');
        const noResultsRow = document.getElementById('subtitles-no-results-row');
        const resultsBody = document.getElementById('subtitles-results-body');
        const loadingIcon = document.getElementById('subtitles-loading-icon');

        if (searchingRow) searchingRow.style.display = isSearching ? 'table-row' : 'none';
        if (resultsHeader) resultsHeader.style.display = 'none';
        if (noResultsRow) noResultsRow.style.display = 'none';
        if (resultsBody) resultsBody.innerHTML = '';
        if (loadingIcon) loadingIcon.style.display = isSearching ? 'inline-block' : 'none';
    },

    updateResults: function (html, query = '') {
        const body = document.getElementById('subtitles-results-body');
        const searchingRow = document.getElementById('subtitles-searching-row');
        const header = document.getElementById('subtitles-results-header');
        const noResults = document.getElementById('subtitles-no-results-row');
        const loadingIcon = document.getElementById('subtitles-loading-icon');

        if (searchingRow) searchingRow.style.display = 'none';
        if (loadingIcon) loadingIcon.style.display = 'none';

        if (!html || html.trim() === '') {
            console.log('Subtitles: No results for query:', query);
            if (noResults) {
                noResults.style.display = 'table-row';
                const display = document.getElementById('subtitles-query-display');
                if (display) display.innerText = query;
            }
            if (header) header.style.display = 'none';
            if (body) body.innerHTML = '';
        } else {
            console.log('Subtitles: Rendering results');
            if (noResults) noResults.style.display = 'none';
            if (header) header.style.display = 'table-header-group';
            if (body) body.innerHTML = html;
        }
    },

    close: function () {
        const overlay = document.getElementById('subtitles-dialog-root');
        if (overlay) {
            overlay.style.display = 'none';
        }
    }
};
