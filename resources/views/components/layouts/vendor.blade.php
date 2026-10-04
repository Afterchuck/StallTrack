@props(['title' => 'Vendor Dashboard', 'active' => 'overview'])

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} · Public Market</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="vendor-portal min-h-screen bg-[#f7f9fc] font-[Arial,Helvetica,sans-serif] text-slate-800">
    @php($unreadNotificationsCount = auth()->user()->unreadNotifications()->count())
    <div class="flex min-h-screen flex-col md:flex-row">
        <aside class="sticky top-0 hidden h-screen w-60 shrink-0 flex-col border-r border-[#1d2b43] bg-[#0d1a31] md:flex">
            <a class="block border-b border-[#1d2b43] px-6 py-6 text-white no-underline" href="{{ route('vendor.dashboard') }}">
                <strong class="block text-lg font-bold tracking-tight">Stall<span class="text-[#08d29c]">Track</span></strong>
                <small class="mt-1 block text-[10px] font-medium uppercase tracking-[.12em] text-slate-400">
                    Vendor Portal
                </small>
            </a>

            <nav class="grid gap-1 px-4 py-5" aria-label="Vendor navigation">
                @foreach ([
                    ['overview', 'Overview', route('vendor.dashboard'), 'M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z'],
                    ['stall', 'My stall', route('vendor.stall'), 'M6 3h9l3 3v15H6zM15 3v4h4M9 12h6M9 16h6'],
                    ['payment-history', 'Payments', route('vendor.payments'), 'M5 4h14v16H5zM8 8h8M8 12h8M8 16h4'],
                    ['notifications', 'Notifications', route('vendor.notifications'), 'M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4'],
                    ['support', 'Help & support', route('vendor.support.index'), 'M12 18h.01M9.09 9a3 3 0 0 1 5.82 1c0 2-3 3-3 3M22 12a10 10 0 1 1-20 0 10 10 0 0 1 20 0'],
                ] as [$key, $label, $url, $icon])
                    <a @class([
                        'flex items-center gap-3 rounded-md px-3 py-3 text-sm font-medium text-slate-300 no-underline transition hover:bg-white/10 hover:text-white',
                        'bg-[#087e69] font-semibold text-white shadow-[inset_-3px_0_0_#08d29c]' => $active === $key || ($key === 'payment-history' && $active === 'payments'),
                    ]) href="{{ $url }}">
                        <svg @class(['size-4 shrink-0 fill-none stroke-current stroke-[1.7]', 'fill-current stroke-none' => $active === $key]) viewBox="0 0 24 24" aria-hidden="true">
                            <path d="{{ $icon }}" />
                        </svg>
                        {{ $label }}
                        @if ($key === 'notifications' && $unreadNotificationsCount > 0)
                            <span class="ml-auto rounded-full bg-rose-500 px-2 py-0.5 text-[10px] font-bold text-white">{{ $unreadNotificationsCount }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>

            <div class="mt-auto border-t border-[#1d2b43] px-4 py-4">
                <form class="m-0" method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="flex w-full cursor-pointer items-center gap-3 rounded-md border-0 bg-transparent px-3 py-3 text-left text-sm font-medium text-slate-300 transition hover:bg-white/10 hover:text-white" type="submit">
                        <svg class="size-4 shrink-0 fill-none stroke-current stroke-[1.7]" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M10 5H5v14h5M14 8l4 4-4 4M8 12h10" />
                        </svg>
                        Sign out
                    </button>
                </form>

                <div class="mt-4 flex items-center gap-2 border-t border-[#1d2b43] pt-4">
                    <span class="grid size-8 place-items-center rounded-full bg-[#087e69] text-[11px] font-bold text-white">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </span>
                    <div>
                        <strong class="block text-[11px] text-white">
                            {{ auth()->user()->name }}
                        </strong>
                        <small class="mt-0.5 block text-[9px] text-slate-400">
                            Verified vendor
                        </small>
                    </div>
                </div>
            </div>
        </aside>

        <main class="min-w-0 flex-1">
            <header class="flex min-h-16 flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-white px-5 py-3 md:px-8">
                <strong class="text-base font-bold text-slate-900">
                    {{ $title }}
                </strong>
                <div class="flex items-center gap-4">
                    <span class="hidden items-center gap-1.5 text-xs text-slate-500 xl:flex">
                        <i class="size-1.5 rounded-full bg-[#08b98a]"></i>
                        Lease active until Dec 2026
                    </span>
                </div>
            </header>

            <nav class="flex gap-2 overflow-x-auto border-b border-slate-200 bg-[#0d1a31] px-4 py-2 md:hidden" aria-label="Mobile vendor navigation">
                @foreach ([['overview', 'Overview', route('vendor.dashboard')], ['stall', 'My stall', route('vendor.stall')], ['payment-history', 'Payments', route('vendor.payments')], ['notifications', 'Notifications', route('vendor.notifications')], ['support', 'Help & support', route('vendor.support.index')]] as [$key, $label, $url])
                    <a @class([
                        'shrink-0 rounded-md px-3 py-2 text-xs font-semibold text-slate-300 no-underline transition hover:bg-white/10 hover:text-white',
                        'bg-[#087e69] text-white' => $active === $key || ($key === 'payment-history' && $active === 'payments'),
                    ]) href="{{ $url }}">{{ $label }}@if ($key === 'notifications' && $unreadNotificationsCount > 0) <span class="ml-1 rounded-full bg-rose-500 px-1.5 py-0.5 text-[9px] text-white">{{ $unreadNotificationsCount }}</span>@endif</a>
                @endforeach
            </nav>

            {{ $slot }}
        </main>
    </div>
</body>
</html>
