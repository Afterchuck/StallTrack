@props(['title' => 'Vendor Dashboard'])

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} · Public Market</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#fbfbfb] font-[Arial,Helvetica,sans-serif] text-[#262626]">
    <div class="flex min-h-screen">
        <aside class="sticky top-0 flex h-screen w-56 shrink-0 flex-col border-r border-[#dedede] bg-[#f7f7f7] max-md:hidden">
            <a class="block border-b border-[#dedede] px-6 py-5 text-[#242424] no-underline" href="{{ route('vendor.dashboard') }}">
                <strong class="block text-base font-bold tracking-tight">Public Market</strong>
                <small class="mt-1 block text-[10px] font-medium uppercase tracking-[.12em] text-[#777]">
                    Vendor Portal
                </small>
            </a>

            <a class="mx-4 mt-5 flex items-center justify-center gap-2 rounded-[3px] bg-[#171717] px-3 py-3 text-xs font-semibold tracking-wide text-white no-underline transition hover:bg-black" href="#payment-form">
                <span class="text-base font-normal leading-none" aria-hidden="true">+</span> 
                Record payment
            </a>

            <nav class="grid gap-1 px-4 py-5" aria-label="Vendor navigation">
                @foreach ([
                    ['Overview', route('vendor.dashboard'), 'M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z'],
                    ['My stall', '#lease', 'M6 3h9l3 3v15H6zM15 3v4h4M9 12h6M9 16h6'],
                    ['Payments', '#payments', 'M5 4h14v16H5zM8 8h8M8 12h8M8 16h4'],
                    ['Notices', '#notices', 'M5 5h14v10H9l-4 4zM8 9h8'],
                ] as [$label, $url, $icon])
                    <a @class([
                        'flex items-center gap-3 rounded-[3px] px-3 py-2.5 text-xs font-medium text-[#575757] no-underline transition hover:bg-[#e9e9e9] hover:text-[#111]',
                        'bg-[#e2e2e2] font-semibold text-[#171717] shadow-[inset_-2px_0_0_#222]' => $label === 'Overview',
                    ]) href="{{ $url }}">
                        <svg @class(['size-4 shrink-0 fill-none stroke-current stroke-[1.7]', 'fill-current stroke-none' => $label === 'Overview']) viewBox="0 0 24 24" aria-hidden="true">
                            <path d="{{ $icon }}" />
                        </svg>
                        {{ $label }}
                    </a>
                @endforeach
            </nav>

            <div class="mt-auto border-t border-[#dedede] px-4 py-4">
                <a class="flex items-center gap-3 rounded-[3px] px-3 py-2.5 text-xs font-medium text-[#575757] no-underline transition hover:bg-[#e9e9e9] hover:text-[#111]" href="#support">
                    <svg class="size-4 shrink-0 fill-none stroke-current stroke-[1.7]" viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="12" cy="12" r="8" />
                        <path d="M12 8v4M12 16h.01" />
                    </svg>
                    Market support
                </a>

                <form class="m-0" method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="flex w-full cursor-pointer items-center gap-3 rounded-[3px] border-0 bg-transparent px-3 py-2.5 text-left text-xs font-medium text-[#575757] transition hover:bg-[#e9e9e9] hover:text-[#111]" type="submit">
                        <svg class="size-4 shrink-0 fill-none stroke-current stroke-[1.7]" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M10 5H5v14h5M14 8l4 4-4 4M8 12h10" />
                        </svg>
                        Sign out
                    </button>
                </form>

                <div class="mt-4 flex items-center gap-2 border-t border-[#dedede] pt-4">
                    <span class="grid size-8 place-items-center rounded-full bg-[#222] text-[11px] font-bold text-white">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </span>
                    <div>
                        <strong class="block text-[11px] text-[#333]">
                            {{ auth()->user()->name }}
                        </strong>
                        <small class="mt-0.5 block text-[9px] text-[#777]">
                            Verified vendor
                        </small>
                    </div>
                </div>
            </div>
        </aside>

        <main class="min-w-0 flex-1">
            <header class="flex h-16 items-center justify-between border-b border-[#dedede] bg-white px-5 md:px-8">
                <strong class="text-sm font-bold text-[#333]">
                    {{ $title }}
                </strong>
                <div class="flex items-center gap-4">
                    <span class="hidden items-center gap-1.5 text-[11px] text-[#6e6e6e] xl:flex">
                        <i class="size-1.5 rounded-full bg-emerald-500"></i> 
                        Lease active until Dec 2026
                    </span>
                    <a class="rounded-[3px] border border-[#ddd] bg-white px-3 py-2 text-[10px] font-semibold text-[#333] no-underline hover:bg-[#f7f7f7]" href="#payment-form">
                        Record payment
                    </a>
                </div>
            </header>

            {{ $slot }}
        </main>
    </div>
</body>
</html>