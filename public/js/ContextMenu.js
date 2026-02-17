/**
 * Custom Context Menu for DuckieTV.Next
 * Mimics the original DuckieTV context menu logic.
 */
(function () {
    'use strict';

    const ContextMenu = {
        menu: null,
        target: null,

        init() {
            // Scope to series-list: serieheaders only appear there
            const seriesList = document.querySelector('series-list');
            const target = seriesList || document;
            target.addEventListener('contextmenu', (e) => this.handleContextMenu(e));
            // Click/scroll dismiss are attached only while menu is open (see show/hide)
            this._dismissClick = () => this.hide();
            this._dismissScroll = () => this.hide();
        },

        handleContextMenu(e) {
            const serieHeader = e.target.closest('.serieheader');
            if (serieHeader) {
                e.preventDefault();
                this.target = serieHeader;
                this.show(e.pageX, e.pageY);
            } else {
                this.hide();
            }
        },

        show(x, y) {
            this.hide();
            document.addEventListener('click', this._dismissClick);
            window.addEventListener('scroll', this._dismissScroll);

            const serieId = this.target.getAttribute('data-serie-id');
            const serieName = this.target.getAttribute('data-serie-name');
            const displayCalendar = this.target.getAttribute('data-display-calendar') === '1';

            this.menu = document.createElement('div');
            this.menu.className = 'dropdown clearfix context-menu';
            this.menu.style.position = 'absolute';
            this.menu.style.left = x + 'px';
            this.menu.style.top = y + 'px';
            this.menu.style.zIndex = '10000';

            const ul = document.createElement('ul');
            ul.className = 'dropdown-menu';
            ul.style.display = 'block';

            const items = [
                { label: 'Mark all watched', action: 'mark_watched' },
                { label: 'Mark all downloaded', action: 'mark_downloaded' },
                { type: 'divider' },
                {
                    label: displayCalendar ? 'Hide from calendar' : 'Show on calendar',
                    action: 'toggle_calendar'
                },
                { label: 'Remove from favorites', action: 'remove', class: 'text-danger' }
            ];

            items.forEach(item => {
                const li = document.createElement('li');
                if (item.type === 'divider') {
                    li.className = 'divider';
                } else {
                    const a = document.createElement('a');
                    a.href = '#';
                    a.className = item.class || '';
                    a.textContent = item.label;
                    a.onclick = (e) => {
                        e.preventDefault();
                        this.handleAction(serieId, item.action);
                        this.hide();
                    };
                    li.appendChild(a);
                }
                ul.appendChild(li);
            });

            this.menu.appendChild(ul);
            document.body.appendChild(this.menu);

            // Adjust position if menu goes off screen
            const menuRect = this.menu.getBoundingClientRect();
            if (menuRect.right > window.innerWidth) {
                this.menu.style.left = (x - menuRect.width) + 'px';
            }
            if (menuRect.bottom > window.innerHeight) {
                this.menu.style.top = (y - menuRect.height) + 'px';
            }
        },

        hide() {
            if (this.menu) {
                this.menu.remove();
                this.menu = null;
                document.removeEventListener('click', this._dismissClick);
                window.removeEventListener('scroll', this._dismissScroll);
            }
        },

        async handleAction(id, action) {
            console.log(`Context Menu Action: ${action} on series ${id}`);

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            let url = `/series/${id}`;
            let method = 'PATCH';

            if (action === 'remove') {
                if (!confirm('Are you sure you want to remove this series from your favorites?')) return;
                method = 'DELETE';
            }

            try {
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: method === 'PATCH' ? JSON.stringify({ action: action }) : null
                });

                if (response.ok) {
                    if (action === 'remove') {
                        window.location.reload();
                    } else if (action === 'toggle_calendar') {
                        // Update local state or reload
                        window.location.reload();
                    } else {
                        // Success toast or similar
                        console.log('Action successful');
                        if (window.Toast) {
                            window.Toast.show('Series updated successfully');
                        }
                    }
                } else {
                    console.error('Action failed');
                }
            } catch (err) {
                console.error('Error performing action:', err);
            }
        }
    };

    if (document.readyState === 'loading') {
        window.addEventListener('DOMContentLoaded', () => ContextMenu.init());
    } else {
        ContextMenu.init();
    }

})();
