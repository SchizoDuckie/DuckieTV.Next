/**
 * Modal.js - A Vanilla JS Modal class to replicate DuckieTV-angular dialogs.main service
 * Mimics behavior of angular-dialog-service with Bootstrap 3 styles.
 */
window.Modal = class Modal {
    constructor(options = {}) {
        this.options = Object.assign({
            backdrop: true,
            keyboard: true,
            size: 'lg',
            windowClass: 'dialogs-default', // Default from dialogs.quacked.js
            id: null,
            minimizable: false,
            onMinimize: null
        }, options);

        this.el = null;
        this.backdrop = null;
    }

    /**
     * Show a generic modal.
     * @param {string} title HTML for the title (usually includes icon)
     * @param {string} content HTML content for the body
     * @param {string} footer HTML content for the footer
     * @param {string} headerClass CSS class for the header (e.g. dialog-header-wait)
     */
    show(title, content, footer = '', headerClass = 'modal-header') {
        const id = this.options.id || 'modal-' + Math.random().toString(36).substr(2, 9);

        let html = `
            <div class="modal fade" id="${id}" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-${this.options.size} ${this.options.windowClass}">
                    <div class="modal-content">
                        <div class="modal-header ${headerClass}">
                            <div class="modal-header-btns" style="float: right;">
                                ${this.options.minimizable ? '<button type="button" class="minimize" title="Minimize" style="background: none; border: none; color: #fff; opacity: 0.5; margin-right: 5px;"><span class="glyphicon glyphicon-minus"></span></button>' : ''}
                                ${this.options.keyboard ? '<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>' : ''}
                            </div>
                            <h4 class="modal-title">${title}</h4>
                        </div>
                        <div class="modal-body"></div>
                        <div class="modal-footer" style="display: none;"></div>
                    </div>
                </div>
            </div>
        `;

        // Create modal element
        const div = document.createElement('div');
        div.innerHTML = html.trim();
        this.el = div.firstChild;

        const body = this.el.querySelector('.modal-body');
        const footerEl = this.el.querySelector('.modal-footer');

        if (content instanceof HTMLElement || content instanceof DocumentFragment) {
            body.appendChild(content);
        } else {
            body.innerHTML = content;
        }

        if (footer) {
            footerEl.style.display = 'block';
            if (footer instanceof HTMLElement || footer instanceof DocumentFragment) {
                footerEl.appendChild(footer);
            } else {
                footerEl.innerHTML = footer;
            }
        }

        // Backdrop
        if (this.options.backdrop) {
            this.backdrop = document.createElement('div');
            this.backdrop.className = 'modal-backdrop fade';
            document.body.appendChild(this.backdrop);
            // Force reflow
            this.backdrop.offsetHeight;
            this.backdrop.classList.add('in');
        }

        // 0. Ensure element is direct child of body (avoids z-index/blur issues)
        document.body.appendChild(this.el);

        // 1. Display block to allow calculations
        this.el.style.display = 'block';
        document.body.classList.add('modal-open');

        // 2. Add 'in' class for transition
        setTimeout(() => {
            this.el.classList.add('in');
        }, 10);

        // Event listeners
        const closeBtns = this.el.querySelectorAll('[data-dismiss="modal"]');
        closeBtns.forEach(btn => btn.addEventListener('click', () => this.hide()));

        const minimizeBtn = this.el.querySelector('.minimize');
        if (minimizeBtn) {
            minimizeBtn.addEventListener('click', () => {
                if (this.options.onMinimize) {
                    this.options.onMinimize();
                }
            });
        }

        if (this.options.keyboard) {
            this.handleEsc = this.handleEsc.bind(this);
            document.addEventListener('keydown', this.handleEsc);
        }

        // Remove close button if header is 'dialog-header-wait' (user cannot minimize/close wait dialogs easily in original?)
        if (headerClass.includes('dialog-header-wait')) {
            const btn = this.el.querySelector('.modal-header .close');
            if (btn) btn.remove();
        }

        return this;
    }

    hide() {
        if (!this.el) return;

        this.el.classList.remove('in');
        if (this.backdrop) this.backdrop.classList.remove('in');
        document.body.classList.remove('modal-open');

        if (this.options.keyboard) {
            document.removeEventListener('keydown', this.handleEsc);
        }

        setTimeout(() => {
            if (this.el && this.el.parentNode) this.el.parentNode.removeChild(this.el);
            if (this.backdrop && this.backdrop.parentNode) this.backdrop.parentNode.removeChild(this.backdrop);
            this.el = null;
            this.backdrop = null;
        }, 300); // Wait for transition
    }

    handleEsc(e) {
        if (e.key === 'Escape') this.hide();
    }

    // --- Static Helpers mimicking dialogs.quacked.js ---

    static confirm(header, msg, yesCallback, noCallback) {
        const modal = new Modal({
            backdrop: 'static',
            keyboard: false
        });

        const titleHtml = `<span class="glyphicon glyphicon-check"></span> ${header}`;

        const footerHtml = `
            <button type="button" class="btn btn-default" id="modal-btn-yes">Yes</button>
            <button type="button" class="btn btn-primary" id="modal-btn-no">No</button>
        `;

        modal.show(titleHtml, msg, footerHtml, 'dialog-header-confirm');

        setTimeout(() => {
            const yesBtn = modal.el.querySelector('#modal-btn-yes');
            const noBtn = modal.el.querySelector('#modal-btn-no');

            if (yesBtn) yesBtn.addEventListener('click', () => {
                modal.hide();
                if (yesCallback) yesCallback();
            });

            if (noBtn) noBtn.addEventListener('click', () => {
                modal.hide();
                if (noCallback) noCallback();
            });
        }, 50);

        return modal;
    }

    static wait(header, msg, progress = 100, options = {}) {
        const modal = new Modal(Object.assign({
            backdrop: 'static',
            keyboard: false
        }, options));

        const titleHtml = `<span class="glyphicon glyphicon-time"></span> ${header}`;

        // Content from wait.html - detect if msg is a DOM object
        let contentHtml;
        if (msg instanceof HTMLElement || msg instanceof DocumentFragment) {
            contentHtml = document.createElement('div');
            contentHtml.className = 'wait-content';
            const p = document.createElement('p');
            p.appendChild(msg);
            contentHtml.appendChild(p);

            const progressDiv = document.createElement('div');
            progressDiv.className = 'progress progress-striped active';
            progressDiv.innerHTML = `<div class="progress-bar progress-bar-info" style="width: ${progress}%"></div>`;
            contentHtml.appendChild(progressDiv);
        } else {
            contentHtml = `
                <div class="wait-content">
                    <p>${msg}</p>
                    <div class="progress progress-striped active">
                        <div class="progress-bar progress-bar-info" style="width: ${progress}%"></div>
                    </div>
                </div>
            `;
        }

        modal.show(titleHtml, contentHtml, '', 'dialog-header-wait');
        return modal;
    }

    /**
     * Updates the content of an existing wait modal (custom method not in original but needed for progress updates)
     */
    updateProgress(msg, percent) {
        if (!this.el) return;
        const p = this.el.querySelector('.modal-body p');
        const bar = this.el.querySelector('.progress-bar');
        if (p) p.innerHTML = msg;
        if (bar) bar.style.width = percent + '%';
    }
}
