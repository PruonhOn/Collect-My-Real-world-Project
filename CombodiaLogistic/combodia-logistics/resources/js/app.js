import './bootstrap';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import Chart from 'chart.js/auto';
import 'datatables.net-bs5';
import Swal from 'sweetalert2';
import $ from 'jquery';

window.$ = $;
window.jQuery = $;
window.Chart = Chart;
window.Swal = Swal;

// Theme toggle
const themeToggle = document.getElementById('theme-toggle');
if (themeToggle) {
    themeToggle.addEventListener('click', () => {
        const html = document.documentElement;
        const currentTheme = html.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        themeToggle.innerHTML = newTheme === 'dark' ? '<i class="bi bi-sun"></i>' : '<i class="bi bi-moon"></i>';
    });
}

// Restore theme
const savedTheme = localStorage.getItem('theme') || 'light';
document.documentElement.setAttribute('data-theme', savedTheme);
if (themeToggle) {
    themeToggle.innerHTML = savedTheme === 'dark' ? '<i class="bi bi-sun"></i>' : '<i class="bi bi-moon"></i>';
}

// Sidebar toggle
const sidebarToggle = document.getElementById('sidebar-toggle');
const sidebar = document.querySelector('.sidebar');
const mainContent = document.querySelector('.main-content');

if (sidebarToggle && sidebar && mainContent) {
    sidebarToggle.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('expanded');
    });
}

// Auto-init tooltips
document.addEventListener('DOMContentLoaded', () => {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(el => new bootstrap.Tooltip(el));
});

// Global AJAX setup
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    }
});

// Global SweetAlert confirm delete
window.confirmDelete = function(url, message = 'Are you sure?') {
    Swal.fire({
        title: message,
        text: 'This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef476f',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: url,
                type: 'DELETE',
                success: function(response) {
                    Swal.fire('Deleted!', response.message || 'Record has been deleted.', 'success');
                    if (response.reload) location.reload();
                    if (response.redirect) window.location.href = response.redirect;
                },
                error: function(xhr) {
                    Swal.fire('Error!', xhr.responseJSON?.message || 'Something went wrong.', 'error');
                }
            });
        }
    });
};

// Toast notification
window.showToast = function(type = 'success', message = 'Operation successful!') {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
    });
    Toast.fire({ icon: type, title: message });
};
