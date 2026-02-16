class DialGauge {
    /**
     * Updates an SVG DialGauge element.
     * @param {HTMLElement} gaugeEl 
     * @param {number} value 
     */
    static update(gaugeEl, value) {
        if (!gaugeEl) return;
        const min = parseFloat(gaugeEl.dataset.min || 0);
        let max = parseFloat(gaugeEl.dataset.max || 100);
        const relative = gaugeEl.dataset.relative === 'true';
        const color = gaugeEl.dataset.color || '#00FF00';
        const colorEnd = gaugeEl.dataset.colorEnd;

        if (relative) {
            while (value > max && max < 1000000000) {
                max *= 10;
            }
            gaugeEl.dataset.max = max;
        }

        const percent = (max > min) ? (value - min) / (max - min) : 0;
        const clampedPercent = Math.max(0, Math.min(1, percent));
        const dasharray = 232.478; // pi * 74 (original gauge radius)
        const dashoffset = dasharray * (1 - clampedPercent);

        const arc = gaugeEl.querySelector('.gauge-arc');
        if (arc) {
            arc.style.strokeDashoffset = dashoffset;
            arc.style.opacity = (value > 0 || !colorEnd) ? '1' : '0.1';

            if (colorEnd) {
                const c1 = this.hexToRgb(color);
                const c2 = this.hexToRgb(colorEnd);
                if (c1 && c2) {
                    const r = Math.round(c1.r + (c2.r - c1.r) * clampedPercent);
                    const g = Math.round(c1.g + (c2.g - c1.g) * clampedPercent);
                    const b = Math.round(c1.b + (c2.b - c1.b) * clampedPercent);
                    arc.style.stroke = `rgb(${r},${g},${b})`;
                }
            }
        }

        const valueEl = gaugeEl.querySelector('.dialgauge-value');
        if (valueEl) {
            this.tweenValue(valueEl, value, gaugeEl.dataset.prevValue || 0, colorEnd ? clampedPercent : null, color, colorEnd);
        }
        gaugeEl.dataset.prevValue = value;
    }

    /**
     * Smoothly animate a value change
     */
    static tweenValue(el, target, start, percent, color, colorEnd) {
        const duration = 1500; // Animation duration in ms
        const startTime = performance.now();
        const startVal = parseFloat(start);
        const diff = target - startVal;

        // Cancel previous animation if running
        if (el.dataset.animId) {
            cancelAnimationFrame(parseInt(el.dataset.animId));
        }

        const parent = el.closest('ng-dial-gauge');
        const max = parent ? parseFloat(parent.dataset.max || 100) : 100;
        const unitEl = parent ? parent.querySelector('.dialgauge-unit') : null;
        const titleEl = parent ? parent.querySelector('.dialgauge-title') : null;

        const animate = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const currentVal = startVal + (diff * progress);

            el.textContent = currentVal.toLocaleString(undefined, { minimumFractionDigits: 1, maximumFractionDigits: 1 });

            // Update label colors to match interpolated bar color
            if (colorEnd) {
                const c1 = this.hexToRgb(color);
                const c2 = this.hexToRgb(colorEnd);
                if (c1 && c2) {
                    const currentPercent = currentVal / max;
                    const clampedP = Math.max(0, Math.min(1, currentPercent));
                    const r = Math.round(c1.r + (c2.r - c1.r) * clampedP);
                    const g = Math.round(c1.g + (c2.g - c1.g) * clampedP);
                    const b = Math.round(c1.b + (c2.b - c1.b) * clampedP);
                    const rgb = `rgb(${r},${g},${b})`;
                    if (unitEl) unitEl.style.fill = rgb;
                    if (titleEl) titleEl.style.fill = rgb;
                }
            }

            if (progress < 1) {
                el.dataset.animId = requestAnimationFrame(animate);
            } else {
                delete el.dataset.animId;
            }
        };

        el.dataset.animId = requestAnimationFrame(animate);
    }

    /**
     * Helper to convert HEX to RGB
     */
    static hexToRgb(hex) {
        const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        return result ? {
            r: parseInt(result[1], 16),
            g: parseInt(result[2], 16),
            b: parseInt(result[3], 16)
        } : null;
    }
}
