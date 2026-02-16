class Calendar {
    constructor() {
        console.log('Calendar: constructor');
        this.el = document.querySelector('calendar');
        if (!this.el) {
            console.error('Calendar: <calendar> element not found!');
            return;
        }

        this.datePicker = this.el.querySelector('[date-picker]');
        console.log('Calendar: initialized', this.el);
        this.init();
    }

    init() {
        // Observer to watch body classes for sidepanel state
        this.observer = new MutationObserver(() => this.zoom());
        this.observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });

        window.addEventListener('resize', () => this.zoom());
        this.zoom(); // Initial zoom

        // Listen for torrent updates to animate progress bars
        document.addEventListener('torrent-status-update', (e) => this.onTorrentUpdate(e));

        // Listen for back/forward browser navigation
        window.addEventListener('popstate', (e) => {
            if (e.state) {
                this.navigate(e.state.mode, e.state.date, false);
            }
        });
    }

    /**
     * Navigate to a different calendar view via XHR
     * @param {string} mode 
     * @param {string} date 
     * @param {boolean} pushState 
     */
    async navigate(mode, date, pushState = true) {
        console.log('Calendar: navigate', mode, date);
        const container = document.getElementById('calendar-content');
        if (!container) return; // Should not happen

        // Update URL
        const url = new URL(window.location.origin + '/calendar');
        url.searchParams.set('mode', mode);
        url.searchParams.set('date', date);

        try {
            // Fetch partial content
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html'
                }
            });

            if (!response.ok) throw new Error('Failed to load calendar');

            const html = await response.text();
            container.innerHTML = html;

            // Re-apply zoom as the new content needs to be scaled
            this.datePicker = this.el.querySelector('[date-picker]');
            this.zoom();

            if (pushState) {
                window.history.pushState({ mode, date }, '', url);
            }

            // Store current state for refresh
            this.currentMode = mode;
            this.currentDate = date;

        } catch (error) {
            console.error('Calendar: navigation failed', error);
            window.Toast.error('Failed to load calendar view.');
        }
    }

    /**
     * Refresh the current view
     */
    async refresh() {
        // If we haven't navigated yet, try to infer from URL or defaults
        if (!this.currentMode) {
            const params = new URLSearchParams(window.location.search);
            this.currentMode = params.get('mode') || 'month';
            this.currentDate = params.get('date') || new Date().toISOString().slice(0, 10);
        }
        return this.navigate(this.currentMode, this.currentDate, false);
    }

    /**
     * Handle real-time torrent progress updates
     * @param {CustomEvent} e 
     */
    onTorrentUpdate(e) {
        const data = e.detail;
        if (!data || !data.torrents) return;

        // Create a map of infoHash -> torrent data for O(1) lookup
        const torrentMap = {};
        data.torrents.forEach(t => {
            // Normalized upper case for comparison
            if (t.infoHash) torrentMap[t.infoHash.toUpperCase()] = t;
        });

        // Find all episode elements with a magnet hash (inside the calendar)
        const episodes = this.el.querySelectorAll('a[data-magnet-hash]');

        episodes.forEach(el => {
            const hash = el.dataset.magnetHash.toUpperCase();
            const bar = el.querySelector('.progress-bar');

            if (!bar) return;

            if (torrentMap[hash]) {
                const t = torrentMap[hash];
                const progress = parseFloat(t.progress || 0);

                bar.style.width = progress + '%';

                // Update class based on state matches legacy logic
                bar.className = 'progress-bar'; // Reset
                if (!t.isStarted && progress < 100) {
                    bar.classList.add('progress-bar-danger');
                } else if (t.isStarted && progress < 100) {
                    bar.classList.add('progress-bar-info');
                } else if (!t.isStarted && progress === 100) {
                    bar.classList.add('progress-bar-success');
                } else if (t.isStarted && progress === 100) {
                    bar.classList.add('progress-bar-warning');
                }
            }
        });
    }

    zoom() {
        if (!this.datePicker) return;

        const isShowing = document.body.classList.contains('sidepanelActive');
        const isExpanded = document.body.classList.contains('sidepanelExpanded');

        let spaceToTheRight = 0;
        if (isExpanded) {
            spaceToTheRight = 840;
        } else if (isShowing) {
            spaceToTheRight = 450;
        }

        const cw = document.body.clientWidth;
        const avail = cw - spaceToTheRight;
        const zoom = avail / cw;

        // console.log(`Calendar: zoom ${zoom} (space: ${spaceToTheRight})`);

        this.datePicker.style.transform = `scale(${zoom})`;
        this.datePicker.style.transformOrigin = 'top left'; // Ensure it scales from top-left

        if (zoom < 1) {
            this.datePicker.classList.add('zoom');
        } else {
            this.datePicker.classList.remove('zoom');
        }
    }
}
