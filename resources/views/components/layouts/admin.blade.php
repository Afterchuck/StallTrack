<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'StallTrack' }} · StallTrack</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-[#f7f9fc] font-[Arial,Helvetica,sans-serif] text-[#192235]">
    @php($openSupportRequestsCount = \App\Models\VendorSupportRequest::whereIn('status', ['Open', 'In Progress'])->count())
    <header class="sticky top-0 z-40 flex min-h-16 flex-wrap items-center gap-7 bg-[#0d1a31] px-4 text-white shadow-md md:flex-nowrap md:px-8">
        <a class="flex items-center gap-2.5 text-lg font-bold text-white no-underline" href="{{ route('dashboard') }}">
            <span class="grid size-5 place-items-center rounded bg-[#08b98a] text-[11px]">▥</span>
            <strong>Stall<span class="text-[#08d29c]">Track</span></strong>
        </a>

        <nav class="order-3 flex w-full items-center gap-2 overflow-x-auto md:order-none md:w-auto" aria-label="Main navigation">
            @foreach (['dashboard' => ['Dashboard', route('dashboard')], 'vendors' => ['Vendors', route('vendors.index')], 'stalls' => ['Stalls', route('stalls')], 'rentals' => ['Rentals', route('rentals')], 'payments' => ['Collections', route('payments')], 'reports' => ['Reports', route('reports')], 'support' => ['Support inbox', route('admin.support.index')]] as $key => [$label, $url])
                <a @class(['rounded-md px-4 py-2.5 text-sm font-medium text-slate-300 no-underline transition hover:bg-white/10 hover:text-white', 'bg-[#087e69] text-white' => ($active ?? 'dashboard') === $key]) href="{{ $url }}">
                    {{ $label }}
                    @if ($key === 'support' && $openSupportRequestsCount > 0)
                        <span class="ml-1 rounded-full bg-rose-500 px-2 py-0.5 text-[10px] font-bold text-white">{{ $openSupportRequestsCount }}</span>
                    @endif
                </a>
            @endforeach
        </nav>

        <details class="relative ml-auto">
            <summary class="flex cursor-pointer list-none items-center gap-2">
                <div class="text-right">
                    <strong class="block text-xs">{{ auth()->user()->name ?? 'Eleanor Vance' }}</strong>
                    <small class="block text-[10px] text-[#08d29c]">Market Admin</small>
                </div>
                <span class="grid size-8 place-items-center rounded-full bg-[#087e69] text-xs font-bold">
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

    <main class="mx-auto w-full max-w-[1280px] flex-1 px-4 py-6 md:px-8 md:py-8">
        {{ $slot }}
    </main>

    <footer class="flex flex-col gap-3 border-t border-slate-200 bg-white px-4 py-4 text-[10px] text-slate-400 sm:flex-row sm:items-center sm:justify-between md:px-8">
        <span>
            © 2026 StallTrack Systems. All rights reserved.
        </span>
        <span class="flex flex-wrap gap-x-4 gap-y-2">
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
