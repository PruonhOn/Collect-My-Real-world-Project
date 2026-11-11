@extends('layouts.admin')

@section('title', 'Backup')
@section('subtitle', 'System backup & restore')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Backup & Restore</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Backup</li>
            </ol>
        </nav>
    </div>
    <button type="button" class="btn btn-primary" onclick="createBackup()">
        <i class="bi bi-download me-1"></i> Create Backup
    </button>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex align-items-center">
            <div class="rounded bg-primary bg-opacity-10 p-3 me-3">
                <i class="bi bi-shield-check text-primary" style="font-size: 1.5rem;"></i>
            </div>
            <div>
                <h6 class="mb-1 fw-bold">Database Backup</h6>
                <p class="text-muted mb-0 small">Create a full backup of your database. This will export all tables and data.</p>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <h6 class="mb-0 fw-semibold">Existing Backups</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="dataTable">
                <thead class="table-light">
                    <tr>
                        <th>Filename</th>
                        <th>Size</th>
                        <th>Created Date</th>
                        <th>Created By</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($backups as $backup)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <i class="bi bi-file-earmark-zip text-primary me-2" style="font-size: 1.25rem;"></i>
                                <span class="fw-semibold">{{ $backup->filename }}</span>
                            </div>
                        </td>
                        <td>{{ $backup->size ?? 'N/A' }}</td>
                        <td>{{ $backup->created_at->format('d M Y, h:i A') }}</td>
                        <td>{{ $backup->createdBy->name ?? 'System' }}</td>
                        <td class="text-center">
                            <a href="{{ route('backup.download', $backup) }}" class="btn btn-sm btn-outline-primary" title="Download">
                                <i class="bi bi-download"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-warning" onclick="restoreBackup('{{ $backup->id }}', '{{ $backup->filename }}')" title="Restore">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteBackup('{{ $backup->id }}', '{{ $backup->filename }}')" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                            <p class="mt-2">No backups found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<style>
    .table th { font-weight: 600; font-size: 0.813rem; text-transform: uppercase; letter-spacing: 0.5px; color: #6c757d; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        $('#dataTable').DataTable({
            order: [[2, 'desc']],
            pageLength: 25,
            responsive: true
        });
    });

    function createBackup() {
        Swal.fire({
            title: 'Create Backup?',
            text: 'This may take a few minutes depending on database size.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0d6efd',
            confirmButtonText: 'Yes, Create Backup'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Creating Backup...',
                    text: 'Please wait while the backup is being created.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                fetch('{{ route("backup.store") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Success!', 'Backup created successfully.', 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', data.message || 'Failed to create backup.', 'error');
                    }
                })
                .catch(() => {
                    Swal.fire('Error', 'An error occurred while creating the backup.', 'error');
                });
            }
        });
    }

    function restoreBackup(id, filename) {
        Swal.fire({
            title: 'Restore Backup?',
            html: `This will <strong>replace all current data</strong> with the backup:<br><br><code>${filename}</code><br><br><strong class="text-danger">This action is irreversible!</strong>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, Restore',
            input: 'text',
            inputPlaceholder: 'Type "RESTORE" to confirm',
            inputValidator: (value) => {
                if (value !== 'RESTORE') {
                    return 'Please type RESTORE to confirm';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire('Restoring...', 'Backup is being restored.', 'info');

                fetch(`/admin/backup/${id}/restore`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Restored!', 'Backup has been restored successfully.', 'success');
                    } else {
                        Swal.fire('Error', data.message || 'Failed to restore backup.', 'error');
                    }
                });
            }
        });
    }

    function deleteBackup(id, filename) {
        Swal.fire({
            title: 'Delete Backup?',
            text: `Are you sure you want to delete ${filename}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, Delete'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/admin/backup/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Deleted!', 'Backup has been deleted.', 'success').then(() => {
                            location.reload();
                        });
                    }
                });
            }
        });
    }
</script>
@endpush
