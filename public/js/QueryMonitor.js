/**
 * QueryMonitor Component
 * 
 * Strictly replicates the original DuckieTV-angular QueryMonitor logic and UI.
 * Uses pre-rendered HTML from app.blade.php.
 * 
 * @see queryMonitor.js in DuckieTV-angular
 * @see templates/querymonitor.html in DuckieTV-angular
 */
window.QueryMonitor = {
    el: null,
    bar: null,
    count: null,
    timer: null,

    init: function () {
        this.el = document.getElementById('query-monitor');
        this.bar = document.getElementById('query-monitor-bar');
        this.count = document.getElementById('query-monitor-count');
    },

    /**
     * Start the monitor
     * @param {number} total Total number of items
     * @param {string} name Task name (e.g. "series")
     */
    start: function (total, name) {
        if (!this.el) this.init();
        if (this.timer) clearTimeout(this.timer);

        this.update(0, total, name);
        this.el.classList.add('show');
    },

    /**
     * Update progress
     * @param {number} current Current item index
     * @param {number} total Total number of items
     * @param {string} name Task name
     */
    update: function (current, total, name) {
        if (!this.el) this.init();

        const progress = total > 0 ? (current / total * 100) : 0;

        if (this.bar) this.bar.style.width = progress + '%';
        if (this.count) this.count.textContent = `${current}/${total} ${name || ''}`;

        // Prevent accidental closing during background tasks
        if (progress < 100) {
            window.onbeforeunload = () => 'Background task in progress...';
        } else {
            window.onbeforeunload = null;
        }
    },

    /**
     * Hide the monitor after a delay
     */
    finish: function () {
        if (!this.el) this.init();
        window.onbeforeunload = null;

        this.timer = setTimeout(() => {
            if (this.el) this.el.classList.remove('show');
        }, 1600);
    }
};

// Auto-init on script load
document.addEventListener('DOMContentLoaded', () => window.QueryMonitor.init());
