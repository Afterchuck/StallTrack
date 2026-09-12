<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Management Portal' }} · Public Market</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="portal-page">
    <aside class="sidebar">
        <div class="portal-label">Management Portal</div>
        <a class="register-vendor" href="{{ route('vendors.create') }}"><span>＋</span> Register Vendor</a>
        <nav class="portal-nav" aria-label="Main navigation">
            <a class="{{ ($active ?? 'vendors') === 'vendors' ? 'active' : '' }}" href="{{ route('dashboard') }}">Vendors</a>
            <a class="{{ ($active ?? '') === 'payments' ? 'active' : '' }}" href="{{ route('payments') }}">Payments</a>
            <a class="{{ ($active ?? '') === 'due-dates' ? 'active' : '' }}" href="{{ route('due-dates') }}">Due Dates</a>
            <a class="{{ ($active ?? '') === 'reports' ? 'active' : '' }}" href="{{ route('reports') }}">Reports</a>
        </nav>
        <form class="sidebar-logout" method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Sign out</button></form>
    </aside>
    <main class="portal-content">{{ $slot }}</main>
</body>
</html>
