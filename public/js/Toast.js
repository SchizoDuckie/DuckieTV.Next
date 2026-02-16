/**
 * Global Toast Notification Service for DuckieTV.Next
 * 
 * Replicates the original DuckieTV notification logic with a modern,
 * vanilla JS implementation. Supports multiple notification types (success, error, info).
 */
class Toast {
    /**
     * Initialize the toast container if it doesn't exist.
     */
    static init() {
        if (!document.getElementById('toast-container')) {
            const container = document.createElement('div');
            container.id = 'toast-container';
            container.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                display: flex;
                flex-direction: column;
                gap: 10px;
                pointer-events: none;
            `;
            document.body.appendChild(container);

            // Inject animations once
            if (!document.getElementById('toast-styles')) {
                const style = document.createElement('style');
                style.id = 'toast-styles';
                style.textContent = `
                    @keyframes toast-in {
                        from { transform: translateX(105%); opacity: 0; }
                        to { transform: translateX(0); opacity: 1; }
                    }
                    @keyframes toast-out {
                        from { transform: translateX(0); opacity: 1; }
                        to { transform: translateX(105%); opacity: 0; }
                    }
                    .toast-success { border-left: 4px solid #2d7a2d; }
                    .toast-error { border-left: 4px solid #a94442; }
                    .toast-info { border-left: 4px solid #245269; }
                `;
                document.head.appendChild(style);
            }
        }
    }

    /**
     * Show a success notification.
     * @param {string} message 
     * @param {number} duration 
     */
    static success(message, duration = 5000) {
        this.show(message, 'success', duration);
    }

    /**
     * Show an error notification.
     * @param {string} message 
     * @param {number} duration 
     */
    static error(message, duration = 8000) {
        this.show(message, 'error', duration);
    }

    /**
     * Show an info notification.
     * @param {string} message 
     * @param {number} duration 
     */
    static info(message, duration = 5000) {
        this.show(message, 'info', duration);
    }

    /**
     * Generic show method.
     * @param {string} message 
     * @param {string} type 'success'|'error'|'info'
     * @param {number} duration ms to stay visible
     */
    static show(message, type = 'info', duration = 5000) {
        this.init();
        const container = document.getElementById('toast-container');

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;

        // Colors palette
        const colors = {
            success: '#5cb85c',
            error: '#d9534f',
            info: '#5bc0de'
        };

        toast.style.cssText = `
            pointer-events: auto;
            min-width: 280px;
            max-width: 400px;
            padding: 14px 18px;
            border-radius: 4px;
            color: white;
            box-shadow: 0 6px 16px rgba(0,0,0,0.2);
            font-size: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            animation: toast-in 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
            background-color: ${colors[type] || colors.info};
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            line-height: 1.4;
        `;

        const text = document.createElement('span');
        text.textContent = message;
        toast.appendChild(text);

        const close = document.createElement('span');
        close.innerHTML = '&times;';
        close.style.cssText = 'cursor: pointer; margin-left: 15px; font-size: 20px; line-height: 1; opacity: 0.7;';
        close.onmouseover = () => close.style.opacity = '1';
        close.onmouseout = () => close.style.opacity = '0.7';
        close.onclick = () => this.remove(toast);
        toast.appendChild(close);

        container.appendChild(toast);

        if (duration > 0) {
            setTimeout(() => this.remove(toast), duration);
        }
    }

    /**
     * Animate out and remove a toast.
     * @param {HTMLElement} toast 
     */
    static remove(toast) {
        if (!toast.parentElement) return;
        toast.style.animation = 'toast-out 0.25s ease-in forwards';
        setTimeout(() => {
            if (toast.parentElement) toast.remove();
        }, 250);
    }
}

window.Toast = Toast;
