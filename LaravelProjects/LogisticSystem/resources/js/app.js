import 'bootstrap';
import 'bootstrap-icons/font/bootstrap-icons.css';
import 'datatables.net-bs5';
import 'select2';

import $ from 'jquery';
window.$ = window.jQuery = $;

import Swal from 'sweetalert2';
window.Swal = Swal;

import Chart from 'chart.js/auto';
window.Chart = Chart;

$(document).ready(function() {
    $('.select2').select2({
        theme: 'bootstrap-5'
    });

    $('.data-table').DataTable({
        responsive: true,
        language: {
            search: "Search:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "Showing 0 to 0 of 0 entries",
            zeroRecords: "No matching records found"
        }
    });

    $('[data-confirm]').on('click', function(e) {
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
