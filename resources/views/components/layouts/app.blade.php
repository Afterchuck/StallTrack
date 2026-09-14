<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'StallTrack' }} · StallTrack</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-page">
    <header class="admin-header">
        <a class="admin-brand" href="{{ route('dashboard') }}"><span class="admin-brand-mark">▥</span><strong>Stall<span>Track</span></strong></a>
        <nav class="admin-nav" aria-label="Main navigation">
            <a class="{{ ($active ?? 'dashboard') === 'dashboard' ? 'active' : '' }}" href="{{ route('dashboard') }}">Dashboard</a>
            <a class="{{ ($active ?? '') === 'vendors' ? 'active' : '' }}" href="{{ route('vendors.index') }}">Vendors</a>
            <a class="{{ ($active ?? '') === 'stalls' ? 'active' : '' }}" href="{{ route('stalls') }}">Stalls</a>
            <a class="{{ ($active ?? '') === 'rentals' ? 'active' : '' }}" href="{{ route('rentals') }}">Rentals</a>
            <a class="{{ ($active ?? '') === 'payments' ? 'active' : '' }}" href="{{ route('payments') }}">Collections</a>
            <a class="{{ ($active ?? '') === 'reports' ? 'active' : '' }}" href="{{ route('reports') }}">Reports</a>
        </nav>
        <details class="admin-account">
            <summary class="admin-user"><div><strong>{{ auth()->user()->name ?? 'Administrator' }}</strong><small>Market Administrator</small></div><span>{{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}</span></summary>
            <div class="admin-account-menu"><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">↪ &nbsp; Log out</button></form></div>
        </details>
    </header>
    <main class="admin-content">{{ $slot }}</main>
    <footer class="admin-footer"><span>© 2026 StallTrack Systems. All rights reserved.</span><span><a href="#">Privacy Policy</a><a href="#">Terms of Service</a><a href="#">System Support</a></span></footer>
</body>
</html>
