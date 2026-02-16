
/**
 * BackgroundRotator Component
 *
 * Automatically rotates the application background by fetching random series fanart.
 * Ported from Angular directive to Vanilla JS.
 */
class BackgroundRotator {
    constructor(config = {}) {
        this.container = document.querySelector('background-rotator .background-image-container');
        this.details = document.querySelector('background-rotator .background-details');

        if (!this.container) return;

        // Matches original: .placeholder, div(bg1), div(bg2)
        this.placeholder = this.container.children[0];
        this.bg1 = this.container.children[1];
        this.bg2 = this.container.children[2];

        this.activeLayer = 0; // 0 = none, 1 = bg1, 2 = bg2
        this.interval = config.interval || 30000;
        this.route = config.route;

        if (!this.route) return;

        this.init();
    }

    init() {
        this.rotateBackground();
        setInterval(() => this.rotateBackground(), this.interval);
    }

    async rotateBackground() {
        try {
            const response = await fetch(this.route);
            if (!response.ok) return;

            const data = await response.json();
            const img = new Image();

            img.onload = () => {
                // Determine target layer (toggle between 1 and 2)
                // If currently bg1 (activeLayer=1), load into bg2. Else load into bg1.
                const target = this.activeLayer === 1 ? this.bg2 : this.bg1;
                const current = this.activeLayer === 1 ? this.bg1 : this.bg2;

                target.style.backgroundImage = `url('${data.fanart}')`;

                // Add active class to target, remove from others
                target.classList.add('active');
                current.classList.remove('active');
                this.placeholder.classList.remove('active');

                // Update text details
                if (this.details) {
                    const name = data.name;
                    const year = data.year;
                    this.details.innerText = year ? `${name} (${year})` : name;
                    this.details.style.display = 'block';
                }

                // Flip state
                this.activeLayer = this.activeLayer === 1 ? 2 : 1;
            };

            img.src = data.fanart;

        } catch (e) {
            console.error('BackgroundRotator error:', e);
        }
    }
}

