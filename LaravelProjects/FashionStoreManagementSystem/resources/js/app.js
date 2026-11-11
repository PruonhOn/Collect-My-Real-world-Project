import './bootstrap';
import './theme';
import '../css/app.css';

import * as bootstrap from 'bootstrap';

window.bootstrap = bootstrap;

document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('appSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    const toggle = document.getElementById('sidebarToggle');

    if (toggle && sidebar && backdrop) {
        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            backdrop.classList.toggle('show');
        });
        backdrop.addEventListener('click', () => {
            sidebar.classList.remove('open');
            backdrop.classList.remove('show');
        });
    }

    window.showToast = function (message, type = 'success') {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const icons = {
            success: 'bi-check-circle-fill text-success',
            error: 'bi-x-circle-fill text-danger',
            warning: 'bi-exclamation-triangle-fill text-warning',
            info: 'bi-info-circle-fill text-primary',
        };

        const toast = document.createElement('div');
        toast.className = 'toast align-items-center show';
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center gap-2">
                    <i class="bi ${icons[type] || icons.info}"></i>
                    <span>${message}</span>
                </div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>`;
        container.appendChild(toast);

        const instance = new bootstrap.Toast(toast, { delay: 4200 });
        instance.show();
        toast.addEventListener('hidden.bs.toast', () => toast.remove());
    };

    if (window.FlashMessages) {
        if (window.FlashMessages.success) window.showToast(window.FlashMessages.success, 'success');
        if (window.FlashMessages.error) window.showToast(window.FlashMessages.error, 'error');
        if (window.FlashMessages.warning) window.showToast(window.FlashMessages.warning, 'warning');
        if (window.FlashMessages.info) window.showToast(window.FlashMessages.info, 'info');
        if (window.FlashMessages.errors && window.FlashMessages.errors.length) {
            window.FlashMessages.errors.forEach(err => window.showToast(err, 'error'));
        }
    }
});
