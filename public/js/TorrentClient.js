/**
 * TorrentClient Component
 *
 * Handles interaction with the torrent client control API.
 * Provides methods to start, pause, stop, and remove torrents.
 */
class TorrentClient {
    /**
     * @param {string} infoHash 
     * @returns {Promise<void>}
     */
    static async start(infoHash) {
        return this._action(infoHash, 'start');
    }

    /**
     * @param {string} infoHash 
     * @returns {Promise<void>}
     */
    static async pause(infoHash) {
        return this._action(infoHash, 'pause');
    }

    /**
     * @param {string} infoHash 
     * @returns {Promise<void>}
     */
    static async stop(infoHash) {
        return this._action(infoHash, 'stop');
    }

    /**
     * @param {string} infoHash 
     * @returns {Promise<void>}
     */
    static async remove(infoHash) {
        return this._action(infoHash, 'remove');
    }

    /**
     * Internal helper to execute a torrent action.
     * @param {string} infoHash 
     * @param {string} action 
     * @private
     */
    static async _action(infoHash, action) {
        const url = `/torrents/${infoHash}/${action}`;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                }
            });

            const data = await response.json();

            if (response.ok && data.success) {
                window.Toast.success(`Torrent ${action} successful`);
                // Auto-refresh the sidepanel content if it's the current one
                // Actually, the poll will handle it, but we can force it
            } else {
                window.Toast.error(data.error || `Failed to ${action} torrent`);
            }
        } catch (error) {
            window.Toast.error(`Network error: ${error.message}`);
        }
    }
}

window.TorrentClient = TorrentClient;
