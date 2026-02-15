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
    color: rgba(255,255,255,.85);
}

.los-dashboard-wrap .header .btn {
    border-radius: .35rem;
    border-color: rgba(255,255,255,.45);
    color: #fff;
    background: rgba(11, 20, 47, .18);
}

.los-dashboard-wrap .header .btn:hover {
    background: rgba(255,255,255,.16);
    border-color: rgba(255,255,255,.65);
}

.los-dashboard-wrap .step {
    margin-top: 1rem;
    padding: 1rem;
    border-radius: .55rem;
    border: 1px solid #d8e0ef;
    background: #fff;
    box-shadow: 0 4px 12px rgba(16, 42, 67, .05);
}

.los-dashboard-wrap .upload-area {
    border: 2px dashed #bfd0ee;
    border-radius: .7rem;
    background: linear-gradient(180deg, #f9fbff 0%, #f1f6ff 100%);
    transition: all .2s ease;
}

.los-dashboard-wrap .upload-area:hover {
    border-color: #6c8cd5;
    transform: translateY(-2px);
}

.los-dashboard-wrap .map-box,
.los-dashboard-wrap .chart-card,
.los-dashboard-wrap .kpi-card,
.los-dashboard-wrap .mgmt-kpi,
.los-dashboard-wrap .sim-kpi {
    border-radius: .5rem;
    border: 1px solid #dbe2f0;
    background: #fff;
    box-shadow: 0 4px 12px rgba(17, 34, 68, .05);
}

.los-dashboard-wrap .chart-head,
.los-dashboard-wrap .section-title {
    color: #2c3e74;
}

.los-dashboard-wrap .section-title {
    margin: 1.1rem 0 .8rem;
    padding: .65rem .9rem;
    font-weight: 700;
    letter-spacing: .02em;
    background: #fff;
    border: 1px solid #d8e0ef;
    border-left: 4px solid #4e73df;
    border-radius: .45rem;
    box-shadow: 0 3px 10px rgba(17, 34, 68, .05);
}

.los-dashboard-wrap #step3 .section-title:first-of-type {
    margin-top: 0;
}

.los-dashboard-wrap .kpi-card {
    position: relative;
    overflow: hidden;
}

.los-dashboard-wrap .kpi-card::after {
    content: '';
    position: absolute;
    right: -10px;
    top: -12px;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(99, 102, 241, .16), rgba(99, 102, 241, 0));
}

.los-dashboard-wrap select.form-control,
.los-dashboard-wrap .form-control {
    border-color: #cfd8ea;
    border-radius: .35rem;
    min-height: 2rem;
}

.los-dashboard-wrap .btn-primary {
    background: linear-gradient(120deg, #3b6fe2, #2e94d1);
    border-color: #3b6fe2;
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

/* Improve icon-text breathing room */
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
#menu-import > a {
    transition: all .2s ease;
}

#menu-dashboard.active > a,
#menu-import.active > a {
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
<div class="page-wrapper">
    <div class="page-inner">
        <aside class="page-sidebar">
            <div class="page-logo">
                <a href="#" class="page-logo-link press-scale-down d-flex align-items-center position-relative">
                    <img src="{{ asset('assets/smartadmin4/img/logo.png') }}" alt="LOS Dashboard">
                    <span class="page-logo-text mr-1">LOS SmartAdmin</span>
                </a>
            </div>

            <nav id="js-primary-nav" class="primary-nav" role="navigation">
                <div class="nav-filter">
                    <div class="position-relative">
                        <input type="text" id="nav_filter_input" placeholder="Filter menu" class="form-control" tabindex="0">
                        <a href="#" onclick="return false;" class="btn-primary btn-search-close js-waves-off" data-action="toggle" data-class="list-filter-active" data-target=".page-sidebar">
                            <i class="fal fa-chevron-up"></i>
                        </a>
                    </div>
                </div>

                <div class="info-card">
                    <img src="{{ asset('assets/smartadmin4/img/demo/avatars/avatar-admin.png') }}" class="profile-image rounded-circle" alt="Administrator">
                    <div class="info-card-text">
                        <a href="#" class="d-flex align-items-center text-white">
                            <span class="text-truncate text-truncate-sm d-inline-block">{{ auth()->user()->name ?? 'User' }}</span>
                        </a>
                        <span class="d-inline-block text-truncate text-truncate-sm">{{ auth()->user()->getRoleNames()->first() ?? '' }}</span>
                    </div>
                    <img src="{{ asset('assets/smartadmin4/img/card-backgrounds/cover-2-lg.png') }}" class="cover" alt="cover">
                    <a href="#" onclick="return false;" class="pull-trigger-btn" data-action="toggle" data-class="list-filter-active" data-target=".page-sidebar" data-focus="nav_filter_input">
                        <i class="fal fa-angle-down"></i>
                    </a>
                </div>

                <ul id="js-nav-menu" class="nav-menu">
                    <li class="active open">
                        <a href="#" title="LOS Menu">
                            <i class="fal fa-chart-area"></i>
                            <span class="nav-link-text">LOS Panel</span>
                        </a>
                        <ul>
                            <li id="menu-dashboard" class="active">
                                <a href="#" title="Dashboard" onclick="openDashboardMenu(); return false;">
                                    <span class="nav-link-text">Dashboard</span>
                                </a>
                            </li>
                            <li id="menu-import">
                                <a href="#" title="Import Data" onclick="openImportDataMenu(); return false;">
                                    <span class="nav-link-text">Import Data</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>

                <div class="filter-message js-filter-message bg-success-600"></div>
            </nav>
        </aside>

        <div class="page-content-wrapper">
            <header class="page-header" role="banner">
                <div class="hidden-md-down dropdown-icon-menu position-relative">
                    <a href="#" class="header-btn btn" data-action="toggle" data-class="nav-function-hidden" title="Hide Navigation">
                        <i class="fal fa-bars"></i>
                    </a>
                    <ul>
                        <li>
                            <a href="#" class="btn" data-action="toggle" data-class="nav-function-minify" title="Minify Navigation">
                                <i class="fal fa-compress-arrows-alt"></i>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="btn" data-action="toggle" data-class="nav-function-fixed" title="Lock Navigation">
                                <i class="fal fa-lock"></i>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="hidden-lg-up">
                    <a href="#" class="header-btn btn press-scale-down" data-action="toggle" data-class="mobile-nav-on">
                        <i class="fal fa-bars"></i>
                    </a>
                </div>

                <div class="search">
                    <form class="app-forms hidden-xs-down" role="search" autocomplete="off">
                        <input type="text" id="search-field" placeholder="Search for anything" class="form-control" tabindex="1">
                        <a href="#" class="btn-danger btn-search-close d-none" data-action="toggle" data-class="mobile-search-on">
                            <i class="fal fa-times"></i>
                        </a>
                    </form>
                </div>

                <div class="ml-auto d-flex">
                    <div class="hidden-sm-up">
                        <a href="#" class="header-icon" data-action="toggle" data-class="mobile-search-on" data-focus="search-field" title="Search">
                            <i class="fal fa-search"></i>
                        </a>
                    </div>

                    <div class="hidden-md-down">
                        <a href="#" class="header-icon" data-toggle="modal" data-target="#modal-settings" title="Settings">
                            <i class="fal fa-cog"></i>
                        </a>
                    </div>

                    <div>
                        <a href="#" class="header-icon" data-toggle="dropdown" title="My Apps">
                            <i class="fal fa-cube"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-animated w-auto h-auto">
                            <div class="dropdown-header bg-trans-gradient d-flex justify-content-center align-items-center rounded-top">
                                <h4 class="m-0 text-center color-white">Quick Shortcut</h4>
                            </div>
                            <div class="custom-scroll h-100">
                                <ul class="app-list">
                                    <li><a href="#" class="app-list-item" onclick="openDashboardMenu(); return false;"><span class="app-list-name">Dashboard</span></a></li>
                                    <li><a href="#" class="app-list-item" onclick="openImportDataMenu(); return false;"><span class="app-list-name">Import Data</span></a></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <a href="#" class="header-icon" data-toggle="modal" data-target="#modal-messenger" title="Messenger">
                        <i class="fal fa-globe"></i>
                        <span class="badge badge-icon">!</span>
                    </a>

                    <div>
                        <a href="#" class="header-icon" data-toggle="dropdown" title="Notifications">
                            <i class="fal fa-bell"></i>
                            <span class="badge badge-icon">11</span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-animated dropdown-xl">
                            <div class="dropdown-header bg-trans-gradient d-flex justify-content-center align-items-center rounded-top mb-2">
                                <h4 class="m-0 text-center color-white">11 New
                                    <small class="mb-0 opacity-80">User Notifications</small>
                                </h4>
                            </div>
                            <ul class="nav nav-tabs nav-tabs-clean" role="tablist">
                                <li class="nav-item"><a class="nav-link px-4 fs-md fw-500 active" data-toggle="tab" href="#tab-messages">Messages</a></li>
                                <li class="nav-item"><a class="nav-link px-4 fs-md fw-500" data-toggle="tab" href="#tab-feeds">Feeds</a></li>
                                <li class="nav-item"><a class="nav-link px-4 fs-md fw-500" data-toggle="tab" href="#tab-events">Events</a></li>
                            </ul>
                            <div class="tab-content tab-notification">
                                <div class="tab-pane active p-3 text-center" id="tab-messages" role="tabpanel">
                                    <h5 class="mt-4 pt-4 fw-500"><span class="d-block fa-3x pb-4 text-muted"><i class="fal fa-arrow-up text-gradient opacity-70"></i></span>Select a tab above to activate</h5>
                                </div>
                                <div class="tab-pane p-3 text-center" id="tab-feeds" role="tabpanel">No feed data.</div>
                                <div class="tab-pane p-3 text-center" id="tab-events" role="tabpanel">No event data.</div>
                            </div>
                            <div class="py-2 px-3 bg-faded d-block rounded-bottom text-right border-faded border-bottom-0 border-right-0 border-left-0">
                                <a href="#" class="fs-xs fw-500 ml-auto">view all notifications</a>
                            </div>
                        </div>
                    </div>

                    <div>
                        <a href="#" data-toggle="dropdown" title="{{ auth()->user()->email ?? '' }}" class="header-icon d-flex align-items-center justify-content-center ml-2">
                            <img src="{{ asset('assets/smartadmin4/img/demo/avatars/avatar-admin.png') }}" class="profile-image rounded-circle" alt="{{ auth()->user()->name ?? 'User' }}">
                        </a>
                        <div class="dropdown-menu dropdown-menu-animated dropdown-lg">
                            <div class="dropdown-header bg-trans-gradient d-flex flex-row py-4 rounded-top">
                                <div class="d-flex flex-row align-items-center mt-1 mb-1 color-white">
                                    <span class="mr-2"><img src="{{ asset('assets/smartadmin4/img/demo/avatars/avatar-admin.png') }}" class="rounded-circle profile-image" alt="{{ auth()->user()->name ?? 'User' }}"></span>
                                    <div class="info-card-text">
                                        <div class="fs-lg text-truncate text-truncate-lg">{{ auth()->user()->name ?? 'User' }}</div>
                                        <span class="text-truncate text-truncate-md opacity-80">{{ auth()->user()->email ?? '' }}</span>
                                    </div>
                                </div>
                            </div>
                            <a href="#" class="dropdown-item" onclick="resetSettings(); return false;">Reset Layout</a>
                            <a href="#" class="dropdown-item" data-toggle="modal" data-target="#modal-settings">Settings</a>
                            <div class="dropdown-divider m-0"></div>
                           <a class="dropdown-item fw-500 pt-3 pb-3" href="{{ route('logout') }}"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                Logout
                            </a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                @csrf
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <main id="js-page-content" role="main" class="page-content">


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
            </main>
        </div>
    </div>
</div>

<div class="modal fade import-progress-modal" id="importProgressModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body p-4 text-center">
                <div class="spinner-grow text-primary mb-3" role="status" aria-hidden="true"></div>
                <h5 class="fw-700 mb-2">Importing data to database</h5>
                <div id="importProgressText" class="text-muted mb-3">Import 0% dari 0 data...</div>
                <div class="progress">
                    <div id="importProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%">0%</div>
                </div>
                <div id="importProgressHint" class="small text-muted mt-3">Mohon tunggu, proses sedang berjalan.</div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade js-modal-settings modal-backdrop-transparent" id="modal-settings" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-right" role="document">
        <div class="modal-content h-100">
            <div class="dropdown-header bg-trans-gradient d-flex justify-content-center align-items-center rounded-top">
                <h4 class="m-0 text-center color-white">
                    Layout Settings
                    <small class="mb-0 opacity-80 d-block">User Interface Settings</small>
                </h4>
                <button type="button" class="close text-white position-absolute pos-top pos-right p-2 m-1 mr-2" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="fal fa-times"></i></span>
                </button>
            </div>
            <div class="modal-body p-0">
                <div class="custom-scroll" style="max-height: calc(100vh - 90px);">
                    <div class="p-3">
                        <h5 class="mt-0 mb-3">App Layout</h5>
                        <div class="d-flex justify-content-between align-items-center mb-3"><div><span class="onoffswitch-title">Fixed Header</span><div class="onoffswitch-title-desc">header is in a fixed at all times</div></div><a href="#" class="btn btn-switch" data-action="toggle" data-class="header-function-fixed"></a></div>
                        <div class="d-flex justify-content-between align-items-center mb-3"><div><span class="onoffswitch-title">Fixed Navigation</span><div class="onoffswitch-title-desc">left panel is fixed</div></div><a href="#" class="btn btn-switch" data-action="toggle" data-class="nav-function-fixed"></a></div>
                        <div class="d-flex justify-content-between align-items-center mb-3"><div><span class="onoffswitch-title">Minify Navigation</span><div class="onoffswitch-title-desc">Skew nav to maximize space</div></div><a href="#" class="btn btn-switch" data-action="toggle" data-class="nav-function-minify"></a></div>
                        <div class="d-flex justify-content-between align-items-center mb-3"><div><span class="onoffswitch-title">Hide Navigation</span><div class="onoffswitch-title-desc">roll mouse on edge to reveal</div></div><a href="#" class="btn btn-switch" data-action="toggle" data-class="nav-function-hidden"></a></div>
                        <div class="d-flex justify-content-between align-items-center mb-4"><div><span class="onoffswitch-title">Boxed Layout</span><div class="onoffswitch-title-desc">Encapsulates to a container</div></div><a href="#" class="btn btn-switch" data-action="toggle" data-class="mod-main-boxed"></a></div>

                        <h5 class="mt-3 mb-3">Mobile Menu</h5>
                        <div class="d-flex justify-content-between align-items-center mb-3"><div><span class="onoffswitch-title">Push Content</span><div class="onoffswitch-title-desc">Content pushed on menu reveal</div></div><a href="#" class="btn btn-switch" data-action="toggle" data-class="nav-mobile-push"></a></div>
                        <div class="d-flex justify-content-between align-items-center mb-4"><div><span class="onoffswitch-title">No Overlay</span><div class="onoffswitch-title-desc">Removes mesh on menu reveal</div></div><a href="#" class="btn btn-switch" data-action="toggle" data-class="mobile-nav-no-overlay"></a></div>

                        <h5 class="mt-3 mb-3">Accessibility</h5>
                        <div class="d-flex justify-content-between align-items-center mb-3"><div><span class="onoffswitch-title">Bigger Content Font</span><div class="onoffswitch-title-desc">content fonts are bigger for readability</div></div><a href="#" class="btn btn-switch" data-action="toggle" data-class="root-text"></a></div>
                        <div class="d-flex justify-content-between align-items-center mb-4"><div><span class="onoffswitch-title">High Contrast Text</span><div class="onoffswitch-title-desc">4.5:1 text contrast ratio</div></div><a href="#" class="btn btn-switch" data-action="toggle" data-class="mod-high-contrast"></a></div>

                        <h5 class="mt-3 mb-3">Global Modifications</h5>
                        <div class="d-flex justify-content-between align-items-center mb-3"><div><span class="onoffswitch-title">Clean Page Background</span><div class="onoffswitch-title-desc">adds more whitespace</div></div><a href="#" class="btn btn-switch" data-action="toggle" data-class="mod-clean-page-bg"></a></div>
                        <div class="d-flex justify-content-between align-items-center mb-3"><div><span class="onoffswitch-title">Disable CSS Animation</span><div class="onoffswitch-title-desc">Disables CSS based animations</div></div><a href="#" class="btn btn-switch" data-action="toggle" data-class="mod-disable-animation"></a></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-messenger" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-right" role="document">
        <div class="modal-content h-100">
            <div class="dropdown-header bg-trans-gradient d-flex justify-content-center align-items-center rounded-top">
                <h4 class="m-0 text-center color-white">Messenger</h4>
            </div>
            <div class="modal-body p-3">
                <p class="text-muted mb-0">Template messenger panel aktif. Konten bisa ditambah nanti.</p>
            </div>
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
