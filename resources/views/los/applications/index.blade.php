@extends('layouts.dashboard')

@section('title', 'Loan Origination System')

@section('styles')
<style>
.los-kpi .panel-content { padding: .9rem 1rem; }
.los-kpi .kpi-value { font-size: 1.3rem; font-weight: 700; color: #2d3f73; }
.los-chip { border-radius: 999px; padding: .2rem .65rem; font-size: .7rem; font-weight: 700; text-transform: uppercase; }
.los-table td, .los-table th { vertical-align: middle; }
.los-muted { color: #7a8cae; }
.los-actions .btn { margin-bottom: .2rem; }
</style>
@endsection

@section('content')
<ol class="breadcrumb page-breadcrumb">
    <li class="breadcrumb-item">LOS</li>
    <li class="breadcrumb-item active">Loan Origination</li>
    <li class="position-absolute pos-top pos-right d-none d-sm-block"><a href="{{ route('los.monitoring.index') }}" class="btn btn-outline-primary btn-sm">Open Monitoring Dashboard</a></li>
</ol>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="row mb-2">
    <div class="col-sm-6 col-xl-2 mb-2">
        <div class="panel los-kpi"><div class="panel-content"><div class="small text-muted">Total Aplikasi</div><div class="kpi-value">{{ $metrics['total'] }}</div></div></div>
    </div>
    <div class="col-sm-6 col-xl-2 mb-2">
        <div class="panel los-kpi"><div class="panel-content"><div class="small text-muted">Draft</div><div class="kpi-value">{{ $metrics['draft'] }}</div></div></div>
    </div>
    <div class="col-sm-6 col-xl-2 mb-2">
        <div class="panel los-kpi"><div class="panel-content"><div class="small text-muted">Review</div><div class="kpi-value">{{ $metrics['review'] }}</div></div></div>
    </div>
    <div class="col-sm-6 col-xl-2 mb-2">
        <div class="panel los-kpi"><div class="panel-content"><div class="small text-muted">Approved</div><div class="kpi-value">{{ $metrics['approved'] }}</div></div></div>
    </div>
    <div class="col-sm-6 col-xl-2 mb-2">
        <div class="panel los-kpi"><div class="panel-content"><div class="small text-muted">SLA Breached</div><div class="kpi-value text-danger">{{ $slaDashboard['breached_count'] }}</div></div></div>
    </div>
    <div class="col-sm-6 col-xl-2 mb-2">
        <div class="panel los-kpi"><div class="panel-content"><div class="small text-muted">Total Plafond</div><div class="kpi-value">Rp {{ number_format($metrics['plafond'], 0, ',', '.') }}</div></div></div>
    </div>
</div>

<div class="row">
    <div class="col-xl-8">
        <div class="panel mb-3">
            <div class="panel-hdr"><h2>Create <span class="fw-300"><i>Loan Application</i></span></h2></div>
            <div class="panel-container show">
                <div class="panel-content">
                    <form method="POST" action="{{ route('los.applications.store') }}">
                        @csrf
                        <div class="form-row">
                            <div class="form-group col-md-2"><label>CIF</label><input name="cif_number" class="form-control"></div>
                            <div class="form-group col-md-3"><label>Customer</label><input name="customer_name" class="form-control" required></div>
                            <div class="form-group col-md-2">
                                <label>Division</label>
                                <select name="division" class="form-control" required>
                                    @foreach($divisionOptions as $division)
                                        <option value="{{ $division }}">{{ $division }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-2">
                                <label>Segment</label>
                                <select name="segment" class="form-control" required>
                                    <option value="corporate">Corporate</option>
                                    <option value="commercial" selected>Commercial</option>
                                    <option value="commex">Commex</option>
                                </select>
                            </div>
                            <div class="form-group col-md-3"><label>Loan Type</label><input name="loan_type" class="form-control" value="term_loan" required></div>
                            <div class="form-group col-md-3"><label>APK Type</label><input name="apk_type" class="form-control" placeholder="manufaktur / kontraktor"></div>
                            <div class="form-group col-md-2"><label>Plafond</label><input type="number" name="plafond_amount" class="form-control" required></div>
                            <div class="form-group col-md-2"><label>Tenor (bulan)</label><input type="number" name="tenor_months" class="form-control"></div>
                            <div class="form-group col-md-2">
                                <label>BWMK</label>
                                <select name="bwmk_type" class="form-control">
                                    <option value="">-</option>
                                    <option value="non_deviasi">Non Deviasi</option>
                                    <option value="deviasi">Deviasi</option>
                                </select>
                            </div>
                            <div class="form-group col-md-3"><label>RM Name</label><input name="rm_name" class="form-control"></div>
                            <div class="form-group col-md-3"><label>Branch</label><input name="branch_name" class="form-control"></div>
                            <div class="form-group col-md-3"><label>Expected Disbursement</label><input type="date" name="expected_disbursement_date" class="form-control"></div>
                            <div class="form-group col-md-12"><label>Purpose</label><textarea name="purpose" class="form-control" rows="2"></textarea></div>
                        </div>
                        <button class="btn btn-primary">Create Application</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="panel mb-3">
            <div class="panel-hdr"><h2>Approval Matrix <span class="fw-300"><i>(Role-based)</i></span></h2></div>
            <div class="panel-container show">
                <div class="panel-content">
                    <form method="POST" action="{{ route('los.approval-matrix.store') }}">
                        @csrf
                        <div class="form-group mb-2"><label>Division</label><input name="division" class="form-control" placeholder="Commercial" required></div>
                        <div class="form-group mb-2">
                            <label>Segment</label>
                            <select name="segment" class="form-control" required>
                                <option value="corporate">Corporate</option>
                                <option value="commercial">Commercial</option>
                                <option value="commex">Commex</option>
                            </select>
                        </div>
                        <div class="form-group mb-2">
                            <label>BWMK</label>
                            <select name="bwmk_type" class="form-control">
                                <option value="">All</option>
                                <option value="non_deviasi">Non Deviasi</option>
                                <option value="deviasi">Deviasi</option>
                            </select>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4"><label>Actor</label><select name="actor_type" class="form-control"><option value="maker">maker</option><option value="checker">checker</option><option value="approver">approver</option></select></div>
                            <div class="form-group col-md-4"><label>Seq</label><input type="number" name="sequence_no" class="form-control" value="1" min="1"></div>
                            <div class="form-group col-md-4"><label>SLA (h)</label><input type="number" name="sla_hours" class="form-control" value="24" min="1"></div>
                        </div>
                        <div class="form-group mb-2"><label>Role Name (Spatie Role)</label><input name="role_name" class="form-control" placeholder="BM / BSM / RM" required></div>
                        <button class="btn btn-outline-primary btn-block">Save Matrix</button>
                    </form>
                    <hr>
                    <div class="table-responsive" style="max-height: 240px;">
                        <table class="table table-sm table-bordered mb-0">
                            <thead><tr><th>Div</th><th>Seg</th><th>Step</th><th>Role</th><th></th></tr></thead>
                            <tbody>
                            @forelse($matrices as $m)
                                <tr>
                                    <td>{{ $m->division }}</td>
                                    <td>{{ $m->segment }}</td>
                                    <td>{{ $m->actor_type }}-{{ $m->sequence_no }}</td>
                                    <td>{{ $m->role_name }} <span class="los-muted">({{ $m->sla_hours }}h)</span></td>
                                    <td>
                                        <form method="POST" action="{{ route('los.approval-matrix.destroy', $m) }}">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-xs btn-outline-danger">x</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted">No matrix data</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="panel">
    <div class="panel-hdr"><h2>Application <span class="fw-300"><i>Pipeline</i></span></h2></div>
    <div class="panel-container show">
        <div class="panel-content">
            <form method="GET" class="form-row align-items-end mb-3">
                <div class="form-group col-md-4"><label>Search</label><input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="no aplikasi / nama / CIF"></div>
                <div class="form-group col-md-3"><label>Stage</label><select name="stage" class="form-control"><option value="">All</option>@foreach(['draft','review','legal','acceptance','approved','rejected','disbursed'] as $stage)<option value="{{ $stage }}" @selected(request('stage') === $stage)>{{ strtoupper($stage) }}</option>@endforeach</select></div>
                <div class="form-group col-md-3"><label>Segment</label><select name="segment" class="form-control"><option value="">All</option>@foreach(['corporate','commercial','commex'] as $seg)<option value="{{ $seg }}" @selected(request('segment') === $seg)>{{ strtoupper($seg) }}</option>@endforeach</select></div>
                <div class="form-group col-md-2"><button class="btn btn-primary btn-block">Filter</button></div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-hover w-100 los-table">
                    <thead class="bg-primary-50">
                        <tr>
                            <th>Application</th>
                            <th>Division</th>
                            <th>Segment</th>
                            <th>Plafond</th>
                            <th>Collateral / Liquidation</th>
                            <th>Stage</th>
                            <th>BWMK</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($applications as $application)
                        <tr>
                            <td>
                                <strong>{{ $application->application_number }}</strong><br>
                                <span class="los-muted">{{ $application->customer_name }} | {{ $application->cif_number ?: '-' }}</span>
                            </td>
                            <td>{{ $application->division }}</td>
                            <td class="text-uppercase">{{ $application->segment }}</td>
                            <td>Rp {{ number_format((float) $application->plafond_amount, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format((float) $application->total_collateral_value, 0, ',', '.') }} / Rp {{ number_format((float) $application->total_liquidation_value, 0, ',', '.') }}</td>
                            <td><span class="badge badge-info los-chip">{{ $application->current_stage }}</span></td>
                            <td>{{ $application->bwmk_type ?: '-' }}</td>
                            <td class="los-actions">
                                <button class="btn btn-xs btn-outline-info js-detail-btn" data-url="{{ route('los.applications.show', $application) }}">Detail</button>
                                <button class="btn btn-xs btn-outline-secondary js-collateral-btn" data-url="{{ route('los.applications.collaterals.store', $application) }}">Collateral</button>
                                <button class="btn btn-xs btn-outline-secondary js-document-btn" data-url="{{ route('los.applications.documents.upsert', $application) }}">Document</button>
                                <button class="btn btn-xs btn-outline-secondary js-covenant-btn" data-url="{{ route('los.applications.covenants.store', $application) }}">Covenant</button>
                                @if(in_array($application->current_stage, ['draft','rejected']))
                                    <form method="POST" action="{{ route('los.applications.submit', $application) }}" class="d-inline">@csrf<button class="btn btn-xs btn-outline-primary">Submit</button></form>
                                @endif
                                @if($application->current_stage === 'review' || $application->current_stage === 'acceptance')
                                    <form method="POST" action="{{ route('los.applications.approve', $application) }}" class="d-inline">@csrf<input type="hidden" name="notes" value="Approved from LOS panel"><button class="btn btn-xs btn-outline-success">Approve</button></form>
                                    <form method="POST" action="{{ route('los.applications.reject', $application) }}" class="d-inline">@csrf<input type="hidden" name="notes" value="Rejected from LOS panel"><button class="btn btn-xs btn-outline-danger">Reject</button></form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted">Belum ada data aplikasi.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $applications->links() }}
        </div>
    </div>
</div>

<div class="modal fade" id="applicationDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Loan Application Detail</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
            <div class="modal-body"><pre id="detailJson" class="bg-light p-3 mb-0" style="max-height: 65vh; overflow: auto;"></pre></div>
        </div>
    </div>
</div>

<div class="modal fade" id="collateralModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Add Collateral</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
            <form id="collateralForm" method="POST">@csrf
                <div class="modal-body">
                    <div class="form-group"><label>Type</label><select name="collateral_type" class="form-control" required><option value="property">Property</option><option value="non_property">Non Property</option></select></div>
                    <div class="form-group"><label>Subtype</label><input name="collateral_subtype" class="form-control" required></div>
                    <div class="form-group"><label>Description</label><input name="description" class="form-control"></div>
                    <div class="form-group"><label>Appraisal Value</label><input type="number" name="appraisal_value" class="form-control" required></div>
                    <div class="form-group"><label>Liquidation Value</label><input type="number" name="liquidation_value" class="form-control" required></div>
                </div>
                <div class="modal-footer"><button class="btn btn-primary">Save</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="documentModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Upload / Update Document</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
            <form id="documentForm" method="POST" enctype="multipart/form-data">@csrf
                <div class="modal-body">
                    <div class="form-group"><label>Document Name</label><input name="document_name" class="form-control" required></div>
                    <div class="form-group"><label>Category</label><select name="document_category" class="form-control"><option value="predefined">Predefined</option><option value="additional">Additional</option></select></div>
                    <div class="form-group"><label>Physical File</label><input type="file" name="file" class="form-control-file"></div>
                    <div class="form-row">
                        <div class="form-group col-md-6"><label>Uploaded Status</label><select name="is_uploaded" class="form-control"><option value="0">Belum Upload</option><option value="1">Uploaded</option></select></div>
                        <div class="form-group col-md-6"><label>Verification</label><select name="verification_status" class="form-control"><option value="pending">Pending</option><option value="valid">Valid</option><option value="invalid">Invalid</option></select></div>
                    </div>
                    <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer"><button class="btn btn-primary">Save</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="covenantModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Add Covenant</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
            <form id="covenantForm" method="POST">@csrf
                <div class="modal-body">
                    <div class="form-group"><label>Phase</label><select name="covenant_phase" class="form-control" required><option value="pre_disbursement">Pre Disbursement</option><option value="at_disbursement">At Disbursement</option><option value="post_disbursement">Post Disbursement</option></select></div>
                    <div class="form-group"><label>Covenant</label><textarea name="covenant_text" class="form-control" rows="3" required></textarea></div>
                    <div class="form-group"><label>Due Date</label><input type="date" name="due_date" class="form-control"></div>
                </div>
                <div class="modal-footer"><button class="btn btn-primary">Save</button></div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.querySelectorAll('.js-detail-btn').forEach(function(btn) {
    btn.addEventListener('click', async function() {
        const target = document.getElementById('detailJson');
        target.textContent = 'Loading...';
        try {
            const res = await fetch(btn.getAttribute('data-url'), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
            const json = await res.json();
            target.textContent = JSON.stringify(json.data, null, 2);
        } catch (e) {
            target.textContent = 'Failed to load detail';
        }
        $('#applicationDetailModal').modal('show');
    });
});

function bindModalAction(buttonClass, formId, modalId) {
    document.querySelectorAll(buttonClass).forEach(function(btn) {
        btn.addEventListener('click', function() {
            const form = document.querySelector(formId);
            form.setAttribute('action', btn.getAttribute('data-url'));
            $(modalId).modal('show');
        });
    });
}

bindModalAction('.js-collateral-btn', '#collateralForm', '#collateralModal');
bindModalAction('.js-document-btn', '#documentForm', '#documentModal');
bindModalAction('.js-covenant-btn', '#covenantForm', '#covenantModal');
</script>
@endsection
