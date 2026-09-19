<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'StallTrack' }} · StallTrack</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f7f9fc] font-[Arial,Helvetica,sans-serif] text-[#192235]">
    <header class="flex min-h-11 items-center gap-5 bg-[#0d1a31] px-5 text-white">
        <a class="flex items-center gap-2 text-sm font-bold text-white no-underline" href="{{ route('dashboard') }}">
            <span class="grid size-5 place-items-center rounded bg-[#08b98a] text-[11px]">▥</span>
            <strong>Stall<span class="text-[#08d29c]">Track</span></strong>
        </a>

        <nav class="flex items-center gap-1" aria-label="Main navigation">
            @foreach (['dashboard' => ['Dashboard', route('dashboard')], 'vendors' => ['Vendors', route('vendors.index')], 'stalls' => ['Stalls', route('stalls')], 'rentals' => ['Rentals', route('rentals')], 'payments' => ['Collections', route('payments')], 'reports' => ['Reports', route('reports')]] as $key => [$label, $url])
                <a @class(['rounded px-3 py-1.5 text-[10px] font-medium text-slate-300 no-underline hover:bg-white/10 hover:text-white', 'bg-[#087e69] text-white' => ($active ?? 'dashboard') === $key]) href="{{ $url }}">
                    {{ $label }}
                </a>
            @endforeach
        </nav>

        <details class="relative ml-auto">
            <summary class="flex cursor-pointer list-none items-center gap-2">
                <div class="text-right">
                    <strong class="block text-[10px]">{{ auth()->user()->name ?? 'Eleanor Vance' }}</strong>
                    <small class="block text-[8px] text-[#08d29c]">Market Admin</small>
                </div>
                <span class="grid size-6 place-items-center rounded-full bg-[#087e69] text-[9px] font-bold">
                    {{ strtoupper(substr(auth()->user()->name ?? 'E', 0, 1)) }}
                </span>
            </summary>

            <div class="absolute top-[calc(100%+8px)] right-0 z-30 w-36 rounded border border-slate-200 bg-white p-1 shadow-lg">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="w-full cursor-pointer rounded border-0 bg-transparent px-3 py-2 text-left text-xs text-slate-700 hover:bg-emerald-50" type="submit">
                        ↪ Log out
                    </button>
                </form>
            </div>
        </details>
    </header>

    <main class="mx-auto w-full max-w-[1280px] px-5 py-4">
        {{ $slot }}
    </main>

    <footer class="flex justify-between border-t border-slate-200 bg-white px-5 py-3 text-[9px] text-slate-400">
        <span>
            © 2026 StallTrack Systems. All rights reserved.
        </span>
        <span class="flex gap-4">
            <a class="text-slate-400 no-underline" href="#">
                Privacy Policy
            </a>
            <a class="text-slate-400 no-underline" href="#">
                Terms of Service
            </a>
            <a class="text-slate-400 no-underline" href="#">
                System Support
            </a>
        </span>
    </footer>
</body>
</html>