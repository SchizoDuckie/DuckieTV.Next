window.BackupRestore = {
    pollingInterval: null,
    lastLogCount: 0,
    selectedFile: null,
    progressModal: null,
    miniProgress: null,
    isMinimized: false,
    status: 'idle',
    i18n: {},
    posters: [],
    failedSeries: [],
    lastLogCount: 0,
    lastFailedCount: 0,

    init: function (i18n = {}) {
        this.i18n = i18n;
        console.log('BackupRestore: init');
        // Check if a restore is already in progress
        this.checkExistingRestore();
    },

    checkExistingRestore: function () {
        fetch('/settings/restore/progress')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'running' || data.status === 'extracting') {
                    console.log('BackupRestore: Ongoing restore detected');
                    this.status = data.status;
                    // Default to minimized view on page load if it's already running
                    this.showMiniProgress();
                    this.startPolling();
                }
            })
            .catch(err => console.error('Restore check error:', err));
    },

    upload: function (input) {
        if (!input.files.length) return;
        this.selectedFile = input.files[0];

        const wipe = document.getElementById('wipebeforeImport').checked;
        let msg = `<p>${this.i18n['BACKUPCTRLjs/restore/intro'] || 'Are you sure you want to restore this backup?'}</p>`;

        if (wipe) {
            msg += `<p><strong>${this.i18n['BACKUPCTRLjs/restore/wipe-warn'] || 'This will wipe your current database before restoring!'}</strong></p>`;
        } else {
            msg += `<p>${this.i18n['BACKUPCTRLjs/restore/merge-info'] || 'This will merge the backup with your current data.'}</p>`;
        }

        Modal.confirm(this.i18n['BACKUPCTRLjs/restore/confirm-hdr'] || 'Restore Backup', msg, () => {
            this.proceedWithRestore();
        }, () => {
            this.cancelRestore();
        });
    },

    proceedWithRestore: function () {
        if (!this.selectedFile) {
            alert(this.i18n['COMMON/error/hdr'] || 'No file selected.');
            return;
        }

        // Show Progress Modal
        this.showDetailedProgress();

        let formData = new FormData();
        formData.append('backup_file', this.selectedFile);
        formData.append('wipe', document.getElementById('wipebeforeImport').checked ? '1' : '0');

        const tokenMeta = document.querySelector('meta[name="csrf-token"]');

        fetch('/settings/restore', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': tokenMeta ? tokenMeta.getAttribute('content') : ''
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.status = 'running';
                    this.startPolling();
                } else {
                    alert((this.i18n['BACKUPCTRLjs/progress/restore-failed'] || 'Restore Failed: ') + data.message);
                    if (this.progressModal) this.progressModal.hide();
                    this.clearInput();
                }
            })
            .catch(error => {
                alert((this.i18n['BACKUPCTRLjs/progress/restore-failed'] || 'Restore Failed: ') + error);
                if (this.progressModal) this.progressModal.hide();
                this.clearInput();
            });
    },

    showDetailedProgress: function () {
        this.isMinimized = false;
        if (this.miniProgress) {
            this.miniProgress.remove();
            this.miniProgress = null;
        }

        const template = document.getElementById('restore-progress-modal-template');
        const content = template.content.cloneNode(true);

        this.progressModal = Modal.wait(
            this.i18n['BACKUPCTRLjs/progress/hdr'] || 'Restoring Backup...',
            content,
            0,
            {
                minimizable: true,
                onMinimize: () => this.minimize()
            }
        );
        // Refresh UI with current state if available
        this.refreshProgress();

        // Wire Stop button
        const stopBtn = this.progressModal.el.querySelector('.btn-stop-restore');
        if (stopBtn) {
            stopBtn.addEventListener('click', () => {
                if (confirm('Are you sure you want to stop the restore? This will leave the current queue as is.')) {
                    this.requestCancellation();
                }
            });
        }
    },

    showMiniProgress: function () {
        this.isMinimized = true;
        if (this.progressModal) {
            this.progressModal.hide();
            this.progressModal = null;
        }

        const template = document.getElementById('restore-progress-mini-template');
        const content = template.content.cloneNode(true);

        document.body.appendChild(content);
        this.miniProgress = document.body.lastElementChild;

        this.miniProgress.addEventListener('click', () => this.maximize());
        // Refresh UI with current state if available
        this.refreshProgress();
    },

    minimize: function () {
        this.showMiniProgress();
    },

    maximize: function () {
        this.showDetailedProgress();
    },

    refreshProgress: function () {
        fetch('/settings/restore/progress')
            .then(response => response.json())
            .then(data => this.updateUI(data));
    },

    requestCancellation: function () {
        fetch('/settings/restore/cancel', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Restore cancellation requested');
                } else {
                    alert('Failed to cancel restore: ' + data.message);
                }
            })
            .catch(err => console.error('Error cancelling restore:', err));
    },

    cancelRestore: function () {
        this.clearInput();
        this.selectedFile = null;
    },

    clearInput: function () {
        const input = document.getElementById('backupInput');
        if (input) input.value = '';
    },

    startPolling: function () {
        this.lastLogCount = 0;
        this.lastFailedCount = 0;
        this.posters = [];
        this.failedSeries = [];

        if (this.pollingInterval) clearInterval(this.pollingInterval);

        this.pollingInterval = setInterval(() => {
            fetch('/settings/restore/progress')
                .then(response => response.json())
                .then(data => {
                    this.updateUI(data);

                    if (data.status === 'completed' || data.status === 'failed' || data.status === 'cancelled') {
                        clearInterval(this.pollingInterval);
                        this.finishRestore(data);
                    }
                })
                .catch(err => console.error('Polling error:', err));
        }, 1000);
    },

    updateUI: function (data) {
        // Track unique posters
        if (data.poster && !this.posters.includes(data.poster)) {
            this.posters.push(data.poster);
            if (this.posters.length > 20) this.posters.shift();
        }

        // Track failed series
        if (data.failed_series && data.failed_series.length > this.failedSeries.length) {
            this.failedSeries = data.failed_series;
        }

        if (this.isMinimized) {
            this.updateMiniUI(data);
        } else {
            this.updateDetailedUI(data);
        }
    },

    updateDetailedUI: function (data) {
        if (!this.progressModal || !this.progressModal.el) return;

        const percent = data.percent || 0;
        const mainBar = this.progressModal.el.querySelector('.main-progress-bar');
        const statusText = this.progressModal.el.querySelector('.restore-status-text');
        const showProgressDiv = this.progressModal.el.querySelector('.restore-show-progress');
        const logContainer = this.progressModal.el.querySelector('.restore-logs');
        const thumbnailTrack = this.progressModal.el.querySelector('.restore-thumbnails-track');
        const failuresContainer = this.progressModal.el.querySelector('.restore-failures-container');
        const failuresList = this.progressModal.el.querySelector('.restore-failed-items');

        if (mainBar) mainBar.style.width = percent + '%';

        let statusMsg = `${this.i18n['COMMON/loading-please-wait/lbl'] || 'Processing...'} ${percent}%`;
        if (data.status === 'extracting') statusMsg = this.i18n['BACKUPCTRLjs/progress/extracting'] || 'Extracting backup file...';
        if (statusText) statusText.innerHTML = statusMsg;

        if (data.show && data.type === 'show_progress') {
            if (showProgressDiv) {
                showProgressDiv.style.display = 'block';
                const showTitle = showProgressDiv.querySelector('.restore-show-text');
                const showBar = showProgressDiv.querySelector('.show-progress-bar');

                let showMsg = `${this.i18n['COMMON/searching/lbl'] || 'Restoring'}: <strong>${data.show}</strong>`;
                if (data.season) showMsg += ` (${this.i18n['COMMON/season/lbl'] || 'Season'} ${data.season})`;

                if (showTitle) showTitle.innerHTML = showMsg;
                if (showBar) showBar.style.width = (data.percent || 0) + '%';
            }
        } else if (data.status === 'running') {
            // Keep current show if just global percent update
        } else {
            if (showProgressDiv) showProgressDiv.style.display = 'none';
        }

        // Update Thumbnails
        if (thumbnailTrack) {
            const currentImgCount = thumbnailTrack.querySelectorAll('img').length;
            if (this.posters.length > currentImgCount) {
                for (let i = currentImgCount; i < this.posters.length; i++) {
                    const img = document.createElement('img');
                    img.src = this.posters[i];
                    img.style.height = '100px';
                    img.style.border = '1px solid #444';
                    img.style.borderRadius = '3px';
                    img.style.boxShadow = '0 2px 5px rgba(0,0,0,0.5)';
                    thumbnailTrack.appendChild(img);
                }
                // Scroll to end
                const offset = Math.max(0, thumbnailTrack.scrollWidth - thumbnailTrack.parentElement.clientWidth);
                thumbnailTrack.style.left = `-${offset}px`;
            }
        }

        // Update Failures
        if (this.failedSeries.length > 0 && failuresContainer && failuresList) {
            failuresContainer.style.display = 'block';
            if (this.failedSeries.length > this.lastFailedCount) {
                for (let i = this.lastFailedCount; i < this.failedSeries.length; i++) {
                    const li = document.createElement('li');
                    li.innerHTML = `<strong>${this.failedSeries[i].time}</strong>: ${this.failedSeries[i].id} - ${this.failedSeries[i].error}`;
                    failuresList.appendChild(li);
                }
                this.lastFailedCount = this.failedSeries.length;
                failuresList.scrollTop = failuresList.scrollHeight;
            }
        }

        // Update Logs
        if (data.logs && data.logs.length > this.lastLogCount && logContainer) {
            for (let i = this.lastLogCount; i < data.logs.length; i++) {
                const logLine = document.createElement('div');
                logLine.textContent = data.logs[i];
                logContainer.appendChild(logLine);
            }
            this.lastLogCount = data.logs.length;
            logContainer.scrollTop = logContainer.scrollHeight;
        }
    },

    updateMiniUI: function (data) {
        if (!this.miniProgress) return;

        const percent = data.percent || 0;
        const mainBar = this.miniProgress.querySelector('.main-progress-bar');
        const statusText = this.miniProgress.querySelector('.mini-status-text');
        const percentLabel = this.miniProgress.querySelector('.percent-label');
        const thumbnailTrackMini = this.miniProgress.querySelector('.restore-thumbnails-track-mini');

        if (mainBar) mainBar.style.width = percent + '%';
        if (percentLabel) percentLabel.textContent = percent + '%';

        let statusMsg = this.i18n['COMMON/loading-please-wait/lbl'] || 'Processing...';
        if (data.show && data.type === 'show_progress') {
            statusMsg = `${this.i18n['COMMON/searching/lbl'] || 'Restoring'}: ${data.show}`;
        } else if (data.status === 'extracting') {
            statusMsg = this.i18n['BACKUPCTRLjs/progress/extracting'] || 'Extracting...';
        } else if (data.logs && data.logs.length > 0) {
            statusMsg = data.logs[data.logs.length - 1];
        }
        if (statusText) statusText.textContent = statusMsg;

        // Update Mini Thumbnails
        if (thumbnailTrackMini) {
            const currentImgCount = thumbnailTrackMini.querySelectorAll('img').length;
            if (this.posters.length > currentImgCount) {
                for (let i = currentImgCount; i < this.posters.length; i++) {
                    const img = document.createElement('img');
                    img.src = this.posters[i];
                    img.style.height = '40px';
                    img.style.borderRadius = '2px';
                    thumbnailTrackMini.appendChild(img);
                }
                thumbnailTrackMini.scrollLeft = thumbnailTrackMini.scrollWidth;
            }
        }
    },

    finishRestore: function (data) {
        this.status = data.status;

        // Ensure detailed view is visible for the final result
        if (this.isMinimized) {
            this.maximize();
        }

        if (data.status === 'completed') {
            const completeMsg = this.i18n['BACKUPCTRLjs/progress/restore-complete'] || 'Restore Complete! Reloading...';
            this.progressModal.updateProgress(completeMsg, 100);
            setTimeout(() => {
                window.location.reload();
            }, 3000);
        } else {
            const failedMsg = (this.i18n['BACKUPCTRLjs/progress/restore-failed'] || 'Restore Failed: ') + (data.message || '');
            this.progressModal.updateProgress(failedMsg, 100);
            setTimeout(() => {
                // Keep it open if there were failures so user can see them
                if (this.failedSeries.length === 0) {
                    this.progressModal.hide();
                    alert(failedMsg);
                }
            }, 500);
        }
    }
};
