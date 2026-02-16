@props(['title','routePrefix','items','columns','idField' => 'id'])

<main id="js-page-content" role="main" class="page-content">
    <div class="los-dashboard-wrap position-relative">
    <div class="main-wrap">
    <style>
        .master-card { border-radius: .5rem; box-shadow: 0 6px 18px rgba(23,24,24,0.04); max-width: none; width: calc(100% - 40px); margin: 0 auto; }
        .master-card .card-header { background: transparent; border-bottom: 1px solid #eef1f6; }
        .master-table td, .master-table th { vertical-align: middle; white-space: nowrap; }
        .master-container { padding: 0 1rem; }
        .card-body.p-3 { padding: 1rem !important; }
        @media (max-width: 1199px) { .master-card { width: calc(100% - 24px); } }
        @media (max-width: 575px) { .master-table td, .master-table th { white-space: normal; } }
    </style>
    <ol class="breadcrumb page-breadcrumb">
        <li class="breadcrumb-item"><a href="javascript:void(0);">Home</a></li>
        <li class="breadcrumb-item"> {{ $title }}</li>
    </ol>
    <div class="container-fluid master-container">
        <div class="row">
            <div class="col-12">
                <div class="card master-card mb-4">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="mb-0"><i class='fal fa-table mr-2'></i> {{ $title }}</h5>
                        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#createModal">Create New</button>
                    </div>
                    <div class="card-body p-3">
                        <div class="table-responsive">
                        <!-- datatable start -->
                        <table id="dt-basic-example" class="table table-bordered table-hover table-striped master-table w-100">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    @foreach($columns as $col)
                                    <th>{{ $col['label'] }}</th>
                                    @endforeach
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $i => $u)
                                <tr id="row-{{ $u->{$idField} }}">
                                    <td>{{ $i+1 }}</td>
                                    @foreach($columns as $col)
                                    <td>{{ $u->{$col['attr']} }}</td>
                                    @endforeach
                                    <td>
                                        <div class="d-flex demo">
                                            <a href="javascript:void(0);"
                                                class="btn btn-sm btn-outline-primary btn-icon btn-inline-block mr-1"
                                                title="update" id="update-{{ $u->{$idField} }}" data-toggle="modal"
                                                data-target="#updateModal"
                                                onclick="updateData('{{ $u->{$idField} }}')"
                                                @foreach($columns as $col)
                                                    data-{{ $col['attr'] }}="{{ $u->{$col['attr']} }}"
                                                @endforeach
                                            >
                                                <i class="fal fa-edit"></i></a>
                                            <a href="javascript:void(0);"
                                                class="btn btn-sm btn-outline-danger btn-icon btn-inline-block mr-1"
                                                title="Delete Record" data-toggle="modal"
                                                onclick="deleteData('{{ $u->{$idField} }}')" data-target="#DeleteModal"
                                                style="color: red"><i class="fal fa-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <!-- datatable end -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Modal -->
    <div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="createModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createModalLabel">Create {{ $title }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="createForm">
                        @csrf
                        @foreach($columns as $col)
                        <div class="form-group">
                            <label>{{ $col['label'] }}</label>
                            <input type="text" name="{{ $col['attr'] }}" class="form-control" placeholder="Enter {{ $col['label'] }}" {{ $col['required'] ?? '' }}>
                        </div>
                        @endforeach
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Modal -->
    <div class="modal fade" id="updateModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Update {{ $title }}</h3>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fal fa-times"></i></span>
                    </button>
                </div>
                <form id="updateForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        @foreach($columns as $col)
                        <div class="form-group">
                            <label>{{ $col['label'] }}</label>
                            <input type="text" name="{{ $col['attr'] }}" class="form-control" value="" placeholder="Enter {{ $col['label'] }}" {{ $col['required'] ?? '' }}>
                        </div>
                        @endforeach
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="DeleteModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Confirmation!!!</h3>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fal fa-times"></i></span>
                    </button>
                </div>
                <form id="deleteForm" method="post">
                    <div class="modal-body">
                        @csrf
                        @method('DELETE')
                        <h5 class="modal-title">Are you sure to delete this item?</h5>
                        <input type="hidden" name="id" id="id" value="">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </div>
    </div>

    <script>
        const masterRoute = '/api/parameters/{{ $routePrefix }}';
        const csrfToken = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';

        async function loadData() {
            try {
                const res = await fetch(masterRoute, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
                const json = await res.json();
                const rows = json.data || json;
                renderTable(rows);
            } catch (err) {
                console.error('Failed to load data', err);
            }
        }

        function renderTable(rows) {
            const tbody = document.querySelector('#dt-basic-example tbody');
            tbody.innerHTML = '';
            rows.forEach((u, i) => {
                const tr = document.createElement('tr');
                tr.id = 'row-' + u['{{ $idField }}'];
                tr.innerHTML = `
                    <td>${i+1}</td>
                    @foreach($columns as $col)
                    <td>${escapeHtml(u['{{ $col['attr'] }}'] ?? '')}</td>
                    @endforeach
                    <td>
                        <div class="d-flex demo">
                            <a href="javascript:void(0);" class="btn btn-sm btn-outline-primary btn-icon btn-inline-block mr-1" id="update-${u['{{ $idField }}']}" onclick="updateData('${u['{{ $idField }}']}')"
                                @foreach($columns as $col)
                                    data-{{ $col['attr'] }}="${escapeHtml(u['{{ $col['attr'] }}'] ?? '')}"
                                @endforeach
                            ><i class="fal fa-edit"></i></a>
                            <a href="javascript:void(0);" class="btn btn-sm btn-outline-danger btn-icon btn-inline-block mr-1" onclick="deleteData('${u['{{ $idField }}']}')" style="color: red"><i class="fal fa-trash"></i></a>
                        </div>
                    </td>
                `;
                // data- attributes included in innerHTML
                tbody.appendChild(tr);
            });
        }

        function escapeHtml(unsafe) {
            if (unsafe === null || unsafe === undefined) return '';
            return String(unsafe)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function updateData(id) {
            const updateBtn = document.getElementById('update-' + id);
            const form = document.getElementById('updateForm');
            form.dataset.id = id;
            @foreach($columns as $col)
                const v_{{ $col['attr'] }} = updateBtn.getAttribute('data-{{ $col['attr'] }}') || '';
                form.querySelector('[name="{{ $col['attr'] }}"]').value = v_{{ $col['attr'] }};
            @endforeach
            showModal('updateModal');
        }

        function deleteData(id) {
            const form = document.getElementById('deleteForm');
            form.dataset.id = id;
            document.getElementById('id').value = id;
            showModal('DeleteModal');
        }

        // Create
        document.getElementById('createForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            const data = {};
            @foreach($columns as $col)
                data['{{ $col['attr'] }}'] = this.querySelector('[name="{{ $col['attr'] }}"]').value;
            @endforeach
            try {
                const res = await fetch(masterRoute, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(data)
                });
                const json = await res.json();
                if (res.ok) {
                    hideModal('createModal');
                    this.reset();
                    loadData();
                } else {
                    alert(json.message || 'Create failed');
                }
            } catch (err) { console.error(err); alert('Create failed'); }
        });

        // Update
        document.getElementById('updateForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            const id = this.dataset.id;
            const data = {};
            @foreach($columns as $col)
                data['{{ $col['attr'] }}'] = this.querySelector('[name="{{ $col['attr'] }}"]').value;
            @endforeach
            try {
                const res = await fetch(masterRoute + '/' + id, {
                    method: 'PUT',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(data)
                });
                const json = await res.json();
                if (res.ok) {
                    hideModal('updateModal');
                    loadData();
                } else {
                    alert(json.message || 'Update failed');
                }
            } catch (err) { console.error(err); alert('Update failed'); }
        });

        // Delete
        document.getElementById('deleteForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            const id = this.dataset.id || document.getElementById('id').value;
            try {
                const res = await fetch(masterRoute + '/' + id, {
                    method: 'DELETE',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });
                const json = await res.json();
                if (res.ok) {
                    hideModal('DeleteModal');
                    loadData();
                } else {
                    alert(json.message || 'Delete failed');
                }
            } catch (err) { console.error(err); alert('Delete failed'); }
        });

        // modal helpers (works with jQuery or Bootstrap 5)
        function showModal(id) {
            const el = document.getElementById(id);
            if (!el) return;
            if (window.jQuery && typeof window.jQuery(el).modal === 'function') {
                window.jQuery(el).modal('show');
                return;
            }
            if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
                let inst = window.bootstrap.Modal.getInstance(el);
                if (!inst) inst = new window.bootstrap.Modal(el);
                inst.show();
                return;
            }
            // fallback: add show class
            el.classList.add('show');
            el.style.display = 'block';
        }

        function hideModal(id) {
            const el = document.getElementById(id);
            if (!el) return;
            if (window.jQuery && typeof window.jQuery(el).modal === 'function') {
                window.jQuery(el).modal('hide');
                return;
            }
            if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
                const inst = window.bootstrap.Modal.getInstance(el);
                if (inst) inst.hide();
                return;
            }
            // fallback
            el.classList.remove('show');
            el.style.display = 'none';
        }

        // Support legacy/data attributes: intercept Close buttons and call hideModal
        document.addEventListener('click', function (evt) {
            const btn = evt.target.closest('[data-dismiss="modal"], [data-bs-dismiss="modal"]');
            if (!btn) return;
            const modal = btn.closest('.modal');
            if (modal && modal.id) {
                hideModal(modal.id);
            } else if (modal) {
                // fallback
                modal.classList.remove('show');
                modal.style.display = 'none';
            }
        });

        // initial load after all scripts (bootstrap/jQuery) are loaded
        window.addEventListener('load', function () { loadData(); });
    </script>
</main>
