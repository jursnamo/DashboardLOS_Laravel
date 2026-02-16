@extends('layouts.dashboard')

@section('title', 'LOS Monitoring SLA')

@section('styles')
<style>
.sla-card .value { font-size: 1.35rem; font-weight: 700; color: #274177; }
.sla-breach { color: #b7202d; font-weight: 700; }
</style>
@endsection

@section('content')
<ol class="breadcrumb page-breadcrumb">
    <li class="breadcrumb-item">LOS</li>
    <li class="breadcrumb-item active">Monitoring SLA & Aging</li>
    <li class="position-absolute pos-top pos-right d-none d-sm-block"><a href="{{ route('los.applications.index') }}" class="btn btn-outline-primary btn-sm">Back to LOS</a></li>
</ol>

<div class="row mb-3">
    <div class="col-md-4 mb-2">
        <div class="panel sla-card"><div class="panel-content"><div class="text-muted small">Pending Approval Steps</div><div class="value">{{ $slaDashboard['pending_count'] }}</div></div></div>
    </div>
    <div class="col-md-4 mb-2">
        <div class="panel sla-card"><div class="panel-content"><div class="text-muted small">Breached SLA Steps</div><div class="value sla-breach">{{ $slaDashboard['breached_count'] }}</div></div></div>
    </div>
    <div class="col-md-4 mb-2">
        <div class="panel sla-card"><div class="panel-content"><div class="text-muted small">Average Aging Pending (hours)</div><div class="value">{{ $slaDashboard['avg_pending_aging_hours'] }}</div></div></div>
    </div>
</div>

<div class="row">
    <div class="col-xl-8">
        <div class="panel">
            <div class="panel-hdr"><h2>Pending Approvals <span class="fw-300"><i>(SLA & Aging)</i></span></h2></div>
            <div class="panel-container show">
                <div class="panel-content table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead><tr><th>Application</th><th>Customer</th><th>Stage</th><th>Actor</th><th>Role</th><th>Aging (h)</th><th>Due At</th><th>Status</th></tr></thead>
                        <tbody>
                        @forelse($slaDashboard['pending_approvals'] as $row)
                            <tr>
                                <td>{{ $row['application_number'] }}</td>
                                <td>{{ $row['customer_name'] }}</td>
                                <td>{{ strtoupper($row['stage']) }}</td>
                                <td>{{ strtoupper($row['actor_type']) }}</td>
                                <td>{{ $row['approver_role'] }}</td>
                                <td>{{ $row['aging_hours'] }}</td>
                                <td>{{ optional($row['due_at'])->format('Y-m-d H:i') ?: '-' }}</td>
                                <td>
                                    @if($row['is_breached'])
                                        <span class="badge badge-danger">BREACHED</span>
                                    @else
                                        <span class="badge badge-success">ON TRACK</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted">Tidak ada pending approval.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="panel mb-3">
            <div class="panel-hdr"><h2>Stage Aging</h2></div>
            <div class="panel-container show">
                <div class="panel-content table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead><tr><th>Stage</th><th>Count</th><th>Avg(h)</th><th>Max(h)</th></tr></thead>
                        <tbody>
                        @forelse($slaDashboard['stage_aging'] as $row)
                            <tr><td>{{ strtoupper($row['stage']) }}</td><td>{{ $row['count'] }}</td><td>{{ $row['avg_hours'] }}</td><td>{{ $row['max_hours'] }}</td></tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">No data</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-hdr"><h2>Approval Volume by Role</h2></div>
            <div class="panel-container show">
                <div class="panel-content table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead><tr><th>Role</th><th>Total Steps</th></tr></thead>
                        <tbody>
                        @forelse($slaDashboard['approval_by_role'] as $row)
                            <tr><td>{{ $row->approver_role }}</td><td>{{ $row->total }}</td></tr>
                        @empty
                            <tr><td colspan="2" class="text-center text-muted">No data</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
