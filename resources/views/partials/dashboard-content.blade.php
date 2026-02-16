<div class="main-wrap">
    <div class="header" style="background: linear-gradient(120deg, #3f4d9f 0%, #4e73df 35%, #39a2db 100%); color: #fff;">
        <div>
            <h4 class="mb-0 fw-bold"><i class="fal fa-chart-pie mr-2"></i><span>Executive LOS Dashboard</span></h4>
            <small class="opacity-75">Strategic Insights & Performance Monitoring</small>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-default btn-sm waves-effect waves-themed" id="presentModeBtn" onclick="togglePresentMode()">
                <i class="fal fa-tv me-1"></i>Present Mode
            </button>
            <button class="btn btn-outline-default btn-sm waves-effect waves-themed" onclick="exportSimulationReport()">
                <i class="fal fa-file-pdf me-1"></i>Export Compare
            </button>
            <button class="btn btn-outline-default btn-sm waves-effect waves-themed" onclick="location.reload()">Reset Analysis</button>
        </div>
    </div>

    <div id="step1" class="step active">
        <div class="upload-area" onclick="document.getElementById('fileIn').click()">
            <input type="file" id="fileIn" hidden accept=".xlsx,.xls,.csv">
            <div class="mb-4"><i class="fal fa-cloud-upload text-primary" style="font-size: 4rem;"></i></div>
            <h4>Upload Loan Data</h4>
            <p class="text-muted">Supports .xlsx and .csv files. Make sure the file is not open in Excel.</p>
        </div>
    </div>

    <div id="step2" class="step">
        <h5 class="fw-bold text-primary mb-4 border-bottom pb-2">Data Mapping Configuration</h5>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="map-box">
                    <span class="lbl"><i class="fal fa-id-badge me-1"></i> Identitas & Bisnis</span>
                    <label class="small text-muted mt-2">Application ID (Unique)</label>
                    <select id="mId" class="form-control mb-2 col-sel"></select>
                    <label class="small text-muted">Segment Bisnis</label>
                    <select id="mSeg" class="form-control mb-2 col-sel"></select>
                    <label class="small text-muted">Purpose</label>
                    <select id="mPurp" class="form-control mb-2 col-sel"></select>
                    <hr class="my-2">
                    <label class="small text-muted">Approved Limit (Numeric)</label>
                    <select id="mLimit" class="form-control mb-2 col-sel"></select>
                    <label class="small text-muted">Branch Name</label>
                    <select id="mBranch" class="form-control col-sel"></select>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="map-box border-primary" style="background:#f8fbff; position: relative; z-index: 50;">
                    <span class="lbl text-primary"><i class="fal fa-stopwatch me-1"></i> Calculation Mode</span>
                    
                    <div class="mode-sw mt-2">
                        <div id="btn-date" class="mode-btn active" onclick="switchMode('date')">
                            <i class="fal fa-calendar-alt me-1"></i> Date
                        </div>
                        <div id="btn-tat" class="mode-btn" onclick="switchMode('tat')">
                            <i class="fal fa-hashtag me-1"></i> Kolom TAT
                        </div>
                    </div>
                    
                    <div id="pan-date" class="inp-p show">
                        <label class="small text-muted">Start Date</label>
                        <select id="mStart" class="form-control col-sel form-control-sm mb-2"></select>
                        <label class="small text-muted">Completed Date</label>
                        <select id="mEnd" class="form-control col-sel form-control-sm"></select>
                    </div>

                    <div id="pan-tat" class="inp-p">
                        <label class="small text-muted fw-bold text-primary">Select TAT/SLA Column</label>
                        <select id="mTat" class="form-control col-sel form-control-sm"></select>
                    </div>

                    <hr class="my-2">
                    <label class="small text-muted fw-bold">Booking Month (for Trend Chart)</label>
                    <select id="mMonth" class="form-control col-sel form-control-sm"></select>
                    <label class="small text-muted fw-bold mt-2">Complete Date (for Outlier Detail Timeline)</label>
                    <select id="mComplete" class="form-control col-sel form-control-sm"></select>
                </div>
            </div>

            <div class="col-md-4">
                <div class="map-box">
                    <span class="lbl"><i class="fal fa-project-diagram me-1"></i> Flow Status</span>
                    <label class="small text-muted mt-2">Status Column</label>
                    <select id="mStat" class="form-control mb-3 col-sel"></select>
                    <div class="alert alert-light border small text-muted">
                        <i class="fal fa-info-circle me-1"></i>
                        Durasi status yang sama dalam 1 ID akan dijumlahkan.
                    </div>
                </div>
            </div>
        </div>
        <div class="text-end mt-4">
            <button class="btn btn-primary btn-lg waves-effect waves-themed px-4" onclick="processData()">
                Generate Dashboard <i class="fal fa-chevron-right ms-2"></i>
            </button>
        </div>
    </div>

    <div id="step3" class="step">
        <h6 class="section-title">1. EXECUTIVE SUMMARY & STATISTICS</h6>
        <div class="row g-3 mb-3">
            <div class="col-md-12">
                <div class="alert alert-info border-0 p-2 mb-0">
                    <div class="d-flex align-items-center">
                        <i class="fal fa-info-circle me-2 fs-5"></i>
                        <div>
                            <small class="fw-bold d-block">Outlier Definition:</small>
                            <small>Data with TAT > <span class="fw-bold" id="outlierFormula">Q3 + 1.5xIQR</span>. Normal range: Q1=<span id="infoQ1">-</span> to Q3=<span id="infoQ3">-</span> days.</small>
                            <small class="d-block mt-1">
                                <i class="fal fa-dollar-sign me-1"></i>
                                <span class="badge badge-success badge-pill">Limit Calculation</span> Only applications with limit > 0 are included in total & average limit.
                                <span id="limitCalcInfo">-</span>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    
        <div class="row g-3 mb-3">
            <div class="col-md-2"><div class="kpi-card" style="border-left-color: #2563eb;"><div class="kpi-title">Total Applications</div><div class="kpi-val" id="kTotalApp">0</div><div class="kpi-sub">Lifetime Volume</div></div></div>
            <div class="col-md-2"><div class="kpi-card" style="border-left-color: #0891b2;"><div class="kpi-title">Avg Apps / Month</div><div class="kpi-val" id="kAvgAppMonth">0</div><div class="kpi-sub">Productivity Rate</div></div></div>
            <div class="col-md-2"><div class="kpi-card" style="border-left-color: #16a34a;"><div class="kpi-title">Total Approved Limit</div><div class="kpi-val text-success" id="kTotalLimit">0</div><div class="kpi-sub">
                <span id="limitSubText">Portfolio Exposure</span>
                <div class="mt-1" id="limitInfoBadge"></div>
            </div></div></div>
            <div class="col-md-2"><div class="kpi-card" style="border-left-color: #ca8a04;"><div class="kpi-title">Avg Limit / App</div><div class="kpi-val text-warning" id="kAvgLimit">0</div><div class="kpi-sub">
                <span id="avgLimitSubText">Average Ticket Size</span>
                <div class="mt-1" id="avgLimitInfoBadge"></div>
            </div></div></div>
            <div class="col-md-2"><div class="kpi-card" style="border-left-color:#6366f1; background:#f5f3ff"><div class="kpi-title">Average TAT</div><div class="kpi-val" id="vAvg">-</div><div class="kpi-sub">Days</div></div></div>
            <div class="col-md-2"><div class="kpi-card" style="border-left-color:#d946ef; background:#fdf4ff"><div class="kpi-title">Mode</div><div class="kpi-val" id="vMode">-</div><div class="kpi-sub">Days</div></div></div>
        </div>
        
        <div class="row g-2">
            <div class="col-md-2"><div class="kpi-card kpi-clickable" onclick="showAppsInRange('q1', q1Apps)" style="border-left-color:#3b82f6; background:#eff6ff"><div class="kpi-title">Quartile 1 (25%)</div><div class="kpi-val" id="vQ1">-</div><div class="kpi-sub">Days <i class="fal fa-eye ms-1 small"></i></div></div></div>
            <div class="col-md-2"><div class="kpi-card kpi-clickable" onclick="showAppsInRange('median', medianApps)" style="border-left-color:#8b5cf6; background:#f3f0ff"><div class="kpi-title">Median TAT</div><div class="kpi-val" id="vMed">-</div><div class="kpi-sub">Days <i class="fal fa-eye ms-1 small"></i></div></div></div>
            <div class="col-md-2"><div class="kpi-card kpi-clickable" onclick="showAppsInRange('q3', q3Apps)" style="border-left-color:#f59e0b; background:#fffbeb"><div class="kpi-title">Quartile 3 (75%)</div><div class="kpi-val" id="vQ3">-</div><div class="kpi-sub">Days <i class="fal fa-eye ms-1 small"></i></div></div></div>
            <div class="col-md-2"><div class="kpi-card" style="border-left-color:#7c3aed; background:#f5f3ff"><div class="kpi-title">IQR (Q3-Q1)</div><div class="kpi-val" id="vIQR">-</div><div class="kpi-sub">Interquartile Range</div></div></div>
            <div class="col-md-2"><div class="kpi-card" style="border-left-color:#d946ef; background:#fdf4ff"><div class="kpi-title">Outlier Boundary</div><div class="kpi-val" id="vOutBoundary">-</div><div class="kpi-sub">Q3 + 1.5xIQR</div></div></div>
            <div class="col-md-2"><div class="kpi-card kpi-clickable" onclick="showOutliersModal('global')" style="border-left-color:#ef4444; background:#fef2f2"><div class="kpi-title text-danger">Total Outliers</div><div class="kpi-val text-danger" id="vOut">-</div><div class="kpi-sub">Click for details</div></div></div>
        </div>

        <h6 class="section-title">2. EXCEPTION MANAGEMENT (OUTLIER ANALYSIS)</h6>
        <div class="row g-4 mb-4">
            <div class="col-md-12">
                <div class="chart-card border-warning">
                    <div class="chart-head text-warning">
                        <span><i class="fal fa-bullseye me-2"></i>Outlier Distribution Map (5 Categories)</span>
                        <span class="small text-muted">Click a point for application details</span>
                    </div>
                    <div class="mb-2" id="scatterLegend">
                        <!-- Legend akan diisi oleh JavaScript -->
                    </div>
                    <div style="height: 320px;"><canvas id="cScatter"></canvas></div>
                </div>
            </div>
            
            <!-- NEW DASHBOARD: TAT vs Loan Size -->
            <div class="col-md-12">
                <div class="chart-card border-info">
                    <div class="chart-head text-info">
                        <span><i class="fal fa-chart-line me-2"></i>TAT vs Loan Size (Limit Approved)</span>
                        <span class="small text-muted">Click a point for application details</span>
                    </div>
                    <div class="mb-2" id="loanSizeLegend">
                        <!-- Legend akan diisi oleh JavaScript -->
                    </div>
                    <div style="height: 320px;"><canvas id="cLoanSize"></canvas></div>
                </div>
            </div>
            
            <div class="col-md-12">
                <div class="chart-card">
                    <div class="chart-head"><span><i class="fal fa-list-ul me-2"></i>Outlier Detail by Status</span>
                        <span class="small text-muted">IQR = Q3 - Q1 | Boundary = Q3 + 1.5xIQR</span>
                    </div>
                    <div style="overflow-y:auto; height: 350px;">
                        <table class="table-custom status-table-small">
                            <thead><tr><th class="text-start">Flow Status</th><th>Avg</th><th>Q1</th><th>Med</th><th>Q3</th><th>IQR</th><th>Boundary</th><th>Outlier</th></tr></thead>
                            <tbody id="tblStatus"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <h6 class="section-title">3. PROCESS HEALTH CHECK</h6>
        <div class="row g-4">
            <div class="col-md-6">
                <div class="chart-card border-danger">
                    <div class="chart-head text-danger d-flex justify-content-between">
                        <span>Top Bottlenecks</span>
                        <span class="small text-warning" id="tatImpactTotal"></span>
                    </div>
                    <div style="height: 280px;"><canvas id="cSlow"></canvas></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-card border-primary">
                    <div class="chart-head text-primary d-flex justify-content-between">
                        <span><i class="fal fa-stream me-2"></i>Avg Step per Status (Group by App ID)</span>
                        <span class="small text-muted" id="stepImpactInfo"></span>
                    </div>
                    <div style="height: 280px;"><canvas id="cAvgStepStatus"></canvas></div>
                    <div class="small text-muted mt-2" id="avgStepStatusInfo"></div>
                </div>
            </div>
            <div class="col-md-12">
                <h6 class="section-title mt-1">4. MANAGEMENT ACTION BOARD</h6>
                <div class="row g-3 mb-1">
                    <div class="col-md-8">
                        <div class="chart-card border-danger">
                            <div class="row g-2 mb-2">
                                <div class="col-md-4"><div class="mgmt-kpi"><div class="k">High Priority</div><div class="v" id="mgmtKpiHigh">-</div></div></div>
                                <div class="col-md-4"><div class="mgmt-kpi"><div class="k">Impact Captured</div><div class="v" id="mgmtKpiImpact">-</div></div></div>
                                <div class="col-md-4"><div class="mgmt-kpi"><div class="k">Avg Delay Top5</div><div class="v" id="mgmtKpiDelay">-</div></div></div>
                            </div>
                            <div class="chart-head text-danger d-flex justify-content-between">
                                <span><i class="fal fa-exclamation-triangle me-2"></i>Top 5 Priority Status</span>
                                <span class="small text-muted">Sorted by highest Impact %</span>
                            </div>
                            <div class="table-responsive" style="max-height:260px;">
                                <table class="table table-sm table-striped mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Status</th>
                                            <th class="text-end">Impact %</th>
                                            <th class="text-end">#App</th>
                                            <th class="text-end">Avg TAT</th>
                                            <th class="text-end">Avg Step</th>
                                            <th class="text-center">Priority</th>
                                        </tr>
                                    </thead>
                                    <tbody id="mgmtActionTable">
                                        <tr><td colspan="7" class="text-center text-muted">No data yet</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="chart-card border-primary">
                            <div class="chart-head text-primary">
                                <span><i class="fal fa-bolt me-2"></i>Decision Playbook</span>
                            </div>
                            <div class="small text-muted mb-2" id="mgmtActionSummary">Menunggu data...</div>
                            <div id="mgmtActionReco" class="small">
                                <div class="text-muted">No recommendations yet.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <h6 class="section-title mt-1">5. Lead Time (Days)</h6>
            </div>
            <div class="col-md-6">
                <div class="chart-card">
                    <div class="chart-head">TAT Distribution (6 Buckets) <span class="small text-primary">Click bar for bucket details</span></div>
                    <div style="height: 250px;"><canvas id="cDist"></canvas></div>
                    <div id="distChartInfo" class="small text-muted mt-2"></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-card">
                    <div class="chart-head">Avg TAT per Purpose</div>
                    <div style="height: 250px;"><canvas id="cPurp"></canvas></div>
                </div>
            </div>
            <div class="mt-2 d-flex justify-content-between align-items-center">
                <small class="text-muted">Click bar to view status details</small>
            </div>
        </div>

        <h6 class="section-title">6. BUSINESS GROWTH & DISTRIBUTION</h6>
        <div class="row g-4 mb-3">
            <div class="col-md-6">
                <div class="chart-card chart-clickable" onclick="showTrendDetail()">
                    <div class="chart-head">Volume & Limit Trend <span class="small text-primary">Click for details</span></div>
                    <div style="height: 250px;"><canvas id="cTrendMix"></canvas></div>
                    <div id="trendChartInfo" class="small text-muted mt-2"></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-card chart-clickable" onclick="showBranchDetail()">
                    <div class="chart-head">Top 10 Branch <span class="small text-primary">Click for details</span></div>
                    <div style="height: 250px;"><canvas id="cBranch"></canvas></div>
                    <div id="branchChartInfo" class="small text-muted mt-2"></div>
                </div>
            </div>
        </div>

        <h6 class="section-title">7. ACTION IMPACT SIMULATOR</h6>
        <div class="row g-3 mb-3">
            <div class="col-md-12">
                <div class="chart-card border-secondary" id="actionSimCard">
                    <div class="chart-head text-dark d-flex justify-content-between align-items-center">
                        <span><i class="fal fa-sliders-h me-2"></i>Simulator Dampak Aksi (What-if)</span>
                        <div class="d-flex align-items-center gap-2">
                            <span class="small text-muted">Atur reduction di sini atau lewat Quick Sim (floating).</span>
                            <button class="sim-reset-btn" onclick="resetActionSimulator()">
                                <i class="fal fa-redo me-1"></i>Reset Simulator
                            </button>
                        </div>
                    </div>
                    <div class="row g-3 mb-2">
                        <div class="col-md-12">
                            <label class="small text-muted fw-semibold">Pengurangan Loop per Status</label>
                            <div class="sim-controls-shell">
                                <div class="sim-controls-head">Atur pengurangan loop per status. Nilai 0% artinya tidak ada intervensi untuk status tersebut.</div>
                                <div id="simStatusControls" class="sim-status-board">
                                    <div class="small text-muted p-2">Status belum tersedia.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-3"><div class="sim-kpi"><div class="label">Current Avg TAT (Portfolio)</div><div class="value" id="simBaseAvgTat">-</div><div class="sub">current conditions</div></div></div>
                        <div class="col-md-3"><div class="sim-kpi"><div class="label">Projected Avg TAT (Portfolio)</div><div class="value text-primary" id="simProjAvgTat">-</div><div class="sub">after action</div></div></div>
                        <div class="col-md-3"><div class="sim-kpi"><div class="label">Penghematan TAT</div><div class="value text-success" id="simTatSaved">-</div><div class="sub">total hari status</div></div></div>
                        <div class="col-md-3"><div class="sim-kpi"><div class="label">Dampak ke Avg Portfolio</div><div class="value text-success" id="simPortfolioDelta">-</div><div class="sub">penurunan hari/app</div></div></div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-5">
                            <div class="sim-kpi h-100">
                                <div class="label mb-1">SLA Breach (Selected Status)</div>
                                <div class="d-flex justify-content-between align-items-end">
                                    <div>
                                        <div class="small text-muted">Baseline</div>
                                        <div class="value" id="simBreachBase">-</div>
                                    </div>
                                    <div class="text-end">
                                        <div class="small text-muted">Proyeksi</div>
                                        <div class="value text-primary" id="simBreachProj">-</div>
                                    </div>
                                </div>
                                <div class="small text-muted mt-2" id="simInsight">Atur status dari Quick Sim untuk melihat pergerakan dampak.</div>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div style="height:170px;"><canvas id="cActionSim"></canvas></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Unified Modal for All Details -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" style="max-width: 86vw; width: 86vw;">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header" id="detailModalHeader">
                <h5 class="modal-title" id="detailModalLabel"><i class="fal fa-tasks me-2"></i>Application Detail</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="closeDetailModal()">
                    <span aria-hidden="true"><i class="fal fa-times"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info py-2 mb-3">
                    <i class="fal fa-info-circle me-2"></i>
                    <span id="detailInfoText">Showing application details</span>
                </div>
                <div class="row mb-3" id="detailSummaryCards">
                    <!-- Summary cards will be inserted here -->
                </div>
                <div class="mb-3">
                    <div class="input-group input-group-sm" style="width: 300px;">
                        <span class="input-group-text"><i class="fal fa-search"></i></span>
                        <input type="text" id="detailSearch" class="form-control" placeholder="Search by App ID, Branch, or Purpose..." onkeyup="filterDetailTable()">
                    </div>
                </div>
                <div class="table-responsive" style="max-height:500px">
                    <table class="table table-hover table-sm small">
                        <thead class="table-light" id="detailTableHeader">
                            <!-- Table headers will be inserted here -->
                        </thead>
                        <tbody id="detailTableBody"></tbody>
                    </table>
                </div>
                <div class="mt-3 text-muted small" id="detailTableFooter">
                    <!-- Footer info will be inserted here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="exportDetailData()">
                    <i class="fal fa-download me-1"></i>Export CSV
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Single Application Detail Modal -->
<div class="modal fade" id="singleAppModal" tabindex="-1" aria-labelledby="singleAppModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" style="max-width: 88vw; width: 88vw;">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header" id="singleAppModalHeader">
                <h5 class="modal-title" id="singleAppModalLabel"><i class="fal fa-file-alt me-2"></i>Application Detail</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="closeSingleAppModal()">
                    <span aria-hidden="true"><i class="fal fa-times"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert" id="singleAppAlert">
                    <i class="fal fa-info-circle me-2"></i>
                    <span id="singleAppInfoText">Detail applications</span>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card h-100" id="singleAppInfoCard">
                            <div class="card-body">
                                <h6 class="card-title fw-bold mb-3" id="singleAppInfoTitle"><i class="fal fa-file-alt me-2"></i>Application Information</h6>
                                <div class="app-detail-row d-flex">
                                    <span class="app-detail-label">App ID:</span>
                                    <span class="app-detail-value fw-bold" id="singleAppId">-</span>
                                </div>
                                <div class="app-detail-row d-flex">
                                    <span class="app-detail-label">Branch:</span>
                                    <span class="app-detail-value" id="singleAppBranch">-</span>
                                </div>
                                <div class="app-detail-row d-flex">
                                    <span class="app-detail-label">Purpose:</span>
                                    <span class="app-detail-value" id="singleAppPurpose">-</span>
                                </div>
                                <div class="app-detail-row d-flex">
                                    <span class="app-detail-label">Segment:</span>
                                    <span class="app-detail-value" id="singleAppSegment">-</span>
                                </div>
                                <div class="app-detail-row d-flex">
                                    <span class="app-detail-label">Booking Month:</span>
                                    <span class="app-detail-value" id="singleAppMonth">-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100" id="singleAppStatCard">
                            <div class="card-body">
                                <h6 class="card-title fw-bold mb-3" id="singleAppStatTitle"><i class="fal fa-chart-line me-2"></i>Statistic Performance</h6>
                                <div class="app-detail-row d-flex">
                                    <span class="app-detail-label">TAT (Days):</span>
                                    <span class="app-detail-value fw-bold" id="singleAppTat">-</span>
                                </div>
                                <div class="app-detail-row d-flex">
                                    <span class="app-detail-label">Approved Limit:</span>
                                    <span class="app-detail-value fw-bold" id="singleAppLimit">-</span>
                                </div>
                                <div class="app-detail-row d-flex">
                                    <span class="app-detail-label">Status:</span>
                                    <span class="app-detail-value" id="singleAppStatus">-</span>
                                </div>
                                <div class="app-detail-row d-flex">
                                    <span class="app-detail-label">vs Q3 (75%):</span>
                                    <span class="app-detail-value" id="singleAppVsQ3">-</span>
                                </div>
                                <div class="app-detail-row d-flex">
                                    <span class="app-detail-label">vs Outlier Boundary:</span>
                                    <span class="app-detail-value" id="singleAppVsOutlierBoundary">-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card" id="singleAppAnalysisCard">
                    <div class="card-header py-2" id="singleAppAnalysisHeader">
                        <h6 class="mb-0"><i class="fal fa-lightbulb me-2"></i>Analisis</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <div class="mb-2">
                                    <div class="h4 mb-0" id="singleAppPercentile">-</div>
                                    <div class="small text-muted">Percentile</div>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="mb-2">
                                    <div class="h4 mb-0" id="singleAppDaysAboveQ3">-</div>
                                    <div class="small text-muted">Days above Q3</div>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="mb-2">
                                    <div class="h4 mb-0" id="singleAppDaysAboveOutlierBoundary">-</div>
                                    <div class="small text-muted">Days di atas Boundary</div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar bg-success" id="singleAppProgressNormal" style="width: 75%">Normal (0-75%)</div>
                                <div class="progress-bar bg-warning" id="singleAppProgressQ3" style="width: 0%">Q3-Boundary</div>
                                <div class="progress-bar bg-danger" id="singleAppProgressOutlier" style="width: 0%">Outlier</div>
                            </div>
                            <div class="mt-2 small text-muted">
                                <i class="fal fa-info-circle me-1"></i>
                                <span id="singleAppAnalysisText">Analisis posisi TAT applications terhadap distribusi normal</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-3" id="singleAppRepeatCard">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                        <div class="fw-bold">
                            <i class="fal fa-sync me-1"></i>Total TAT by Status
                        </div>
                        <span class="repeat-badge" id="singleAppRepeatTotal">Total Return: 0x</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped mb-0 repeat-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Status</th>
                                        <th class="text-end">Total Step</th>
                                        <th class="text-end">TAT (TOTAL)</th>
                                        <th class="text-center">Priority</th>
                                    </tr>
                                </thead>
                                <tbody id="singleAppRepeatTable">
                                    <tr><td colspan="4" class="text-center text-muted">-</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card mt-3" id="singleAppFlowCard">
                    <div class="card-header py-2 bg-light d-flex justify-content-between align-items-center">
                        <div class="fw-bold text-secondary">
                            <i class="fal fa-project-diagram me-1"></i>Flow Step (sorted by Complete Date)
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="flow-timeline-wrap">
                            <div class="flow-timeline" id="singleAppFlowTimeline">
                                <div class="small text-muted">Flow data is not available yet</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="singleAppShowAllBtn" onclick="showAllOutliersFromSingle()">
                    <i class="fal fa-list-ul me-1"></i>Show All Outliers
                </button>
            </div>
        </div>
    </div>
</div>
<!-- Tambahkan di akhir file sebelum </body> -->
<div class="modal fade" id="bottleneckDetailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable" style="max-width: 88vw; width: 88vw;">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title" id="bottleneckModalLabel">
                    <i class="fal fa-hourglass-half me-2"></i>Detail Bottleneck: <span id="bottleneckStatusName"></span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="fal fa-times"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-light border mb-3 py-2">
                    <div class="small"><strong>Status:</strong> <span id="bottleneckStatusHeader">-</span></div>
                </div>

                <h6 class="section-title">1. KPI Summary</h6>
                <div class="row mb-3" id="bottleneckSummaryCards">
                    <!-- Akan diisi oleh JavaScript -->
                </div>

                <h6 class="section-title">Status TAT vs Loan Size</h6>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="chart-card">
                            <div class="chart-head">TAT vs Loan Size per Status</div>
                            <div class="bottleneck-mini-chart">
                                <canvas id="bottleneckLoanSizeChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-card">
                            <div class="chart-head">2. Loop Frequency Distribution by Loan Size</div>
                            <div class="bottleneck-mini-chart">
                                <canvas id="bottleneckLoopFreqChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <h6 class="section-title">4. Impact to SLA</h6>
                <div class="row g-2 mb-2" id="slaQuickCards">
                    <div class="col-md-4">
                        <div class="kpi-card py-2">
                            <div class="kpi-title">Overall Breach</div>
                            <div class="kpi-val" id="slaOverallBreach">-</div>
                            <div class="kpi-sub">across visible buckets</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="kpi-card py-2">
                            <div class="kpi-title">High Priority Buckets</div>
                            <div class="kpi-val" id="slaHighPriority">-</div>
                            <div class="kpi-sub">need immediate action</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="kpi-card py-2">
                            <div class="kpi-title">No Data Buckets</div>
                            <div class="kpi-val" id="slaNoDataCount">-</div>
                            <div class="kpi-sub">hidden from table</div>
                        </div>
                    </div>
                </div>
                <div class="alert alert-info py-2 mb-2 small" id="slaInsightText">
                    Insight SLA akan muncul setelah status dipilih.
                </div>
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>Loan Size</th>
                                <th class="text-end">Total Applications</th>
                                <th class="text-end">Loop Frequency Distribution</th>
                                <th class="text-end">Avg TAT</th>
                                <th class="text-end">Avg TAT (Status)</th>
                                <th class="text-end">SLA Breach</th>
                                <th class="text-center">Priority</th>
                            </tr>
                        </thead>
                        <tbody id="slaImpactTable"></tbody>
                    </table>
                    <div class="small text-muted" id="slaNoDataInfo"></div>
                    <small class="text-muted">SLA Breach is calculated as status TAT > selected status Avg TAT.</small>
                    <div class="small mt-2 text-secondary" id="slaPriorityInfo">
                        Rule Priority:
                        <span class="badge badge-danger ml-1">High</span> if n >= 30 and breach >= 15%,
                        <span class="badge badge-warning ml-1">Medium</span> if n >= 30 and breach >= 8%,
                        <span class="badge badge-success ml-1">Low</span> otherwise.
                        Breach% = breachCount / total applications bucket x 100.
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="exportBottleneckData()">
                    <i class="fal fa-download me-1"></i>Export Data
                </button>
            </div>
        </div>
    </div>
</div>
<button class="ai-chat-fab" id="aiChatFab" onclick="toggleAIChat()">
    <i class="fal fa-robot me-1"></i>AI Chat
</button>

<button class="sim-fab" id="simFab" onclick="toggleSimFloat()">
    <i class="fal fa-sliders-h me-1"></i>Quick Sim
</button>

<div class="ai-chat-panel" id="aiChatPanel">
    <div class="ai-chat-head">
        <span><i class="fal fa-star me-1"></i>Gemini Assistant</span>
        <button class="btn btn-sm btn-outline-default waves-effect waves-themed py-0 px-2" onclick="toggleAIChat()">x</button>
    </div>
    <div class="ai-chat-log" id="aiChatLog">
        <div class="ai-msg bot">Halo. Saya bisa bantu jelaskan insight dashboard ini. Coba tanya: "kenapa SLA breach tinggi?"</div>
    </div>
    <div class="ai-chat-foot">
        <textarea id="aiChatInput" class="form-control form-control-sm" placeholder="Tulis pertanyaan..."></textarea>
        <button class="btn btn-primary btn-sm" id="aiChatSendBtn" onclick="sendAIChat()">Kirim</button>
    </div>
</div>

<div class="sim-float-panel" id="simFloatPanel">
    <div class="sim-float-head">
        <span><i class="fal fa-sliders-h me-1"></i>Quick Action Simulator</span>
        <button class="btn btn-sm btn-outline-default waves-effect waves-themed py-0 px-2" onclick="toggleSimFloat()">x</button>
    </div>
    <div class="sim-float-body">
        <div class="small text-muted mb-2">Atur reduction status di sini. Dashboard akan bergerak real-time.</div>
        <div id="simStatusControlsFloating" class="sim-status-board">
            <div class="small text-muted p-2">Status belum tersedia.</div>
        </div>
    </div>
    <div class="sim-float-foot">
        <small class="text-muted">Tip: cocok untuk presentasi direksi tanpa scroll.</small>
        <div class="sim-foot-actions">
            <select id="simScenarioSlot" class="form-control form-control-sm sim-slot">
                <option value="A">Scenario A</option>
                <option value="B">Scenario B</option>
                <option value="C">Scenario C</option>
            </select>
            <button class="sim-reset-btn" onclick="saveScenarioSlot()"><i class="fal fa-save me-1"></i>Save</button>
            <button class="sim-reset-btn" onclick="loadScenarioSlot()"><i class="fal fa-folder-open me-1"></i>Load</button>
            <button class="sim-reset-btn" onclick="resetActionSimulator()">
                <i class="fal fa-redo me-1"></i>Reset
            </button>
        </div>
    </div>
</div>








