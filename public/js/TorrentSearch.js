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
                addRoute: root.getAttribute('data-add-route')
            };

            // Extract Title (if exists)
            const titleEl = doc.querySelector('.modal-title');
            const title = titleEl ? titleEl.innerHTML : 'FIND TORRENT';

            // Extract Body (content inside modal-body)
            const bodyEl = doc.querySelector('.modal-body');
            // If body element has inline styles (likely), we might want to preserve a wrapper?
            // specific to TorrentSearch.
            // The template sends <div class="modal-body" ...> ... </div>
            // We want the INNER HTML of that body.
            const bodyContent = bodyEl ? bodyEl.innerHTML : '';

            // Note: The template's modal-header has specific styles. 
            // We are using the standard Modal header now. 
            // If we want to replicate the EXACT look, we might need a custom headerClass.

            const modal = new Modal({
                size: 'lg',
                windowClass: 'dialogs-default' // Standard look
            });

            // Show the modal
            modal.show(title, bodyContent);

            // Initialize the interactive search component
            // Pass the modal instance (wrapper) and the config
            TorrentSearch._instance = new TorrentSearch(modal, config);

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
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

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
        this.statusEl = this.el.querySelector('#torrent-search-status');
        this.errorEl = this.el.querySelector('#torrent-search-error');
        this.noResultsEl = this.el.querySelector('#torrent-no-results');
        this.resultsContainer = this.el.querySelector('#torrent-results-container');
        this.resultsBody = this.el.querySelector('#torrent-results-body');
    }

    bindEvents() {
        // Modal class handles 'close' buttons (data-dismiss="modal") automatically.
        // We just need to handle our custom logic.

        // Search button click
        if (this.searchBtn) {
            this.searchBtn.addEventListener('click', () => this.doSearch());
        }

        // Enter key in search input
        if (this.searchInput) {
            this.searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.doSearch();
                }
            });
        }

        // Engine selector buttons
        this.el.querySelectorAll('.torrent-engine-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                this.el.querySelectorAll('.torrent-engine-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
            });
        });

        // Quality filter buttons
        this.el.querySelectorAll('.quality-btn').forEach(btn => {
            btn.addEventListener('click', () => this.toggleQuality(btn));
        });

        // Sort header clicks
        this.el.querySelectorAll('.torrent-sort').forEach(th => {
            th.addEventListener('click', () => this.toggleSort(th.dataset.sort));
        });
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
                this.sortAndRender();
                this.showStatus('results');
            }
        } catch (error) {
            this.showStatus('error', error.message);
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
            th.classList.toggle('active', th.dataset.sort === this.sortField);
        });

        this.sortAndRender();
    }

    sortAndRender() {
        const field = this.sortField;
        const desc = this.sortDesc;

        this.results.sort((a, b) => {
            let valA = a[field];
            let valB = b[field];

            if (field === 'size') {
                valA = parseFloat(valA) || 0;
                valB = parseFloat(valB) || 0;
            }

            if (typeof valA === 'number' && typeof valB === 'number') {
                return desc ? valB - valA : valA - valB;
            }

            const strA = String(valA || '').toLowerCase();
            const strB = String(valB || '').toLowerCase();
            const cmp = strA.localeCompare(strB);
            return desc ? -cmp : cmp;
        });

        this.renderResults();
    }

    renderResults() {
        if (!this.resultsBody) return;

        this.resultsBody.innerHTML = this.results.map((result, index) => {
            const magnetIcon = result.noMagnet
                ? `<a href="javascript:void(0)" class="torrent-get-magnet" data-index="${index}" title="Fetch magnet link">
                       <i class="glyphicon glyphicon-magnet" style="color: #aaa;"></i>
                   </a>`
                : (result.magnetUrl || result.torrentUrl
                    ? `<a href="javascript:void(0)" class="torrent-add-client" data-index="${index}" title="Add to torrent client">
                           <i class="glyphicon glyphicon-magnet" style="color: #5bc0de;"></i>
                       </a>`
                    : '');

            return `<tr>
                <td>${magnetIcon}</td>
                <td class="releasename" title="${this.escapeHtml(result.releasename)}">${this.escapeHtml(result.releasename)}</td>
                <td style="text-align: right; white-space: nowrap;">${this.escapeHtml(result.size)}</td>
                <td style="text-align: right; color: #5cb85c;">${result.seeders}</td>
                <td style="text-align: right; color: #d9534f;">${result.leechers}</td>
            </tr>`;
        }).join('');

        this.resultsBody.querySelectorAll('.torrent-get-magnet').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const index = parseInt(link.dataset.index);
                this.fetchDetails(index, link);
            });
        });

        this.resultsBody.querySelectorAll('.torrent-add-client').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const index = parseInt(link.dataset.index);
                const result = this.results[index];
                this.addTorrent(result.magnetUrl, result.torrentUrl, result.infoHash, result.releasename);
            });
        });
    }

    async fetchDetails(index, linkEl) {
        const result = this.results[index];
        if (!result) return;

        const iconEl = linkEl.querySelector('i');
        if (iconEl) {
            iconEl.className = 'glyphicon glyphicon-refresh';
            iconEl.style.animation = 'torrent-spin 1s linear infinite';
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

            if (data.magnetUrl || data.torrentUrl) {
                result.magnetUrl = data.magnetUrl;
                result.noMagnet = false;
                if (data.torrentUrl) result.torrentUrl = data.torrentUrl;

                this.addTorrent(result.magnetUrl, result.torrentUrl, result.infoHash, result.releasename);

                linkEl.outerHTML = `<a href="javascript:void(0)" class="torrent-add-client" data-index="${index}" title="Add to torrent client">
                    <i class="glyphicon glyphicon-magnet" style="color: #5bc0de;"></i>
                </a>`;
            } else {
                if (iconEl) {
                    iconEl.className = 'glyphicon glyphicon-remove';
                    iconEl.style.animation = '';
                    iconEl.style.color = '#d9534f';
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

        try {
            const response = await fetch(this.addRoute, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    magnet: magnet,
                    url: url,
                    infoHash: infoHash,
                    releaseName: releaseName
                }),
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
        if (this.statusEl) this.statusEl.style.display = (state === 'searching') ? 'block' : 'none';
        if (this.errorEl) {
            this.errorEl.style.display = (state === 'error') ? 'block' : 'none';
            if (state === 'error') this.errorEl.textContent = message || 'Unknown error';
        }
        if (this.noResultsEl) this.noResultsEl.style.display = (state === 'empty') ? 'block' : 'none';
        if (this.resultsContainer) this.resultsContainer.style.display = (state === 'results') ? 'block' : 'none';
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
