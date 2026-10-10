@props(['title' => 'Vendor Dashboard', 'active' => 'overview'])

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <title>{{ $title }} · Public Market</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="vendor-portal min-h-screen bg-[#f7f9fc] font-[Arial,Helvetica,sans-serif] text-slate-800">
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
                    ['payments', 'Payments', route('vendor.payments'), 'M5 4h14v16H5zM8 8h8M8 12h8M8 16h4'],
                    ['support', 'Help & support', route('vendor.support'), 'M12 21a9 9 0 1 0-9-9 9 9 0 0 0 9 9ZM9.5 9a2.5 2.5 0 1 1 4.25 1.79c-.85.77-1.75 1.2-1.75 2.71M12 17h.01'],
                ] as [$key, $label, $url, $icon])
                    <a @class([
                        'flex items-center gap-3 rounded-md px-3 py-3 text-sm font-medium text-slate-300 no-underline transition',
                        'bg-[#087e69] font-semibold text-white shadow-[inset_-3px_0_0_#08d29c]' => $active === $key,
                        'hover:bg-white/10 hover:text-white' => $active !== $key,
                    ]) href="{{ $url }}">
                        <svg @class(['size-4 shrink-0 fill-none stroke-current stroke-[1.7]', 'fill-current stroke-none' => $active === $key]) viewBox="0 0 24 24" aria-hidden="true">
                            <path d="{{ $icon }}" />
                        </svg>
                        {{ $label }}
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
                <a href="{{ route('vendor.notifications') }}" class="flex items-center gap-2 rounded-md border border-slate-200 px-3 py-2 text-sm font-semibold text-[#087e69]" aria-label="Notifications, {{ $unreadNotificationCount }} unread">
                    <svg class="size-5 fill-none stroke-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" stroke-width="1.7"/></svg>
                    Notifications <span class="rounded-full bg-emerald-100 px-2 py-0.5">{{ $unreadNotificationCount }}</span>
                </a>
            </header>

            <form class="flex justify-end border-b border-slate-200 bg-white px-4 py-1 md:hidden" method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="min-h-11 cursor-pointer rounded-md px-3 py-2 text-sm font-semibold text-[#087e69] hover:bg-emerald-50 focus-visible:outline-2 focus-visible:outline-emerald-700" type="submit">Sign out</button>
            </form>

            <nav class="flex gap-2 overflow-x-auto border-b border-slate-200 bg-[#0d1a31] px-4 py-2 md:hidden" aria-label="Mobile vendor navigation">
                @foreach ([['overview', 'Overview', route('vendor.dashboard')], ['stall', 'My stall', route('vendor.stall')], ['payments', 'Payments', route('vendor.payments')], ['support', 'Help & support', route('vendor.support')]] as [$key, $label, $url])
                    <a @class([
                        'flex min-h-11 shrink-0 items-center rounded-md px-3 py-2 text-xs font-semibold text-slate-300 no-underline transition',
                        'bg-[#087e69] text-white' => $active === $key,
                        'hover:bg-white/10 hover:text-white' => $active !== $key,
                    ]) href="{{ $url }}">{{ $label }}</a>
                @endforeach
            </nav>

            {{ $slot }}
        </main>
    </div>

    <dialog id="payment-receipt-dialog" class="fixed left-1/2 top-1/2 m-0 max-h-[90vh] w-[min(760px,calc(100%-2rem))] max-w-none -translate-x-1/2 -translate-y-1/2 overflow-y-auto rounded-2xl border-0 bg-[#f7f9fc] p-3 shadow-2xl backdrop:bg-slate-950/60 sm:p-5" aria-labelledby="receipt-title">
        <div class="sticky top-0 z-10 mb-4 flex justify-end gap-2 bg-[#f7f9fc]/95 py-2 backdrop-blur">
            <button id="download-payment-receipt" class="app-btn-primary" type="button">Download PNG</button>
            <button id="close-payment-receipt" class="app-btn-secondary" type="button">Close</button>
        </div>
        <article class="mx-auto max-w-[700px] rounded-[24px] border-2 border-slate-200 bg-white px-5 py-6 text-[#344256] shadow-sm sm:px-9 sm:py-7">
            <header class="text-center">
                <h2 class="m-0 text-3xl font-extrabold tracking-tight text-[#08295e] sm:text-4xl">StallTrack</h2>
                <p class="mb-0 mt-0.5 text-sm font-bold text-slate-400">PUBLIC MARKET</p>
                <p id="receipt-title" class="mb-0 mt-3 text-sm font-extrabold text-slate-400">VENDOR PAYMENT RECEIPT</p>
                <p id="receipt-amount" class="mb-5 mt-2 text-4xl font-extrabold tracking-tight text-[#08295e] sm:text-5xl">₱0.00</p>
            </header>
            <dl class="m-0 grid grid-cols-[minmax(0,0.85fr)_minmax(0,1.15fr)] gap-x-3 gap-y-3 text-xs sm:gap-x-6 sm:gap-y-4 sm:text-sm">
                <dt class="font-extrabold uppercase text-slate-500">Reference ID</dt><dd id="receipt-reference" class="m-0 break-all text-right font-extrabold"></dd>
                <dt class="font-extrabold uppercase text-slate-500">Vendor</dt><dd id="receipt-vendor" class="m-0 break-words text-right font-extrabold"></dd>
                <dt class="font-extrabold uppercase text-slate-500">Bill</dt><dd id="receipt-bill" class="m-0 break-words text-right font-extrabold"></dd>
                <dt class="font-extrabold uppercase text-slate-500">Stall</dt><dd id="receipt-stall" class="m-0 break-words text-right font-extrabold"></dd>
                <dt class="font-extrabold uppercase text-slate-500">Payment method</dt><dd id="receipt-method" class="m-0 break-words text-right font-extrabold"></dd>
                <dt class="font-extrabold uppercase text-slate-500">Paid date</dt><dd id="receipt-date" class="m-0 break-words text-right font-extrabold"></dd>
                <dt class="border-t border-slate-200 pt-3 font-extrabold uppercase text-slate-500 sm:pt-4">Billing start</dt><dd id="receipt-start" class="m-0 border-t border-slate-200 pt-3 text-right font-extrabold sm:pt-4"></dd>
                <dt class="font-extrabold uppercase text-slate-500">Billing end</dt><dd id="receipt-end" class="m-0 text-right font-extrabold"></dd>
                <dt class="font-extrabold uppercase text-slate-500">Payment status</dt><dd class="m-0 text-right font-extrabold text-emerald-700">Confirmed</dd>
            </dl>
            <p class="mb-0 mt-8 text-center text-xs font-bold text-slate-400">Generated from StallTrack vendor billing history</p>
        </article>
    </dialog>
</body>
</html>
