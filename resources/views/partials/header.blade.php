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
                <img src="{{ Avatar::create(auth()->user()->name ?? 'User')->toBase64() }}" class="profile-image rounded-circle" alt="{{ auth()->user()->name ?? 'User' }}">
            </a>
            <div class="dropdown-menu dropdown-menu-animated dropdown-lg">
                <div class="dropdown-header bg-trans-gradient d-flex flex-row py-4 rounded-top">
                    <div class="d-flex flex-row align-items-center mt-1 mb-1 color-white">
                        <span class="mr-2"><img src="{{ Avatar::create(auth()->user()->name ?? 'User')->toBase64() }}" class="rounded-circle profile-image" alt="{{ auth()->user()->name ?? 'User' }}"></span>
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
