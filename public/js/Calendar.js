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

        console.log(`Calendar: zoom ${zoom} (space: ${spaceToTheRight})`);

        this.datePicker.style.transform = `scale(${zoom})`;
        this.datePicker.style.transformOrigin = 'top left'; // Ensure it scales from top-left

        if (zoom < 1) {
            this.datePicker.classList.add('zoom');
        } else {
            this.datePicker.classList.remove('zoom');
        }
    }
}
