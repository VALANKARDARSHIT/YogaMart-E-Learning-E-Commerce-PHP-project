<!-- Toast Notification Component -->
<style>
.toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 100000;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.toast {
    background: white;
    padding: 1rem 1.5rem;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 15px;
    min-width: 320px;
    max-width: 450px;
    transform: translateX(120%);
    transition: transform 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    border-left: 6px solid #ccc;
    position: relative;
    overflow: hidden;
}

.toast.show {
    transform: translateX(0);
}

.toast.success { border-left-color: #2ecc71; }
.toast.error { border-left-color: #ff4757; }
.toast.info { border-left-color: #3498db; }
.toast.warning { border-left-color: #f1c40f; }

.toast i { font-size: 1.4rem; }
.toast.success i { color: #2ecc71; }
.toast.error i { color: #ff4757; }
.toast.info i { color: #3498db; }
.toast.warning i { color: #f1c40f; }

.toast-message {
    flex: 1;
    font-size: 0.95rem;
    font-weight: 600;
    color: #2d3436;
}
</style>

<div id="toast-container" class="toast-container"></div>

<script>
window.showToast = function(message, type = 'info', duration = 5000) {
    const container = document.getElementById('toast-container');
    if (!container) return;
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    let icon = 'info-circle';
    if (type === 'success') icon = 'check-circle';
    if (type === 'error') icon = 'exclamation-circle';
    if (type === 'warning') icon = 'exclamation-triangle';
    
    toast.innerHTML = `
        <i class="fas fa-${icon}"></i>
        <div class="toast-message">${message}</div>
    `;
    
    container.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, duration);
};

// Also define showGlobalToast as it is used in some places
window.showGlobalToast = window.showToast;

// Override window.alert
window.alert = (msg) => {
    if (!msg) return;
    const sMsg = String(msg).toLowerCase();
    const type = (sMsg.includes('success') || sMsg.includes('successful')) ? 'success' : 
                 (sMsg.includes('error') || sMsg.includes('fail') || sMsg.includes('invalid') || sMsg.includes('required')) ? 'error' : 'info';
    window.showToast(msg, type);
};
</script>
