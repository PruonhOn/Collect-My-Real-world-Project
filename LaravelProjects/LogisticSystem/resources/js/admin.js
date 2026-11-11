import 'bootstrap';
import 'bootstrap-icons/font/bootstrap-icons.css';
import '../css/app.css';
import 'datatables.net-bs5';
import 'select2';

import $ from 'jquery';
window.$ = window.jQuery = $;

import Swal from 'sweetalert2';
window.Swal = Swal;

import Chart from 'chart.js/auto';
window.Chart = Chart;

$(document).ready(function () {
    $('.select2').select2({
        theme: 'bootstrap-5'
    });

    $('.data-table').DataTable({
        responsive: true,
        language: {
            search: 'Search:',
            lengthMenu: 'Show _MENU_ entries',
            info: 'Showing _START_ to _END_ of _TOTAL_ entries',
            infoEmpty: 'Showing 0 to 0 of 0 entries',
            zeroRecords: 'No matching records found'
        }
    });

    $('[data-confirm]').on('click', function (e) {
        e.preventDefault();
        const message = $(this).data('confirm') || 'Are you sure?';
        const href = $(this).attr('href');
        Swal.fire({
            title: 'Confirm',
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = href;
            }
        });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mobileToggle = document.getElementById('sidebarMobileToggle');
    const sidebarClose = document.getElementById('sidebarClose');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const darkModeToggle = document.getElementById('darkModeToggle');

    function isMobile() {
        return window.innerWidth < 992;
    }

    function restoreSidebar() {
        if (!isMobile()) {
            const saved = localStorage.getItem('sidebarCollapsed');
            if (saved === 'true') {
                sidebar.classList.add('collapsed');
                if (mainContent) mainContent.classList.add('expanded');
            }
        }
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('collapsed');
            if (mainContent) mainContent.classList.toggle('expanded');
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        });
    }

    function showMobileSidebar() {
        sidebar.classList.add('show-mobile');
        if (sidebarOverlay) sidebarOverlay.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function hideMobileSidebar() {
        sidebar.classList.remove('show-mobile');
        if (sidebarOverlay) sidebarOverlay.classList.remove('show');
        document.body.style.overflow = '';
    }

    if (mobileToggle) {
        mobileToggle.addEventListener('click', showMobileSidebar);
    }
    if (sidebarClose) {
        sidebarClose.addEventListener('click', hideMobileSidebar);
    }
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', hideMobileSidebar);
    }

    window.addEventListener('resize', function () {
        if (!isMobile()) {
            hideMobileSidebar();
            restoreSidebar();
        } else {
            sidebar.classList.remove('collapsed');
            if (mainContent) mainContent.classList.remove('expanded');
        }
    });

    restoreSidebar();

    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', function () {
            const html = document.documentElement;
            const current = html.getAttribute('data-bs-theme');
            const next = current === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-bs-theme', next);
            localStorage.setItem('theme', next);
            this.innerHTML = next === 'dark'
                ? '<i class="bi bi-sun-fill fs-5"></i>'
                : '<i class="bi bi-moon-fill fs-5"></i>';
        });
    }

    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-bs-theme', savedTheme);
    if (darkModeToggle) {
        darkModeToggle.innerHTML = savedTheme === 'dark'
            ? '<i class="bi bi-sun-fill fs-5"></i>'
            : '<i class="bi bi-moon-fill fs-5"></i>';
    }

    setTimeout(function () {
        document.querySelectorAll('.alert-dismissible').forEach(function (el) {
            el.style.transition = 'opacity 0.5s';
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 500);
        });
    }, 5000);

    document.querySelectorAll('.nav-submenu .nav-link').forEach(function (link) {
        link.addEventListener('click', function () {
            if (isMobile()) {
                hideMobileSidebar();
            }
        });
    });
});
