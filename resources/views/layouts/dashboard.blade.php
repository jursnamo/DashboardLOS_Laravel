<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'LOS Dashboard - Advanced Analytics')</title>

    <link href="{{ asset('assets/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('assets/dashboard.css') }}">
</head>
<body>
    @yield('content')

    <script src="{{ asset('assets/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/chart.js') }}"></script>
    <script src="{{ asset('assets/xlsx.full.min.js') }}"></script>
    <script src="{{ asset('assets/dashboard.js') }}"></script>
</body>
</html>
