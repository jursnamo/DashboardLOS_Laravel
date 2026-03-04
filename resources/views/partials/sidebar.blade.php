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
            <img src="{{ Avatar::create(auth()->user()->name ?? 'User')->toBase64() }}" class="profile-image rounded-circle" alt="{{ auth()->user()->name ?? 'User' }}">
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
            <li class="{{ (request()->is('dashboard*') || request()->is('parameters*') || request()->is('import*') || request()->is('los*')) ? 'active open' : '' }}">
                <a href="#" title="LOS Menu">
                    <i class="fal fa-chart-area"></i>
                    <span class="nav-link-text">LOS Panel</span>
                </a>
                <ul>
                    <li id="menu-dashboard" class="{{ request()->is('dashboard*') ? 'active' : '' }}">
                        <a href="{{ route('dashboard.index') }}" title="Dashboard">
                            <span class="nav-link-text">Dashboard</span>
                        </a>
                    </li>
                    <li id="menu-import">
                        <a href="#" title="Import Data" onclick="openImportDataMenu(); return false;">
                            <span class="nav-link-text">Import Data</span>
                        </a>
                    </li>
                    <li id="menu-datamart">
                        <a href="#" title="Execute Datamart Job" onclick="triggerDatamartJob(); return false;">
                            <span class="nav-link-text">Execute Datamart Job</span>
                        </a>
                    </li>
                    <li class="{{ request()->is('los/applications*') ? 'active' : '' }}">
                        <a href="{{ route('los.applications.index') }}" title="Loan Origination">
                            <span class="nav-link-text">Loan Origination</span>
                        </a>
                    </li>
                    <li class="{{ request()->is('los/monitoring*') ? 'active' : '' }}">
                        <a href="{{ route('los.monitoring.index') }}" title="Monitoring SLA">
                            <span class="nav-link-text">Monitoring SLA</span>
                        </a>
                    </li>
                </ul>
            </li>
            <li class="{{ request()->is('parameters*') ? 'active open' : '' }}">
                <a href="#" title="Parameter Menu">
                    <i class="fal fa-cog"></i>
                    <span class="nav-link-text">Parameter</span>
                </a>
                <ul>
                    <li class="{{ request()->is('parameters/branches*') ? 'active' : '' }}"><a href="{{ url('parameters/branches') }}"><span class="nav-link-text">Branches</span></a></li>
                    <li class="{{ request()->is('parameters/rms*') ? 'active' : '' }}"><a href="{{ url('parameters/rms') }}"><span class="nav-link-text">Relationship Managers</span></a></li>
                    <li class="{{ request()->is('parameters/bi_industries*') ? 'active' : '' }}"><a href="{{ url('parameters/bi_industries') }}"><span class="nav-link-text">BI Industries</span></a></li>
                    <li class="{{ request()->is('parameters/cimb_sectors*') ? 'active' : '' }}"><a href="{{ url('parameters/cimb_sectors') }}"><span class="nav-link-text">CIMB Sectors</span></a></li>
                    <li class="{{ request()->is('parameters/constitutions*') ? 'active' : '' }}"><a href="{{ url('parameters/constitutions') }}"><span class="nav-link-text">Constitutions</span></a></li>
                    <li class="{{ request()->is('parameters/economy_sectors*') ? 'active' : '' }}"><a href="{{ url('parameters/economy_sectors') }}"><span class="nav-link-text">Economy Sectors</span></a></li>
                    <li class="{{ request()->is('parameters/bi_collectabilities*') ? 'active' : '' }}"><a href="{{ url('parameters/bi_collectabilities') }}"><span class="nav-link-text">BI Collectability</span></a></li>
                    <li class="{{ request()->is('parameters/basel_types*') ? 'active' : '' }}"><a href="{{ url('parameters/basel_types') }}"><span class="nav-link-text">Basel Types</span></a></li>
                </ul>
            </li>
        </ul>
        <div class="filter-message js-filter-message bg-success-600"></div>
    </nav>
</aside>
