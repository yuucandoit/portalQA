@extends('layouts.app')

@section('title', config('app.name', 'QA Portal') . ' - User Management')

@section('page_title', 'User & Access Management')
@section('page_subtitle', 'Kelola daftar pengguna, tetapkan hak akses Administrator, dan atur status akun')

@section('content')
    <!-- Alert Success -->
    @if (session('success'))
        <div class="alert-box alert-success mb-4">
            <div class="alert-content">
                <i class="fas fa-check-circle alert-icon"></i>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" class="alert-close" onclick="this.closest('.alert-box').remove()">&times;</button>
        </div>
    @endif

    <!-- Alert Errors -->
    @if (isset($errors) && $errors->any())
        <div class="alert-box alert-danger mb-4">
            <div class="alert-content">
                <i class="fas fa-exclamation-triangle alert-icon"></i>
                <div>
                    <strong>Perhatian:</strong>
                    <ul class="error-list">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <button type="button" class="alert-close" onclick="this.closest('.alert-box').remove()">&times;</button>
        </div>
    @endif

    <!-- ─── STATS SUMMARY CARDS ─────────────────────────────────────────── -->
    <div class="stats-grid mb-4">
        <div class="stat-card">
            <div class="stat-icon bg-blue-subtle text-blue">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-data">
                <span class="stat-label">Total Pengguna</span>
                <span class="stat-value">{{ $totalUsers }}</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon bg-purple-subtle text-purple">
                <i class="fas fa-user-shield"></i>
            </div>
            <div class="stat-data">
                <span class="stat-label">Administrator</span>
                <span class="stat-value">{{ $totalAdmins }}</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon bg-emerald-subtle text-emerald">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="stat-data">
                <span class="stat-label">Member Aktif</span>
                <span class="stat-value">{{ $totalActive }}</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon bg-amber-subtle text-amber">
                <i class="fas fa-user-slash"></i>
            </div>
            <div class="stat-data">
                <span class="stat-label">Nonaktif / Pending</span>
                <span class="stat-value">{{ $totalInactive }}</span>
            </div>
        </div>
    </div>

    <!-- ─── MAIN CONTENT CARD ────────────────────────────────────────────── -->
    <div class="content-card">
        <!-- Card Header & Filter Bar -->
        <div class="card-header user-header-bar">
            <div class="header-title-group">
                <h3 class="header-heading">
                    <i class="fas fa-users-cog text-purple mr-2"></i>Daftar Pengguna
                </h3>
                <span class="badge badge-counter">
                    Total: {{ $users->total() }} Akun
                </span>
            </div>

            <div class="header-actions-group">
                <!-- Search & Filters Form -->
                <form method="GET" action="{{ route('admin.users.index') }}" class="filters-form">
                    <!-- Search Input -->
                    <div class="search-input-wrap">
                        <i class="fas fa-search search-icon"></i>
                        <input 
                            type="text" 
                            name="search" 
                            value="{{ request('search') }}" 
                            placeholder="Cari nama atau email..." 
                            class="search-input"
                        />
                    </div>

                    <!-- Role Filter -->
                    <select name="role" class="filter-select" onchange="this.form.submit()">
                        <option value="">Semua Role</option>
                        <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Administrator</option>
                        <option value="member" {{ request('role') === 'member' ? 'selected' : '' }}>Member</option>
                    </select>

                    <!-- Status Filter -->
                    <select name="status" class="filter-select" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        <option value="Active" {{ request('status') === 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ request('status') === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>

                    <button type="submit" class="btn-filter-submit">
                        <i class="fas fa-filter"></i>
                    </button>

                    @if(request('search') || request('role') || request('status'))
                        <a href="{{ route('admin.users.index') }}" class="btn-filter-reset" title="Reset Filter">
                            <i class="fas fa-rotate-left"></i>
                        </a>
                    @endif
                </form>

                <!-- Add User Button -->
                <button type="button" class="btn-primary" onclick="openCreateModal()">
                    <i class="fas fa-user-plus mr-1"></i> Tambah User
                </button>
            </div>
        </div>

        <!-- Users Table -->
        <div class="table-responsive">
            <table class="user-table">
                <thead>
                    <tr>
                        <th style="width: 60px;">#</th>
                        <th>Pengguna</th>
                        <th>Hak Akses (Role)</th>
                        <th>Status Akun</th>
                        <th>Terdaftar</th>
                        <th style="width: 140px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $index => $u)
                        @php
                            $isCurrentUser = Auth::id() === $u->id;
                            $words = explode(' ', trim($u->name));
                            $initials = count($words) >= 2 
                                ? strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1))
                                : strtoupper(substr($u->name, 0, 2));
                        @endphp
                        <tr class="{{ $isCurrentUser ? 'row-current-user' : '' }}">
                            <!-- Index -->
                            <td class="text-muted" style="font-weight: 500; font-size: 13px;">
                                {{ $users->firstItem() + $index }}
                            </td>

                            <!-- User Info -->
                            <td>
                                <div class="user-cell">
                                    <div class="avatar-circle {{ $u->isAdmin() ? 'avatar-admin' : 'avatar-member' }}">
                                        {{ $initials }}
                                    </div>
                                    <div class="user-details">
                                        <div class="user-name">
                                            <strong>{{ $u->name }}</strong>
                                            @if($isCurrentUser)
                                                <span class="badge-you">Anda</span>
                                            @endif
                                        </div>
                                        <div class="user-email">
                                            <i class="fas fa-envelope mr-1 text-muted"></i>{{ $u->email }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Role & Quick Toggle -->
                            <td>
                                <div class="role-cell">
                                    @if($u->isAdmin())
                                        <span class="role-badge role-badge-admin" title="Memiliki akses penuh ke sistem & user management">
                                            <i class="fas fa-shield-alt"></i> Administrator
                                        </span>
                                    @else
                                        <span class="role-badge role-badge-member" title="Akses standar QA Portal">
                                            <i class="fas fa-user"></i> Member
                                        </span>
                                    @endif

                                    <!-- Quick Role Toggle Button -->
                                    @if(!$isCurrentUser)
                                        <form action="{{ route('admin.users.role', $u->id) }}" method="POST" style="display: inline-block;">
                                            @csrf
                                            <input type="hidden" name="role" value="{{ $u->isAdmin() ? 'member' : 'admin' }}">
                                            <button 
                                                type="submit" 
                                                class="btn-toggle-inline {{ $u->isAdmin() ? 'text-amber' : 'text-purple' }}" 
                                                title="{{ $u->isAdmin() ? 'Ubah menjadi Member' : 'Jadikan Administrator' }}"
                                                onclick="return confirm('Apakah Anda yakin ingin mengubah role {{ $u->name }} menjadi {{ $u->isAdmin() ? 'Member' : 'Administrator' }}?')"
                                            >
                                                <i class="fas {{ $u->isAdmin() ? 'fa-arrow-down' : 'fa-arrow-up' }}"></i>
                                                <span class="toggle-text">{{ $u->isAdmin() ? 'Set Member' : 'Set Admin' }}</span>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>

                            <!-- Status & Quick Toggle -->
                            <td>
                                <div class="status-cell">
                                    @if($u->status === 'Active')
                                        <span class="status-pill status-active">
                                            <span class="status-dot"></span> Active
                                        </span>
                                    @else
                                        <span class="status-pill status-inactive">
                                            <span class="status-dot"></span> Inactive
                                        </span>
                                    @endif

                                    @if(!$isCurrentUser)
                                        <form action="{{ route('admin.users.status', $u->id) }}" method="POST" style="display: inline-block;">
                                            @csrf
                                            <input type="hidden" name="status" value="{{ $u->status === 'Active' ? 'Inactive' : 'Active' }}">
                                            <button 
                                                type="submit" 
                                                class="btn-toggle-inline {{ $u->status === 'Active' ? 'text-rose' : 'text-emerald' }}" 
                                                title="{{ $u->status === 'Active' ? 'Nonaktifkan akun' : 'Aktifkan akun' }}"
                                                onclick="return confirm('Apakah Anda yakin ingin {{ $u->status === 'Active' ? 'menonaktifkan' : 'mengaktifkan' }} akun {{ $u->name }}?')"
                                            >
                                                <i class="fas {{ $u->status === 'Active' ? 'fa-power-off' : 'fa-check' }}"></i>
                                                <span class="toggle-text">{{ $u->status === 'Active' ? 'Nonaktifkan' : 'Aktifkan' }}</span>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>

                            <!-- Registered Date -->
                            <td class="text-muted" style="font-size: 13px;">
                                <div title="{{ $u->created_at ? $u->created_at->format('d M Y H:i:s') : '-' }}">
                                    <i class="far fa-calendar-alt mr-1"></i>
                                    {{ $u->created_at ? $u->created_at->format('d M Y') : '-' }}
                                </div>
                            </td>

                            <!-- Actions -->
                            <td>
                                <div class="action-buttons-group">
                                    <!-- Edit Button -->
                                    <button 
                                        type="button" 
                                        class="btn-action btn-action-edit" 
                                        title="Edit Pengguna"
                                        onclick="openEditModal({{ json_encode([
                                            'id' => $u->id,
                                            'name' => $u->name,
                                            'email' => $u->email,
                                            'role' => $u->role ?? 'member',
                                            'status' => $u->status,
                                            'is_self' => $isCurrentUser
                                        ]) }})"
                                    >
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <!-- Delete Button -->
                                    @if(!$isCurrentUser)
                                        <button 
                                            type="button" 
                                            class="btn-action btn-action-delete" 
                                            title="Hapus Pengguna"
                                            onclick="openDeleteModal({{ $u->id }}, '{{ addslashes($u->name) }}')"
                                        >
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    @else
                                        <button 
                                            type="button" 
                                            class="btn-action btn-action-disabled" 
                                            title="Akun Anda sendiri tidak dapat dihapus"
                                            disabled
                                        >
                                            <i class="fas fa-lock"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-state">
                                <div class="empty-state-content">
                                    <i class="fas fa-users-slash empty-icon"></i>
                                    <h4>Tidak Ada Data Pengguna</h4>
                                    <p class="text-muted">Tidak ditemukan pengguna yang sesuai dengan pencarian atau filter yang dipilih.</p>
                                    @if(request('search') || request('role') || request('status'))
                                        <a href="{{ route('admin.users.index') }}" class="btn-secondary mt-2">
                                            <i class="fas fa-rotate-left mr-1"></i> Reset Filter
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if ($users->hasPages())
            <div class="card-footer custom-pagination">
                <div class="pagination-info text-muted">
                    Menampilkan <strong>{{ $users->firstItem() }}</strong> sampai <strong>{{ $users->lastItem() }}</strong> dari <strong>{{ $users->total() }}</strong> pengguna
                </div>
                <div>
                    {{ $users->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- ─── MODAL 1: CREATE USER ─────────────────────────────────────────── -->
    <div id="createUserModal" class="qa-modal-overlay" style="display: none;">
        <div class="qa-modal-dialog">
            <div class="qa-modal-header">
                <div class="modal-title-group">
                    <div class="modal-header-icon bg-blue-subtle text-blue">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div>
                        <h4 class="modal-title">Tambah Pengguna Baru</h4>
                        <span class="modal-subtitle">Buat akun tim QA baru dan tentukan hak aksesnya</span>
                    </div>
                </div>
                <button type="button" class="btn-close-modal" onclick="closeCreateModal()">&times;</button>
            </div>

            <form action="{{ route('admin.users.store') }}" method="POST">
                @csrf
                <div class="qa-modal-body">
                    <div class="form-group mb-3">
                        <label for="create_name" class="form-label required">Nama Lengkap</label>
                        <input type="text" id="create_name" name="name" class="form-control" required placeholder="Contoh: Wahyu Sanjaya" value="{{ old('name') }}" />
                    </div>

                    <div class="form-group mb-3">
                        <label for="create_email" class="form-label required">Alamat Email</label>
                        <input type="email" id="create_email" name="email" class="form-control" required placeholder="user@gmail.com" value="{{ old('email') }}" />
                    </div>

                    <div class="form-grid-2 mb-3">
                        <div class="form-group">
                            <label for="create_role" class="form-label required">Hak Akses (Role)</label>
                            <select id="create_role" name="role" class="form-control" required>
                                <option value="member" {{ old('role') === 'member' ? 'selected' : '' }}>QA Member (Akses Standar)</option>
                                <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Administrator (Akses Penuh + User Management)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="create_status" class="form-label required">Status Akun</label>
                            <select id="create_status" name="status" class="form-control" required>
                                <option value="Active" {{ old('status', 'Active') === 'Active' ? 'selected' : '' }}>Active (Dapat Login)</option>
                                <option value="Inactive" {{ old('status') === 'Inactive' ? 'selected' : '' }}>Inactive (Diblokir)</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-grid-2 mb-2">
                        <div class="form-group">
                            <label for="create_password" class="form-label required">Password</label>
                            <input type="password" id="create_password" name="password" class="form-control" required placeholder="Minimal 6 karakter" />
                        </div>

                        <div class="form-group">
                            <label for="create_password_confirmation" class="form-label required">Konfirmasi Password</label>
                            <input type="password" id="create_password_confirmation" name="password_confirmation" class="form-control" required placeholder="Ketik ulang password" />
                        </div>
                    </div>
                </div>

                <div class="qa-modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeCreateModal()">Batal</button>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-check mr-1"></i> Simpan Pengguna
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ─── MODAL 2: EDIT USER ───────────────────────────────────────────── -->
    <div id="editUserModal" class="qa-modal-overlay" style="display: none;">
        <div class="qa-modal-dialog">
            <div class="qa-modal-header">
                <div class="modal-title-group">
                    <div class="modal-header-icon bg-purple-subtle text-purple">
                        <i class="fas fa-user-edit"></i>
                    </div>
                    <div>
                        <h4 class="modal-title">Edit Data Pengguna</h4>
                        <span class="modal-subtitle">Perbarui profil, hak akses Administrator, atau password</span>
                    </div>
                </div>
                <button type="button" class="btn-close-modal" onclick="closeEditModal()">&times;</button>
            </div>

            <form id="editUserForm" method="POST">
                @csrf
                @method('PUT')
                <div class="qa-modal-body">
                    <div id="editSelfNotice" class="alert-box alert-info mb-3" style="display: none;">
                        <i class="fas fa-info-circle mr-2"></i>
                        <span>Anda sedang mengedit akun sendiri. Demi keamanan, role Admin dan status Active tidak dapat diubah dari sini.</span>
                    </div>

                    <div class="form-group mb-3">
                        <label for="edit_name" class="form-label required">Nama Lengkap</label>
                        <input type="text" id="edit_name" name="name" class="form-control" required />
                    </div>

                    <div class="form-group mb-3">
                        <label for="edit_email" class="form-label required">Alamat Email</label>
                        <input type="email" id="edit_email" name="email" class="form-control" required />
                    </div>

                    <div class="form-grid-2 mb-3">
                        <div class="form-group">
                            <label for="edit_role" class="form-label required">Hak Akses (Role)</label>
                            <select id="edit_role" name="role" class="form-control" required>
                                <option value="member">QA Member (Akses Standar)</option>
                                <option value="admin">Administrator (Akses Penuh)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="edit_status" class="form-label required">Status Akun</label>
                            <select id="edit_status" name="status" class="form-control" required>
                                <option value="Active">Active (Dapat Login)</option>
                                <option value="Inactive">Inactive (Diblokir)</option>
                            </select>
                        </div>
                    </div>

                    <div class="password-reset-box">
                        <div class="reset-box-title">
                            <i class="fas fa-key mr-1"></i> Ganti Password (Opsional)
                        </div>
                        <p class="reset-box-note text-muted">Biarkan kosong jika tidak ingin mengubah password pengguna ini.</p>
                        
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label for="edit_password" class="form-label">Password Baru</label>
                                <input type="password" id="edit_password" name="password" class="form-control" placeholder="Minimal 6 karakter" />
                            </div>

                            <div class="form-group">
                                <label for="edit_password_confirmation" class="form-label">Konfirmasi Password Baru</label>
                                <input type="password" id="edit_password_confirmation" name="password_confirmation" class="form-control" placeholder="Ulangi password baru" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="qa-modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeEditModal()">Batal</button>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save mr-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ─── MODAL 3: DELETE CONFIRMATION ─────────────────────────────────── -->
    <div id="deleteUserModal" class="qa-modal-overlay" style="display: none;">
        <div class="qa-modal-dialog qa-modal-dialog-sm">
            <div class="qa-modal-header">
                <div class="modal-title-group">
                    <div class="modal-header-icon bg-rose-subtle text-rose">
                        <i class="fas fa-trash-alt"></i>
                    </div>
                    <div>
                        <h4 class="modal-title">Konfirmasi Hapus</h4>
                        <span class="modal-subtitle">Tindakan ini tidak dapat dibatalkan</span>
                    </div>
                </div>
                <button type="button" class="btn-close-modal" onclick="closeDeleteModal()">&times;</button>
            </div>

            <form id="deleteUserForm" method="POST">
                @csrf
                @method('DELETE')
                <div class="qa-modal-body">
                    <p style="font-size: 14px; color: #475569; line-height: 1.6;">
                        Apakah Anda yakin ingin menghapus pengguna <strong id="deleteUserName" style="color: #1e293b;"></strong>? 
                        Seluruh data terkait pengguna ini akan terpengaruh.
                    </p>
                </div>

                <div class="qa-modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeDeleteModal()">Batal</button>
                    <button type="submit" class="btn-danger-confirm">
                        <i class="fas fa-trash-alt mr-1"></i> Ya, Hapus Pengguna
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
            border: 1px solid #e2e8f0;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .stat-data {
            display: flex;
            flex-direction: column;
        }

        .stat-label {
            font-size: 12.5px;
            color: #64748b;
            font-weight: 500;
        }

        .stat-value {
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.2;
            margin-top: 2px;
        }

        /* Subtle Colors */
        .bg-blue-subtle { background: #eff6ff; }
        .text-blue { color: #2563eb; }
        .bg-purple-subtle { background: #f3e8ff; }
        .text-purple { color: #7c3aed; }
        .bg-emerald-subtle { background: #ecfdf5; }
        .text-emerald { color: #059669; }
        .bg-amber-subtle { background: #fffbeb; }
        .text-amber { color: #d97706; }
        .bg-rose-subtle { background: #fff1f2; }
        .text-rose { color: #e11d48; }

        /* Card Header & Filters */
        .user-header-bar {
            padding: 18px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            border-bottom: 1px solid #f1f5f9;
        }

        .header-title-group {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-heading {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
            display: flex;
            align-items: center;
        }

        .badge-counter {
            background: #f1f5f9;
            color: #475569;
            padding: 4px 10px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
        }

        .header-actions-group {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .filters-form {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .search-input-wrap {
            position: relative;
        }

        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 13px;
        }

        .search-input {
            padding: 8px 12px 8px 34px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            font-size: 13.5px;
            width: 220px;
            outline: none;
            transition: all 0.2s ease;
            background: white;
        }

        .search-input:focus {
            border-color: #7c3aed;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
        }

        .filter-select {
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            font-size: 13px;
            background: white;
            color: #334155;
            outline: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .filter-select:focus {
            border-color: #7c3aed;
        }

        .btn-filter-submit {
            background: #1e293b;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 10px;
            font-size: 13px;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .btn-filter-submit:hover {
            background: #0f172a;
        }

        .btn-filter-reset {
            background: #f1f5f9;
            color: #64748b;
            padding: 8px 10px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .btn-filter-reset:hover {
            background: #e2e8f0;
            color: #1e293b;
        }

        .btn-primary {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            padding: 9px 18px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 13.5px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
            transition: all 0.2s ease;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(99, 102, 241, 0.4);
        }

        /* Table Styling */
        .table-responsive {
            overflow-x: auto;
            width: 100%;
        }

        .user-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .user-table th {
            background: #f8fafc;
            color: #475569;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 14px 20px;
            border-bottom: 1px solid #e2e8f0;
        }

        .user-table td {
            padding: 14px 20px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13.5px;
            vertical-align: middle;
        }

        .user-table tbody tr {
            transition: background 0.15s ease;
        }

        .user-table tbody tr:hover {
            background: #f8fafc;
        }

        .row-current-user {
            background: #faf5ff !important;
        }

        /* User Cell */
        .user-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .avatar-circle {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 13px;
            flex-shrink: 0;
            color: white;
        }

        .avatar-admin {
            background: linear-gradient(135deg, #6366f1, #a855f7);
            box-shadow: 0 2px 8px rgba(168, 85, 247, 0.3);
        }

        .avatar-member {
            background: linear-gradient(135deg, #3b82f6, #06b6d4);
        }

        .user-details {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-size: 14px;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .badge-you {
            background: #ede9fe;
            color: #6d28d9;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 6px;
            text-transform: uppercase;
        }

        .user-email {
            font-size: 12px;
            color: #64748b;
            margin-top: 2px;
        }

        /* Role Cell */
        .role-cell {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
        }

        .role-badge-admin {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            box-shadow: 0 2px 6px rgba(99, 102, 241, 0.25);
        }

        .role-badge-member {
            background: #e2e8f0;
            color: #334155;
        }

        .btn-toggle-inline {
            background: none;
            border: 1px dashed #cbd5e1;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s ease;
        }

        .btn-toggle-inline:hover {
            border-color: currentColor;
            background: rgba(0, 0, 0, 0.03);
            transform: translateY(-1px);
        }

        /* Status Cell */
        .status-cell {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
        }

        .status-active {
            background: #ecfdf5;
            color: #065f46;
        }

        .status-active .status-dot {
            background: #10b981;
        }

        .status-inactive {
            background: #f1f5f9;
            color: #64748b;
        }

        .status-inactive .status-dot {
            background: #94a3b8;
        }

        /* Actions */
        .action-buttons-group {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-action {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.15s ease;
        }

        .btn-action-edit {
            color: #2563eb;
        }

        .btn-action-edit:hover {
            background: #eff6ff;
            border-color: #bfdbfe;
        }

        .btn-action-delete {
            color: #e11d48;
        }

        .btn-action-delete:hover {
            background: #fff1f2;
            border-color: #fecdd3;
        }

        .btn-action-disabled {
            color: #cbd5e1;
            cursor: not-allowed;
            background: #f8fafc;
        }

        /* Empty State */
        .empty-state {
            padding: 50px 20px;
            text-align: center;
        }

        .empty-state-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .empty-icon {
            font-size: 42px;
            color: #cbd5e1;
            margin-bottom: 12px;
        }

        /* Pagination Footer */
        .card-footer.custom-pagination {
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            border-top: 1px solid #f1f5f9;
        }

        /* Modal Styles */
        .qa-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .qa-modal-dialog {
            background: white;
            border-radius: 20px;
            width: 100%;
            max-width: 580px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            animation: modalFadeIn 0.2s ease-out;
            overflow: hidden;
        }

        .qa-modal-dialog-sm {
            max-width: 440px;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: scale(0.96); }
            to { opacity: 1; transform: scale(1); }
        }

        .qa-modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-title-group {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .modal-header-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
        }

        .modal-title {
            margin: 0;
            font-size: 17px;
            font-weight: 700;
            color: #1e293b;
        }

        .modal-subtitle {
            font-size: 12px;
            color: #64748b;
        }

        .btn-close-modal {
            background: none;
            border: none;
            font-size: 22px;
            color: #94a3b8;
            cursor: pointer;
            padding: 4px;
            line-height: 1;
            transition: color 0.15s ease;
        }

        .btn-close-modal:hover {
            color: #0f172a;
        }

        .qa-modal-body {
            padding: 24px;
            overflow-y: auto;
        }

        .qa-modal-footer {
            padding: 16px 24px;
            background: #f8fafc;
            border-top: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
        }

        .form-label.required::after {
            content: " *";
            color: #e11d48;
        }

        .form-control {
            width: 100%;
            padding: 9px 13px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            font-size: 13.5px;
            outline: none;
            transition: all 0.2s ease;
            box-sizing: border-box;
            background: white;
            color: #1e293b;
        }

        .form-control:focus {
            border-color: #7c3aed;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
        }

        .form-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        @media (max-width: 540px) {
            .form-grid-2 {
                grid-template-columns: 1fr;
            }
        }

        .password-reset-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px 16px;
            margin-top: 16px;
        }

        .reset-box-title {
            font-size: 13px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 4px;
        }

        .reset-box-note {
            font-size: 12px;
            margin-bottom: 12px;
        }

        .btn-cancel {
            background: white;
            border: 1px solid #cbd5e1;
            color: #475569;
            padding: 9px 18px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .btn-cancel:hover {
            background: #f1f5f9;
            color: #0f172a;
        }

        .btn-submit {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border: none;
            color: white;
            padding: 9px 20px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
        }

        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(99, 102, 241, 0.4);
        }

        .btn-danger-confirm {
            background: #e11d48;
            border: none;
            color: white;
            padding: 9px 20px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(225, 29, 72, 0.3);
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
        }

        .btn-danger-confirm:hover {
            background: #be123c;
            transform: translateY(-1px);
        }

        /* Alert Box */
        .alert-box {
            padding: 12px 18px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 13.5px;
        }

        .alert-content {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-icon {
            font-size: 16px;
            flex-shrink: 0;
        }

        .alert-close {
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            line-height: 1;
            padding: 0 4px;
        }

        .alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #16a34a;
        }

        .alert-danger {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }

        .alert-info {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1d4ed8;
        }

        .error-list {
            margin: 4px 0 0 18px;
            padding: 0;
            font-size: 13px;
        }
    </style>
@endpush

@push('scripts')
    <script>
        // Modal Create
        function openCreateModal() {
            document.getElementById('createUserModal').style.display = 'flex';
            setTimeout(() => {
                const el = document.getElementById('create_name');
                if (el) el.focus();
            }, 100);
        }

        function closeCreateModal() {
            document.getElementById('createUserModal').style.display = 'none';
        }

        // Modal Edit
        function openEditModal(userData) {
            const form = document.getElementById('editUserForm');
            form.action = `/admin/users/${userData.id}`;

            document.getElementById('edit_name').value = userData.name || '';
            document.getElementById('edit_email').value = userData.email || '';
            document.getElementById('edit_role').value = userData.role || 'member';
            document.getElementById('edit_status').value = userData.status || 'Active';
            document.getElementById('edit_password').value = '';
            document.getElementById('edit_password_confirmation').value = '';

            const notice = document.getElementById('editSelfNotice');
            const roleSelect = document.getElementById('edit_role');
            const statusSelect = document.getElementById('edit_status');

            if (userData.is_self) {
                notice.style.display = 'flex';
                roleSelect.disabled = true;
                statusSelect.disabled = true;
            } else {
                notice.style.display = 'none';
                roleSelect.disabled = false;
                statusSelect.disabled = false;
            }

            document.getElementById('editUserModal').style.display = 'flex';
            setTimeout(() => {
                const el = document.getElementById('edit_name');
                if (el) el.focus();
            }, 100);
        }

        function closeEditModal() {
            document.getElementById('editUserModal').style.display = 'none';
        }

        document.getElementById('editUserForm').addEventListener('submit', function() {
            document.getElementById('edit_role').disabled = false;
            document.getElementById('edit_status').disabled = false;
        });

        // Modal Delete
        function openDeleteModal(userId, userName) {
            const form = document.getElementById('deleteUserForm');
            form.action = `/admin/users/${userId}`;
            document.getElementById('deleteUserName').textContent = userName;
            document.getElementById('deleteUserModal').style.display = 'flex';
        }

        function closeDeleteModal() {
            document.getElementById('deleteUserModal').style.display = 'none';
        }

        // Close on Escape or click outside
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeCreateModal();
                closeEditModal();
                closeDeleteModal();
            }
        });

        document.querySelectorAll('.qa-modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeCreateModal();
                    closeEditModal();
                    closeDeleteModal();
                }
            });
        });
    </script>
@endpush
