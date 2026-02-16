<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, user-scalable=no, minimal-ui">
    <title>@yield('title', 'LOS Dashboard - SmartAdmin')</title>

    <link rel="stylesheet" media="screen, print" href="{{ asset('assets/smartadmin4/css/vendors.bundle.css') }}">
    <link rel="stylesheet" media="screen, print" href="{{ asset('assets/smartadmin4/css/app.bundle.css') }}">
    <style id="global-icon-text-spacing">
    /* global-icon-text-spacing */
    .modal .modal-title i[class*="fa-"],
    .modal .btn i[class*="fa-"],
    .modal .dropdown-item i[class*="fa-"],
    .modal .nav-link i[class*="fa-"],
    .dropdown-menu .dropdown-item i[class*="fa-"],
    .dropdown-menu .nav-link i[class*="fa-"],
    .dropdown-menu .app-list-item i[class*="fa-"],
    .swal2-popup i[class*="fa-"] {
        margin-right: .6rem !important;
    }

    .modal .dropdown-item,
    .modal .nav-link,
    .dropdown-menu .dropdown-item,
    .dropdown-menu .nav-link,
    .dropdown-menu .app-list-item {
        display: flex;
        align-items: center;
        gap: .6rem;
    }

    .modal .btn,
    .dropdown-menu .btn {
        display: inline-flex;
        align-items: center;
        gap: .55rem;
    }
    </style>
    @yield('styles')
</head>
<body class="mod-bg-1 nav-function-fixed header-function-fixed">
<script>
'use strict';
var classHolder = document.getElementsByTagName('BODY')[0],
    themeSettings = localStorage.getItem('themeSettings') ? JSON.parse(localStorage.getItem('themeSettings')) : {},
    themeURL = themeSettings.themeURL || '';

if (themeSettings.themeOptions) {
    classHolder.className = themeSettings.themeOptions;
}

if (themeURL && !document.getElementById('mytheme')) {
    var cssfile = document.createElement('link');
    cssfile.id = 'mytheme';
    cssfile.rel = 'stylesheet';
    cssfile.href = themeURL;
    document.getElementsByTagName('head')[0].appendChild(cssfile);
}

function saveSettings() {
    themeSettings.themeOptions = String(classHolder.className).split(/[^\w-]+/).filter(function (item) {
        return /^(nav|header|mod|display)-/i.test(item);
    }).join(' ');

    if (document.getElementById('mytheme')) {
        themeSettings.themeURL = document.getElementById('mytheme').getAttribute('href');
    }

    localStorage.setItem('themeSettings', JSON.stringify(themeSettings));
}

function resetSettings() {
    localStorage.setItem('themeSettings', '');
    window.location.reload();
}
</script>

<div class="page-wrapper">
    <div class="page-inner">
        @include('partials.sidebar')

        <div class="page-content-wrapper">
            @include('partials.header')
            <main id="js-page-content" role="main" class="page-content">
                @yield('content')
            </main>
        </div>
    </div>
</div>
        @include('partials.modals')

<script src="{{ asset('assets/smartadmin4/js/vendors.bundle.js') }}"></script>
<script src="{{ asset('assets/smartadmin4/js/app.bundle.js') }}"></script>
<script src="{{ asset('assets/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/chart.js') }}"></script>
<script src="{{ asset('assets/xlsx.full.min.js') }}"></script>

@yield('scripts')
</body>
</html>





