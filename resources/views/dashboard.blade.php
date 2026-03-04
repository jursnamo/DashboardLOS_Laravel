@extends('layouts.dashboard')

@section('title', 'LOS Dashboard')

@section('styles')
<link rel="stylesheet" href="{{ asset('assets/dashboard-legacy.css') }}">
<style>
.los-dashboard-wrap {
    padding: 0;
    background: linear-gradient(180deg, #eef3fb 0%, #f7f9fd 45%, #fbfcff 100%);
}

.los-dashboard-wrap .main-wrap {
    padding: 1rem;
    background: #ffffff;
    border: 1px solid #d8e0ef;
    border-radius: .6rem;
    box-shadow: 0 6px 18px rgba(17, 34, 68, .08);
    overflow: visible;
}

.los-dashboard-wrap .header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.15rem;
    border-radius: .6rem;
    color: #fff;
    background: linear-gradient(120deg, #3f4d9f 0%, #4e73df 35%, #39a2db 100%);
    box-shadow: 0 8px 20px rgba(42, 66, 130, .3);
}

.los-dashboard-wrap .header small {
    opacity: .9;
}

.los-dashboard-wrap .btn-primary:hover {
    filter: brightness(1.05);
}

.los-dashboard-wrap .mode-btn {
    border-radius: .35rem;
    border: 1px solid #d0dbef;
    background: #f6f9ff;
    color: #40588e;
}

.los-dashboard-wrap .mode-btn.active {
    border-color: #497be4;
    color: #fff;
    background: linear-gradient(120deg, #497be4, #38a5d4);
}

.page-header .header-icon i,
.los-dashboard-wrap .header i,
.los-dashboard-wrap .section-title i,
.los-dashboard-wrap .chart-head i,
.los-dashboard-wrap .lbl i,
.los-dashboard-wrap .kpi-sub i,
.los-dashboard-wrap .small i {
    margin-right: .42rem;
}

.page-header .header-icon,
.dropdown-menu .dropdown-item i,
.nav-menu .nav-link-text {
    letter-spacing: .01em;
}

#menu-dashboard > a,
#menu-import > a,
#menu-datamart > a {
    transition: all .2s ease;
}

#menu-dashboard.active > a,
#menu-import.active > a,
#menu-datamart.active > a {
    color: #1ee0d5;
    font-weight: 600;
}

.subheader {
    margin-bottom: 1rem;
    border-radius: .45rem;
    border: 1px solid #d8e0ef;
    background: linear-gradient(180deg, #f8faff 0%, #f2f6fe 100%);
}

.dashboard-loading-overlay {
    position: fixed;
    inset: 0;
    z-index: 2050;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding-top: 6.25rem;
    background: rgba(245, 248, 255, .78);
    backdrop-filter: blur(1px);
}

.dashboard-loading-card {
    min-width: 250px;
    text-align: center;
    padding: 1rem 1.25rem;
    border-radius: .6rem;
    border: 1px solid #d8e0ef;
    background: #fff;
    box-shadow: 0 8px 24px rgba(17, 34, 68, .12);
}

.dashboard-loading-card .spinner-grow {
    width: 2rem;
    height: 2rem;
}

.import-progress-modal .modal-content {
    border: 1px solid #d8e0ef;
    border-radius: .7rem;
    box-shadow: 0 14px 30px rgba(17, 34, 68, .18);
}

.import-progress-modal .spinner-grow {
    width: 2.1rem;
    height: 2.1rem;
}

.import-progress-modal .progress {
    height: .85rem;
    border-radius: 999px;
    background: #eaf1ff;
}

.import-progress-modal .progress-bar {
    font-size: .68rem;
    font-weight: 700;
}

@media (max-width: 991.98px) {
    .dashboard-loading-overlay {
        padding-top: 4.5rem;
    }

    .los-dashboard-wrap {
        padding: 0;
    }

    .los-dashboard-wrap .main-wrap {
        padding: .75rem;
    }

    .los-dashboard-wrap .header {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>
@endsection

@section('content')
<div class="los-dashboard-wrap position-relative">
    @include('partials.dashboard-content')
    <div id="dashboardLoadingOverlay" class="dashboard-loading-overlay d-none" aria-live="polite" aria-busy="true">
        <div class="dashboard-loading-card">
            <div class="spinner-grow text-primary" role="status" aria-hidden="true"></div>
            <div id="dashboardLoadingText" class="mt-2 fw-600 text-primary">Loading dashboard data from database...</div>
            <div class="small text-muted mt-1">Please wait</div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('assets/dashboard-legacy.js') }}"></script>
<script>
$(function () {
    if ($.fn.smartPanel) {
        $('#js-page-content').smartPanel();
    }

    $('.btn-switch').on('click', function () {
        setTimeout(function () {
            if (typeof saveSettings === 'function') {
                saveSettings();
            }
        }, 50);
    });
});
</script>
@endsection
