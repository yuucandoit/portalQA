@extends('layouts.app')

@section('title', config('app.name', 'QA Portal') . ' - Projects')

@section('page_title', 'Projects')
@section('page_subtitle', 'Manage and view all QA projects')

@section('content')
    <!-- Success Alert -->
    @if (session('success'))
        <div class="content-card mb-4" style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; padding: 14px 20px; border-radius: 12px; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-check-circle" style="font-size: 18px;"></i>
                <span>{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" style="background: none; border: none; color: #16a34a; cursor: pointer; font-size: 16px;">&times;</button>
        </div>
    @endif

    <!-- Validation Errors Alert -->
    @if ($errors->any())
        <div class="content-card mb-4" style="background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; padding: 14px 20px; border-radius: 12px;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                <i class="fas fa-exclamation-circle" style="font-size: 18px;"></i>
                <strong>Terjadi kesalahan saat menyimpan data:</strong>
            </div>
            <ul style="margin-left: 28px; font-size: 13.5px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Main Content Card -->
    <div class="content-card">
        <!-- Card Header & Filter -->
        <div class="card-header" style="padding: 20px 24px; flex-wrap: wrap; gap: 16px;">
            <div style="display: flex; align-items: center; gap: 12px; flex: 1; min-width: 260px;">
                <h3 style="font-size: 18px; font-weight: 700;"><i class="fas fa-folder-open mr-2 text-blue-500"></i>All Projects</h3>
                <span class="badge" style="background: rgba(102, 126, 234, 0.15); color: #667eea; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600;">
                    Total: {{ $projects->total() }}
                </span>
            </div>

            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <!-- Search Form -->
                <form method="GET" action="{{ route('projects.index') }}" style="display: flex; align-items: center; gap: 8px;">
                    <div style="position: relative;">
                        <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 13px;"></i>
                        <input 
                            type="text" 
                            name="search" 
                            value="{{ request('search') }}" 
                            placeholder="Cari project..." 
                            style="padding: 8px 12px 8px 34px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; width: 220px; outline: none; transition: border 0.2s ease;"
                        />
                    </div>
                    <button type="submit" style="background: #1e293b; color: white; border: none; padding: 8px 14px; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer;">
                        Cari
                    </button>
                    @if(request('search'))
                        <a href="{{ route('projects.index') }}" style="color: #64748b; font-size: 13px; text-decoration: none; padding: 8px;">Reset</a>
                    @endif
                </form>

                <!-- Add Project Button -->
                <button type="button" onclick="openCreateModal()" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 9px 18px; border-radius: 10px; font-weight: 600; font-size: 13.5px; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);">
                    <i class="fas fa-plus"></i> New Project
                </button>
            </div>
        </div>

        <!-- Table Body -->
        <div class="card-body" style="padding: 0;">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="padding-left: 24px;">Project Name</th>
                            <th>Version</th>
                            <th>Test Cases</th>
                            <th>Pass Rate</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th style="text-align: right; padding-right: 24px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($projects as $project)
                            @php
                                $stats = $project->stats;
                                $passRate = $stats['pass_rate'];
                            @endphp
                            <tr>
                                <td style="padding-left: 24px;">
                                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                        @if($project->project_code)
                                            <span style="background: #e0e7ff; color: #4338ca; font-weight: 700; font-family: monospace; font-size: 11px; padding: 2px 8px; border-radius: 6px; letter-spacing: 0.05em;" title="Kode Project Buckleup">
                                                {{ $project->project_code }}
                                            </span>
                                        @endif
                                        <span style="font-weight: 700; color: #1e293b; font-size: 14.5px;">{{ $project->name }}</span>
                                    </div>
                                    <div style="font-size: 11.5px; color: #64748b; margin-top: 3px;">
                                        <i class="fas fa-folder text-amber-500 mr-1" style="font-size: 11px;"></i>
                                        <span style="font-family: monospace; color: #475569;">{{ $project->formatted_name }}</span>
                                    </div>
                                    @if ($project->description)
                                        <div style="font-size: 12px; color: #94a3b8; margin-top: 2px;">{{ Str::limit($project->description, 60) }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge-status badge-info" style="font-family: monospace;">{{ $project->version }}</span>
                                </td>
                                <td>
                                    <span style="font-weight: 600; color: #334155;">{{ $stats['total'] }} Cases</span>
                                </td>
                                <td>
                                    @if ($passRate >= 80)
                                        <span class="badge-status badge-success">{{ $passRate }}%</span>
                                    @elseif ($passRate >= 50)
                                        <span class="badge-status badge-warning">{{ $passRate }}%</span>
                                    @else
                                        <span class="badge-status badge-danger">{{ $passRate }}%</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge-status badge-success">Active</span>
                                </td>
                                <td style="color: #64748b; font-size: 13px;">
                                    {{ $project->created_at ? $project->created_at->format('Y-m-d') : '-' }}
                                </td>
                                <td style="text-align: right; padding-right: 24px;">
                                    <div style="display: flex; items-center; justify-content: flex-end; gap: 8px;">
                                        <!-- Edit Button -->
                                        <button 
                                            type="button" 
                                            onclick="openEditModal({{ json_encode($project) }})" 
                                            style="background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; padding: 6px 12px; border-radius: 8px; font-size: 12.5px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;"
                                            title="Edit Project">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>

                                        <!-- Delete Form -->
                                        <form action="{{ route('projects.destroy', $project->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus project {{ $project->name }}?')" style="display: inline-block;">
                                            @csrf
                                            @method('DELETE')
                                            <button 
                                                type="submit" 
                                                style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; padding: 6px 12px; border-radius: 8px; font-size: 12.5px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;"
                                                title="Delete Project">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="padding: 40px; text-align: center; color: #64748b;">
                                    <i class="fas fa-folder-open" style="font-size: 48px; color: #cbd5e1; margin-bottom: 12px;"></i>
                                    <h4 style="font-size: 16px; font-weight: 600; color: #334155; margin-bottom: 4px;">Belum Ada Data Project</h4>
                                    <p style="font-size: 13px;">Silakan klik tombol <strong>New Project</strong> untuk menambahkan project baru.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination Footer -->
        @if ($projects->hasPages())
            <div style="padding: 16px 24px; border-top: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                <div style="font-size: 13px; color: #64748b;">
                    Menampilkan <strong>{{ $projects->firstItem() }}</strong> - <strong>{{ $projects->lastItem() }}</strong> dari <strong>{{ $projects->total() }}</strong> project
                </div>
                <div class="custom-pagination">
                    {{ $projects->links('pagination::bootstrap-5') }}
                </div>
            </div>
        @endif
    </div>

    <!-- ===== CREATE PROJECT MODAL ===== -->
    <div id="createProjectModal" class="modal-backdrop" style="display: none;">
        <div class="modal-box">
            <div class="modal-header">
                <h3><i class="fas fa-folder-plus mr-2 text-blue-500"></i>Tambah Project Baru</h3>
                <button type="button" class="modal-close" onclick="closeCreateModal()">&times;</button>
            </div>
            <form action="{{ route('projects.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div style="display: grid; grid-template-columns: 140px 1fr; gap: 12px;" class="mb-4">
                        <div>
                            <label for="create_project_code">Kode Buckleup</label>
                            <input type="text" id="create_project_code" name="project_code" placeholder="NEWM2" class="modal-input" style="text-transform: uppercase; font-family: monospace;" />
                        </div>
                        <div>
                            <label for="create_name">Nama Project <span style="color: #ef4444;">*</span></label>
                            <input type="text" id="create_name" name="name" required placeholder="New M2 / ICCS" class="modal-input" />
                        </div>
                    </div>
                    <div class="form-group mb-4">
                        <label for="create_version">Versi Project</label>
                        <input type="text" id="create_version" name="version" placeholder="Contoh: v1.0.0" class="modal-input" />
                    </div>
                    <div class="form-group mb-4">
                        <label for="create_description">Deskripsi Project</label>
                        <textarea id="create_description" name="description" rows="3" placeholder="Deskripsi singkat mengenai project..." class="modal-input"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeCreateModal()" class="btn-cancel">Batal</button>
                    <button type="submit" class="btn-submit"><i class="fas fa-save mr-1"></i> Simpan Project</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== EDIT PROJECT MODAL ===== -->
    <div id="editProjectModal" class="modal-backdrop" style="display: none;">
        <div class="modal-box">
            <div class="modal-header">
                <h3><i class="fas fa-edit mr-2 text-blue-500"></i>Edit Project</h3>
                <button type="button" class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <form id="editProjectForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div style="display: grid; grid-template-columns: 140px 1fr; gap: 12px;" class="mb-4">
                        <div>
                            <label for="edit_project_code">Kode Buckleup</label>
                            <input type="text" id="edit_project_code" name="project_code" class="modal-input" style="text-transform: uppercase; font-family: monospace;" />
                        </div>
                        <div>
                            <label for="edit_name">Nama Project <span style="color: #ef4444;">*</span></label>
                            <input type="text" id="edit_name" name="name" required class="modal-input" />
                        </div>
                    </div>
                    <div class="form-group mb-4">
                        <label for="edit_version">Versi Project</label>
                        <input type="text" id="edit_version" name="version" class="modal-input" />
                    </div>
                    <div class="form-group mb-4">
                        <label for="edit_description">Deskripsi Project</label>
                        <textarea id="edit_description" name="description" rows="3" class="modal-input"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeEditModal()" class="btn-cancel">Batal</button>
                    <button type="submit" class="btn-submit"><i class="fas fa-save mr-1"></i> Update Project</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        /* Modal Styles */
        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            animation: fadeIn 0.2s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-box {
            background: white;
            border-radius: 16px;
            width: 100%;
            max-width: 520px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            padding: 18px 24px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-header h3 {
            font-size: 17px;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
            display: flex;
            align-items: center;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            color: #94a3b8;
            cursor: pointer;
            line-height: 1;
            transition: color 0.2s ease;
        }

        .modal-close:hover {
            color: #ef4444;
        }

        .modal-body {
            padding: 24px;
        }

        .modal-body label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
        }

        .modal-input {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid #cbd5e1;
            border-radius: 10px;
            font-size: 14px;
            outline: none;
            transition: all 0.2s ease;
            background: #f8fafc;
        }

        .modal-input:focus {
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
        }

        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid #f1f5f9;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
        }

        .btn-cancel {
            background: white;
            border: 1px solid #cbd5e1;
            color: #64748b;
            padding: 9px 16px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-cancel:hover {
            background: #f1f5f9;
            color: #334155;
        }

        .btn-submit {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            color: white;
            padding: 9px 20px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            transition: all 0.2s ease;
        }

        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
        }

        /* Pagination Styles */
        .custom-pagination .pagination {
            display: flex;
            gap: 4px;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .custom-pagination .page-item .page-link {
            padding: 6px 12px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            color: #475569;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .custom-pagination .page-item.active .page-link {
            background: #667eea;
            border-color: #667eea;
            color: white;
            font-weight: 600;
        }

        .custom-pagination .page-item.disabled .page-link {
            opacity: 0.5;
            cursor: not-allowed;
        }
    </style>
@endpush

@push('scripts')
    <script>
        function openCreateModal() {
            document.getElementById('createProjectModal').style.display = 'flex';
            document.getElementById('create_name').focus();
        }

        function closeCreateModal() {
            document.getElementById('createProjectModal').style.display = 'none';
        }

        function openEditModal(project) {
            const form = document.getElementById('editProjectForm');
            form.action = `/projects/${project.id}`;

            document.getElementById('edit_project_code').value = project.project_code || '';
            document.getElementById('edit_name').value = project.name || '';
            document.getElementById('edit_version').value = project.version || '';
            document.getElementById('edit_description').value = project.description || '';

            document.getElementById('editProjectModal').style.display = 'flex';
            document.getElementById('edit_name').focus();
        }

        function closeEditModal() {
            document.getElementById('editProjectModal').style.display = 'none';
        }

        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeCreateModal();
                closeEditModal();
            }
        });
    </script>
@endpush
