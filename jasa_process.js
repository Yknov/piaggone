// assets/js/jasa_process.js
// JavaScript untuk interaksi real-time pada fitur Proses Jasa

class JasaProcessManager {
    constructor(options = {}) {
        this.apiBaseUrl = options.apiBaseUrl || '../api/jasa_process_actions.php';
        this.refreshInterval = options.refreshInterval || 30000; // 30 detik
        this.autoRefresh = options.autoRefresh !== false;
        this.refreshTimer = null;
        
        this.init();
    }
    
    init() {
        console.log('JasaProcessManager initialized');
        
        // Setup event listeners
        this.setupEventListeners();
        
        // Start auto refresh jika enabled
        if (this.autoRefresh) {
            this.startAutoRefresh();
        }
        
        // Update running durations untuk proses yang sedang berjalan
        this.updateRunningDurations();
    }
    
    setupEventListeners() {
        // Quick start buttons
        document.querySelectorAll('[data-action="quick-start"]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const processId = e.target.closest('[data-process-id]').dataset.processId;
                this.quickStart(processId);
            });
        });
        
        // Quick complete buttons
        document.querySelectorAll('[data-action="quick-complete"]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const processId = e.target.closest('[data-process-id]').dataset.processId;
                this.quickComplete(processId);
            });
        });
        
        // Refresh button
        const refreshBtn = document.getElementById('refresh-processes');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                this.refreshProcesses();
            });
        }
    }
    
    async quickStart(processId) {
        if (!confirm('Mulai proses jasa ini sekarang?')) {
            return;
        }
        
        try {
            const response = await fetch(this.apiBaseUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=quick_start&process_id=${processId}`
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification('success', data.message);
                this.refreshProcesses();
            } else {
                this.showNotification('error', data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            this.showNotification('error', 'Terjadi kesalahan');
        }
    }
    
    async quickComplete(processId) {
        if (!confirm('Selesaikan proses jasa ini?')) {
            return;
        }
        
        try {
            const response = await fetch(this.apiBaseUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=quick_complete&process_id=${processId}`
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification('success', data.message);
                this.refreshProcesses();
            } else {
                this.showNotification('error', data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            this.showNotification('error', 'Terjadi kesalahan');
        }
    }
    
    async getStats() {
        try {
            const response = await fetch(`${this.apiBaseUrl}?action=get_stats`);
            const data = await response.json();
            
            if (data.success) {
                this.updateStatsDisplay(data.data);
            }
        } catch (error) {
            console.error('Error fetching stats:', error);
        }
    }
    
    updateStatsDisplay(stats) {
        // Update card statistik
        const statElements = {
            'total': document.getElementById('stat-total'),
            'waiting': document.getElementById('stat-waiting'),
            'in_progress': document.getElementById('stat-in-progress'),
            'completed': document.getElementById('stat-completed'),
            'cancelled': document.getElementById('stat-cancelled')
        };
        
        Object.keys(statElements).forEach(key => {
            const element = statElements[key];
            if (element && stats[key] !== undefined) {
                element.textContent = stats[key];
                
                // Animasi perubahan
                element.classList.add('stat-updated');
                setTimeout(() => {
                    element.classList.remove('stat-updated');
                }, 500);
            }
        });
    }
    
    async getActiveProcesses() {
        try {
            const response = await fetch(`${this.apiBaseUrl}?action=get_active_processes`);
            const data = await response.json();
            
            if (data.success) {
                console.log(`Active processes: ${data.count}`);
                this.updateBadgeCount(data.count);
                return data.data;
            }
        } catch (error) {
            console.error('Error fetching active processes:', error);
        }
        return [];
    }
    
    updateBadgeCount(count) {
        const badges = document.querySelectorAll('.process-count-badge');
        badges.forEach(badge => {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'inline-block' : 'none';
        });
    }
    
    async updateRunningDurations() {
        const durationElements = document.querySelectorAll('[data-process-running]');
        
        durationElements.forEach(async (element) => {
            const processId = element.dataset.processId;
            
            try {
                const response = await fetch(`${this.apiBaseUrl}?action=get_process_duration&process_id=${processId}`);
                const data = await response.json();
                
                if (data.success) {
                    element.textContent = data.duration.formatted;
                }
            } catch (error) {
                console.error('Error updating duration:', error);
            }
        });
        
        // Update setiap 1 detik untuk proses yang sedang berjalan
        setTimeout(() => this.updateRunningDurations(), 1000);
    }
    
    async getCustomerActiveProcess() {
        try {
            const response = await fetch(`${this.apiBaseUrl}?action=get_customer_active_process`);
            const data = await response.json();
            
            if (data.success && data.has_active) {
                this.updateCustomerTrackingDisplay(data.data);
            }
        } catch (error) {
            console.error('Error fetching customer process:', error);
        }
    }
    
    updateCustomerTrackingDisplay(process) {
        // Update timeline progress
        const progressBar = document.getElementById('process-progress-bar');
        if (progressBar) {
            let width = 25;
            let statusClass = 'bg-warning';
            
            if (process.status === 'in_progress') {
                width = 50;
                statusClass = 'bg-info progress-bar-striped progress-bar-animated';
            } else if (process.status === 'completed') {
                width = 100;
                statusClass = 'bg-success';
            }
            
            progressBar.className = `progress-bar ${statusClass}`;
            progressBar.style.width = `${width}%`;
        }
        
        // Update current duration jika ada
        if (process.current_duration) {
            const durationElement = document.getElementById('current-duration');
            if (durationElement) {
                durationElement.textContent = process.current_duration;
            }
        }
    }
    
    startAutoRefresh() {
        console.log(`Auto refresh started (${this.refreshInterval}ms)`);
        
        this.refreshTimer = setInterval(() => {
            this.refreshProcesses();
        }, this.refreshInterval);
    }
    
    stopAutoRefresh() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
            this.refreshTimer = null;
            console.log('Auto refresh stopped');
        }
    }
    
    async refreshProcesses() {
        console.log('Refreshing processes...');
        
        // Show loading indicator
        const refreshBtn = document.getElementById('refresh-processes');
        if (refreshBtn) {
            refreshBtn.disabled = true;
            refreshBtn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Refreshing...';
        }
        
        try {
            // Update stats
            await this.getStats();
            
            // Update active processes count
            await this.getActiveProcesses();
            
            // Jika customer, update tracking
            const role = document.body.dataset.userRole;
            if (role === 'customer') {
                await this.getCustomerActiveProcess();
            }
            
            // Update last refresh time
            const lastUpdateElement = document.getElementById('last-update-time');
            if (lastUpdateElement) {
                lastUpdateElement.textContent = new Date().toLocaleTimeString('id-ID');
            }
            
            this.showNotification('success', 'Data berhasil diperbarui', 2000);
        } catch (error) {
            console.error('Error refreshing:', error);
            this.showNotification('error', 'Gagal memperbarui data');
        } finally {
            // Restore button
            if (refreshBtn) {
                refreshBtn.disabled = false;
                refreshBtn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Refresh';
            }
        }
    }
    
    showNotification(type, message, duration = 3000) {
        // Cek apakah sudah ada container notifikasi
        let container = document.getElementById('notification-container');
        
        if (!container) {
            container = document.createElement('div');
            container.id = 'notification-container';
            container.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999;';
            document.body.appendChild(container);
        }
        
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
        notification.style.cssText = 'min-width: 300px; margin-bottom: 10px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        container.appendChild(notification);
        
        // Auto remove after duration
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 150);
        }, duration);
    }
}

// Initialize saat DOM ready
document.addEventListener('DOMContentLoaded', () => {
    // Cek apakah ada elemen yang memerlukan JasaProcessManager
    const needsManager = document.querySelector('[data-jasa-process]');
    
    if (needsManager) {
        const options = {
            refreshInterval: parseInt(needsManager.dataset.refreshInterval) || 30000,
            autoRefresh: needsManager.dataset.autoRefresh !== 'false'
        };
        
        window.jasaProcessManager = new JasaProcessManager(options);
        
        console.log('JasaProcessManager ready!');
    }
});

// CSS untuk animasi
const style = document.createElement('style');
style.textContent = `
    .stat-updated {
        animation: statPulse 0.5s ease;
    }
    
    @keyframes statPulse {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.1);
            color: #0d6efd;
        }
    }
    
    .spin {
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        from {
            transform: rotate(0deg);
        }
        to {
            transform: rotate(360deg);
        }
    }
`;
document.head.appendChild(style);
