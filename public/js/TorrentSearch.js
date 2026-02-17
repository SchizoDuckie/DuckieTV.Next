/**
 * TorrentSearch Component
 *
 * Manages the torrent search modal dialog lifecycle using the shared Modal class.
 */
class TorrentSearch {

    /**
     * Open the torrent search dialog as a modal overlay.
     * Use shared Modal class!
     */
    static async open(dialogUrl) {
        TorrentSearch.close();

        try {
            const response = await fetch(dialogUrl, {
                headers: {
                    'Accept': 'text/html',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                console.error('TorrentSearch: Failed to load dialog', response.status);
                return;
            }

            const html = await response.text();

            // Parse HTML to extract parts
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            // Extract attributes from root .modal div
            const root = doc.body.firstChild;
            const config = {
                searchRoute: root.getAttribute('data-search-route'),
                detailsRoute: root.getAttribute('data-details-route'),
                addRoute: root.getAttribute('data-add-route'),
                titleTemplate: root.getAttribute('data-title-template'),
                episodeId: root.getAttribute('data-episode-id')
            };

            // Extract Title (if exists)
            const titleEl = doc.querySelector('.modal-title');
            const title = titleEl ? titleEl.innerHTML : 'FIND TORRENT';

            // Extract Body (content inside modal-body)
            const bodyEl = doc.querySelector('.modal-body');
            const bodyContent = bodyEl ? bodyEl.innerHTML : '';


            const modal = new Modal({
                size: 'lg',
                windowClass: 'dialogs-default' // Standard look
            });

            // Show the modal with hidden header
            modal.show('', bodyContent, '', 'hidden-modal-header');


            TorrentSearch._instance = new TorrentSearch(modal, config);


            // Bind Advanced Options Toggle
            const toggleAdv = modal.el.querySelector('#torrent-advanced-toggle');
            const advOptions = modal.el.querySelector('#torrent-advanced-options');
            if (toggleAdv && advOptions) {
                toggleAdv.addEventListener('click', (e) => {
                    e.preventDefault();
                    const isHidden = advOptions.style.display === 'none';
                    advOptions.style.display = isHidden ? 'block' : 'none';
                    const span = toggleAdv.querySelector('span');
                    if (span) {
                        span.innerText = isHidden ? 'Hide Advanced Options' : 'Show Advanced Options';
                    }
                });
            }

            // Bind Engine Toggles via delegation on the modal
            modal.el.addEventListener('change', async (e) => {
                const checkbox = e.target.closest('.engine-toggle');
                if (!checkbox) return;
                try {
                    const enabledEngines = Array.from(modal.el.querySelectorAll('.engine-toggle:checked'))
                        .map(cb => cb.dataset.engine);
                    await fetch('/settings/torrent-search', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ 'torrenting.search_enabled_engines': enabledEngines }),
                    });
                    if (TorrentSearch._instance) {
                        TorrentSearch._instance.doSearch();
                    }
                } catch (e) {
                    console.error('Failed to save engine setting', e);
                }
            });


        } catch (error) {
            console.error('TorrentSearch: Error opening dialog', error);
            TorrentSearch.close();
        }
    }

    static close() {
        if (TorrentSearch._instance) {
            TorrentSearch._instance.modal.hide();
            TorrentSearch._instance = null;
        }
    }

    static init() {
        const btn = document.querySelector('#actionbar_search a');
        if (btn) {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                TorrentSearch.open(btn.getAttribute('href'));
            });
        }
    }

    /**
     * @param {Modal} modalInstance The Modal class instance
     * @param {Object} config Route configuration
     */
    constructor(modalInstance, config) {
        this.modal = modalInstance; // The Modal class instance
        this.el = modalInstance.el; // The DOM element (.modal)

        this.searchRoute = config.searchRoute;
        this.detailsRoute = config.detailsRoute;
        this.addRoute = config.addRoute;
        this.episodeId = config.episodeId;
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        this.titleTemplate = config.titleTemplate || '';

        this.results = [];
        this.sortField = 'seeders';
        this.sortDesc = true;
        this.currentEngine = '';

        this.bindElements();
        this.bindEvents();

        // Auto-search if query is pre-filled
        if (this.searchInput && this.searchInput.value.trim()) {
            this.doSearch();
        }
    }

    /**
     * Cache references to DOM elements within the modal.
     */
    bindElements() {
        // Elements are inside this.el (the modal wrapper)
        this.searchInput = this.el.querySelector('#torrent-search-input');
        this.searchBtn = this.el.querySelector('#torrent-search-btn');

        // Status Rows/Bodies
        this.initialStateRow = this.el.querySelector('#torrent-initial-state');
        this.searchingRow = this.el.querySelector('#torrent-searching-row');
        this.noResultsRow = this.el.querySelector('#torrent-no-results-row');
        this.noResultsQuery = this.el.querySelector('#torrent-no-results-query');
        this.errorRow = this.el.querySelector('#torrent-error-row');
        this.errorMsg = this.el.querySelector('#torrent-error-msg');

        this.resultsHeader = this.el.querySelector('#torrent-results-header');
        this.resultsBody = this.el.querySelector('#torrent-results-body');
    }

    // ... (rest of the file)

    bindEvents() {
        if (this.searchBtn) {
            this.searchBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.doSearch();
            });
        }
        if (this.searchInput) {
            this.searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') this.doSearch();
            });
        }

        // Single delegated listener for all interactive elements in the modal
        this.el.addEventListener('click', (e) => {
            const addBtn = e.target.closest('.torrent-add-client');
            if (addBtn) {
                e.preventDefault();
                this.addTorrent(addBtn.dataset.magnet, addBtn.dataset.url, addBtn.dataset.infoHash, addBtn.dataset.releaseName);
                return;
            }

            const magnetBtn = e.target.closest('.torrent-fetch-magnet');
            if (magnetBtn) {
                e.preventDefault();
                this.fetchDetails(parseInt(magnetBtn.dataset.index), magnetBtn, 'magnet');
                return;
            }

            const torrentBtn = e.target.closest('.torrent-fetch-torrent');
            if (torrentBtn) {
                e.preventDefault();
                this.fetchDetails(parseInt(torrentBtn.dataset.index), torrentBtn, 'torrent');
                return;
            }

            const engineBtn = e.target.closest('.torrent-engine-btn');
            if (engineBtn) {
                e.preventDefault();
                this.el.querySelectorAll('.torrent-engine-btn').forEach(b => b.classList.remove('active'));
                engineBtn.classList.add('active');
                this.currentEngine = engineBtn.dataset.engine;
                this.doSearch();
                return;
            }

            const sortBtn = e.target.closest('.torrent-sort');
            if (sortBtn) {
                e.preventDefault();
                this.toggleSort(sortBtn.dataset.sort);
                return;
            }

            const qualityBtn = e.target.closest('.quality-btn');
            if (qualityBtn) {
                e.preventDefault();
                this.toggleQuality(qualityBtn);
                return;
            }
        });
    }

    renderResults(html) {
        if (!this.resultsBody) return;
        this.resultsBody.innerHTML = html;
        // No need to rebind events!
    }

    getActiveEngine() {
        const activeBtn = this.el.querySelector('.torrent-engine-btn.active');
        return activeBtn ? activeBtn.dataset.engine : null;
    }

    toggleQuality(btn) {
        const quality = btn.dataset.quality;
        const currentQuery = this.searchInput.value.trim();

        const allQualities = Array.from(this.el.querySelectorAll('.quality-btn'))
            .map(b => b.dataset.quality);

        let cleanedQuery = currentQuery;
        allQualities.forEach(q => {
            cleanedQuery = cleanedQuery.replace(new RegExp('\\b' + q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\b', 'gi'), '');
        });
        cleanedQuery = cleanedQuery.replace(/\s+/g, ' ').trim();

        const wasActive = btn.classList.contains('active');

        this.el.querySelectorAll('.quality-btn').forEach(b => b.classList.remove('active'));

        if (!wasActive) {
            btn.classList.add('active');
            this.searchInput.value = cleanedQuery + ' ' + quality;
        } else {
            this.searchInput.value = cleanedQuery;
        }
    }

    async doSearch() {
        const query = this.searchInput?.value.trim();
        if (!query) return;

        const engine = this.getActiveEngine();
        this.currentEngine = engine;

        this.showStatus('searching');

        try {
            const params = new URLSearchParams({
                query: query,
                sortBy: this.sortField + '.' + (this.sortDesc ? 'd' : 'a'),
            });
            if (engine) params.set('engine', engine);

            const response = await fetch(this.searchRoute + '?' + params.toString(), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const data = await response.json();

            if (!response.ok || data.error) {
                this.showStatus('error', data.error || 'Search failed');
                return;
            }

            this.results = data.results || [];
            this.currentEngine = data.engine || engine;

            if (this.results.length === 0) {
                this.showStatus('empty');
            } else {
                this.renderResults(data.html); // Pass HTML
                this.showStatus('results');
                this.updateTitle();
            }
        } catch (error) {
            this.showStatus('error', error.message);
        }
    }

    updateTitle() {
        const header = this.el.querySelector('#torrent-dialog-header');
        if (header && this.titleTemplate) {
            // Replace :itemslength placeholder with actual count
            let newTitle = this.titleTemplate.replace(':itemslength', this.results.length);
            // If query exists, append it (optional, to match blade logic)
            const query = this.searchInput?.value.trim();
            if (query) {
                newTitle += ` <small>(${query})</small>`;
            }
            header.innerHTML = newTitle;
        }
    }

    toggleSort(field) {
        if (this.sortField === field) {
            this.sortDesc = !this.sortDesc;
        } else {
            this.sortField = field;
            this.sortDesc = (field !== 'releasename');

        }

        this.el.querySelectorAll('.torrent-sort').forEach(th => {
            const isMatch = th.dataset.sort === this.sortField;
            const span = th.querySelector('span.sortorder');

            // Reset others
            if (!isMatch) {
                if (span) span.className = 'sortorder';


            } else {
                if (span) {
                    span.className = 'sortorder';
                    if (!this.sortDesc) {
                        span.classList.add('reverse');
                    }
                }
            }
        });

        this.sortAndRender();
    }

    sortAndRender() {
        // Sort is passed server-side via sortBy param; this.sortField/sortDesc are already updated by toggleSort()
        this.doSearch();
    }

    async fetchDetails(index, linkEl, type) {
        const result = this.results[index];
        if (!result) return;

        const iconEl = linkEl.querySelector('i');
        if (iconEl) {
            iconEl.className = 'glyphicon glyphicon-refresh';
            iconEl.style.animation = 'torrent-spin 1s linear infinite';
            iconEl.style.color = '#5bc0de';
            if (!document.getElementById('torrent-spin-style')) {
                const style = document.createElement('style');
                style.id = 'torrent-spin-style';
                style.textContent = '@keyframes torrent-spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }';
                document.head.appendChild(style);
            }
        }

        try {
            const response = await fetch(this.detailsRoute, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    engine: this.currentEngine,
                    url: result.detailUrl,
                    releasename: result.releasename,
                }),
            });

            const data = await response.json();

            // Update result with fetched data
            if (data.magnetUrl) result.magnetUrl = data.magnetUrl;
            if (data.torrentUrl) result.torrentUrl = data.torrentUrl;
            if (data.infoHash) result.infoHash = data.infoHash;

            if ((type === 'magnet' && result.magnetUrl) || (type === 'torrent' && result.torrentUrl)) {
                // Update the row's add-button data attrs in place so the delegation handler picks them up
                const row = linkEl.closest('tr');
                if (row) {
                    const addBtn = row.querySelector('.torrent-add-client');
                    if (addBtn) {
                        if (result.magnetUrl) addBtn.dataset.magnet = result.magnetUrl;
                        if (result.torrentUrl) addBtn.dataset.url = result.torrentUrl;
                        if (result.infoHash) addBtn.dataset.infoHash = result.infoHash;
                    }
                }
                if (iconEl) {
                    iconEl.className = 'glyphicon glyphicon-ok';
                    iconEl.style.animation = '';
                    iconEl.style.color = '#5cb85c';
                }
                return;
            }

            if (!result.magnetUrl && !result.torrentUrl) {
                if (iconEl) {
                    iconEl.className = 'glyphicon glyphicon-remove';
                    iconEl.style.animation = '';
                    iconEl.style.color = '#d9534f';
                    setTimeout(() => {
                        if (iconEl) iconEl.style.color = '#aaa';
                    }, 2000);
                }
            }
        } catch (error) {
            if (iconEl) {
                iconEl.className = 'glyphicon glyphicon-remove';
                iconEl.style.animation = '';
                iconEl.style.color = '#d9534f';
            }
        }
    }

    async addTorrent(magnet, url, infoHash, releaseName) {
        if (!this.addRoute) {
            console.error('TorrentSearch: addRoute not configured');
            return;
        }


        const payload = {
            releaseName: releaseName,
            episode_id: this.episodeId
        };

        // If we have a magnet, just use that. It's the most reliable and doesn't require infoHash validation.
        if (magnet) {
            payload.magnet = magnet;
        } else {
            // If no magnet, we try URL. This requires infoHash by backend definition.
            payload.url = url;
            payload.infoHash = infoHash;
        }

        try {
            const response = await fetch(this.addRoute, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            });

            const data = await response.json();

            if (response.ok && data.success) {
                if (window.Toast) window.Toast.success(data.message || 'Torrent added successfully');
            } else {
                if (window.Toast) window.Toast.error(data.error || 'Failed to add torrent');
            }
        } catch (error) {
            if (window.Toast) window.Toast.error('Network error: ' + error.message);
        }
    }

    showStatus(state, message) {
        // Hide all first
        if (this.initialStateRow) this.initialStateRow.style.display = 'none';
        if (this.searchingRow) this.searchingRow.style.display = 'none';
        if (this.noResultsRow) this.noResultsRow.style.display = 'none';
        if (this.errorRow) this.errorRow.style.display = 'none';
        if (this.resultsHeader) this.resultsHeader.style.display = 'none';
        if (this.resultsBody) this.resultsBody.style.display = 'none';

        // Show specific state
        switch (state) {
            case 'searching':
                if (this.searchingRow) this.searchingRow.style.display = '';
                break;
            case 'empty':
                if (this.noResultsRow) this.noResultsRow.style.display = '';
                if (this.noResultsQuery && this.searchInput) this.noResultsQuery.innerText = this.searchInput.value;
                break;
            case 'error':
                if (this.errorRow) this.errorRow.style.display = '';
                if (this.errorMsg) this.errorMsg.textContent = message || 'Unknown error';
                break;
            case 'results':
                if (this.resultsHeader) this.resultsHeader.style.display = '';
                if (this.resultsBody) this.resultsBody.style.display = '';
                break;
            case 'initial':
                if (this.initialStateRow) this.initialStateRow.style.display = '';
                break;
        }
    }

    escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
}

/** @type {TorrentSearch|null} Currently active dialog instance */
TorrentSearch._instance = null;
