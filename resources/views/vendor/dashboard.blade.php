<x-layouts.vendor title="Vendor Dashboard">
    @php($vendorPayments = $payments ?? collect())
    @php($firstName = explode(' ', auth()->user()->name)[0])

    <div class="mx-auto max-w-[1180px] px-5 py-8 md:px-8 md:py-10">
        <p class="m-0 text-[10px] font-semibold tracking-[.12em] text-[#777]">
            STALL A-14 · MUNICIPAL MARKET
        </p>

        <section class="mb-8 mt-2 flex items-end justify-between gap-5">
            <div>
                <h1 class="m-0 text-[28px] font-bold tracking-[-.03em] text-[#222]">
                    Good day, {{ $firstName }}
                </h1>
                <p class="mb-0 mt-2 text-sm text-[#737373]">
                    Here is a quick view of your stall, lease, and payment records.
                </p>
            </div>
            <a class="hidden shrink-0 text-xs font-semibold text-[#252525] no-underline hover:underline sm:block" href="#lease">
                View lease details →
            </a>
        </section>

        @if (session('success'))
            <div class="mb-5 rounded-[3px] border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
                {{ session('success') }}
            </div>
        @endif

        <section class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Account summary">
            @foreach ([['Current balance', '₱1,200.00', 'Due Aug 30, 2026', true], ['Monthly stall rent', '₱1,200.00', 'Next cycle: Sep 01, 2026', false], ['Lease status', 'Active', 'Valid through Dec 31, 2026', false], ['Payments on file', $vendorPayments->count() ?: 4, 'Most recent activity', false]] as [$label, $value, $detail, $isAlert])
                <article class="border border-[#e0e0e0] bg-white px-5 py-4">
                    <span class="block text-[10px] font-semibold uppercase tracking-[.1em] text-[#777]">
                        {{ $label }}
                    </span>
                    <strong @class(['mt-2 block text-xl font-bold tracking-tight text-[#222]', 'text-emerald-700' => $label === 'Lease status'])>
                        {{ $value }}
                    </strong>
                    <small @class(['mt-2 block text-[10px]', 'text-rose-600' => $isAlert, 'text-[#777]' => ! $isAlert])>
                        {{ $detail }}
                    </small>
                </article>
            @endforeach
        </section>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_280px]">
            <div class="grid content-start gap-5">
                <section class="flex items-center gap-4 border border-[#f0d3a2] bg-[#fffaf0] px-5 py-4">
                    <div class="grid size-7 shrink-0 place-items-center rounded-full bg-[#d99627] text-sm font-bold text-white">
                        !
                    </div>
                    <div>
                        <h2 class="m-0 text-base font-bold tracking-tight text-[#292929]">
                            August stall rent is due
                        </h2>
                        <p class="mb-0 mt-1.5 text-xs leading-5 text-[#6d6d6d]">
                            Your monthly rental fee of ₱1,200.00 is due on August 30, 2026.
                        </p>
                    </div>
                    <a class="ml-auto shrink-0 text-xs font-semibold text-[#573b0b] no-underline hover:underline" href="#payment-form">
                        Pay now →
                    </a>
                </section>

                <section class="border border-[#e0e0e0] bg-white p-5" id="payments">
                    <div class="mb-5 flex items-start justify-between gap-3">
                        <div>
                            <h2 class="m-0 text-base font-bold tracking-tight text-[#292929]">
                                Payment history
                            </h2>
                            <p class="mb-0 mt-1.5 text-xs leading-5 text-[#6d6d6d]">
                                Payments submitted to Market Treasury.
                            </p>
                        </div>
                        <a class="shrink-0 text-xs font-semibold text-[#252525] no-underline hover:underline" href="#payment-form">
                            Submit payment
                        </a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[520px] border-collapse text-left">
                            <thead>
                                <tr>
                                    @foreach (['Receipt', 'Date', 'Amount', 'Status'] as $heading)
                                        <th class="border-y border-[#e5e5e5] bg-[#fafafa] px-3 py-3 text-[10px] font-semibold uppercase tracking-[.08em] text-[#777]">
                                            {{ $heading }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($vendorPayments->take(4) as $payment)
                                    <tr>
                                        <td class="border-b border-[#ededed] px-3 py-3 text-xs text-[#444]">
                                            {{ $payment->receipt_number }}
                                        </td>
                                        <td class="border-b border-[#ededed] px-3 py-3 text-xs text-[#444]">
                                            {{ $payment->paid_at->format('M d, Y') }}
                                        </td>
                                        <td class="border-b border-[#ededed] px-3 py-3 text-xs font-semibold text-[#292929]">
                                            ₱{{ number_format((float) $payment->amount, 2) }}
                                        </td>
                                        <td class="border-b border-[#ededed] px-3 py-3 text-xs text-[#444]">
                                            <span class="inline-flex rounded-sm bg-amber-100 px-2 py-1 text-[9px] font-semibold text-amber-700">
                                                Pending review
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    @foreach ([['OR-0062', 'Aug 26, 2026', '₱1,200.00'], ['OR-0061', 'Jul 28, 2026', '₱1,200.00'], ['OR-0060', 'Jun 25, 2026', '₱1,200.00']] as [$receipt, $date, $amount])
                                        <tr>
                                            <td class="border-b border-[#ededed] px-3 py-3 text-xs text-[#444]">
                                                {{ $receipt }}
                                            </td>
                                            <td class="border-b border-[#ededed] px-3 py-3 text-xs text-[#444]">
                                                {{ $date }}
                                            </td>
                                            <td class="border-b border-[#ededed] px-3 py-3 text-xs font-semibold text-[#292929]">
                                                {{ $amount }}
                                            </td>
                                            <td class="border-b border-[#ededed] px-3 py-3 text-xs text-[#444]">
                                                <span class="inline-flex rounded-sm bg-emerald-100 px-2 py-1 text-[9px] font-semibold text-emerald-700">
                                                    Verified
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="scroll-mt-5 border border-[#e0e0e0] bg-white p-5" id="payment-form">
                    <div class="mb-5">
                        <h2 class="m-0 text-base font-bold tracking-tight text-[#292929]">
                            Record a payment
                        </h2>
                        <p class="mb-0 mt-1.5 text-xs leading-5 text-[#6d6d6d]">
                            Enter the details from your official payment receipt.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('vendor.payments.store') }}" class="grid gap-3 md:grid-cols-2">
                        @csrf

                        <label class="grid gap-2 text-[10px] font-semibold uppercase tracking-[.08em] text-[#666]">
                            Amount paid 
                            <span class="relative">
                                <b class="absolute top-1/2 left-3 -translate-y-1/2 text-sm font-normal text-[#777]">₱</b>
                                <input class="h-10 w-full rounded-[3px] border border-[#d8d8d8] bg-white py-0 pr-3 pl-7 text-xs font-normal tracking-normal text-[#333] placeholder:text-[#aaa] focus:border-[#555] focus:ring-2 focus:ring-black/10" name="amount" type="number" min="0.01" step="0.01" placeholder="1,200.00" required>
                            </span>
                        </label>

                        <label class="grid gap-2 text-[10px] font-semibold uppercase tracking-[.08em] text-[#666]">
                            Payment date 
                            <input class="h-10 w-full rounded-[3px] border border-[#d8d8d8] bg-white px-3 text-xs font-normal tracking-normal text-[#333] focus:border-[#555] focus:ring-2 focus:ring-black/10" name="paid_at" type="date" value="{{ now()->format('Y-m-d') }}" required>
                        </label>

                        <label class="grid gap-2 text-[10px] font-semibold uppercase tracking-[.08em] text-[#666]">
                            Receipt number 
                            <input class="h-10 w-full rounded-[3px] border border-[#d8d8d8] bg-white px-3 text-xs font-normal tracking-normal text-[#333] placeholder:text-[#aaa] focus:border-[#555] focus:ring-2 focus:ring-black/10" name="receipt_number" placeholder="OR-0063" required>
                        </label>

                        <button class="self-end rounded-[3px] bg-[#191919] px-4 py-3 text-xs font-semibold normal-case tracking-normal text-white hover:bg-black md:col-start-2" type="submit">
                            Submit payment →
                        </button>
                    </form>
                </section>
            </div>

            <aside class="grid content-start gap-5">
                <section class="border border-[#e0e0e0] bg-white p-5" id="lease">
                    <span class="text-[10px] font-semibold uppercase tracking-[.1em] text-[#888]">
                        My stall
                    </span>
                    <h2 class="mt-2 text-base font-bold tracking-tight text-[#292929]">
                        Stall A-14
                    </h2>
                    <p class="mb-0 mt-1.5 text-xs leading-5 text-[#6d6d6d]">
                        Dry goods & fresh produce · Section A
                    </p>

                    <dl class="my-5 grid gap-3 border-y border-[#e5e5e5] py-4">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-[10px] text-[#777]">Lease period</dt>
                            <dd class="m-0 text-[11px] font-semibold text-[#333]">Jan 2024 – Dec 2026</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-[10px] text-[#777]">Monthly rent</dt>
                            <dd class="m-0 text-[11px] font-semibold text-[#333]">₱1,200.00</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-[10px] text-[#777]">Stall status</dt>
                            <dd class="m-0">
                                <span class="inline-flex rounded-sm bg-emerald-100 px-2 py-1 text-[9px] font-semibold text-emerald-700">
                                    Active
                                </span>
                            </dd>
                        </div>
                    </dl>

                    <a class="text-xs font-semibold text-[#252525] no-underline hover:underline" href="#lease">
                        View full lease →
                    </a>
                </section>

                <section class="border border-[#e0e0e0] bg-white p-5" id="notices">
                    <span class="text-[10px] font-semibold uppercase tracking-[.1em] text-[#888]">
                        Notice
                    </span>
                    <h2 class="mt-2 text-base font-bold tracking-tight text-[#292929]">
                        Keep your permit visible
                    </h2>
                    <p class="mb-0 mt-1.5 text-xs leading-5 text-[#6d6d6d]">
                        Display your current Mayor’s Permit on the front of your stall at all times.
                    </p>
                    <a class="mt-5 block text-xs font-semibold text-[#252525] no-underline hover:underline" href="#notices">
                        Read market guidelines →
                    </a>
                </section>

                <section class="bg-[#232323] p-5 text-white" id="support">
                    <span class="text-[10px] font-semibold uppercase tracking-[.1em] text-[#b2b2b2]">
                        Need help?
                    </span>
                    <h2 class="mt-2 text-base font-bold tracking-tight">
                        Market support
                    </h2>
                    <p class="mb-0 mt-1.5 text-xs leading-5 text-[#c9c9c9]">
                        For payment questions or stall concerns, visit the Market Admin Office.
                    </p>
                    <strong class="mt-4 block text-sm">(084) 216-9080</strong>
                    <small class="mt-4 block text-[10px] text-[#b2b2b2]">Monday–Friday, 8:00 AM–4:00 PM</small>
                </section>
            </aside>
        </div>
    </div>
</x-layouts.vendor>