<x-layouts.admin :title="$title" :active="$active">
    <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 pb-5">
            <div>
                <p class="mb-2 text-[10px] font-bold uppercase tracking-[.16em] text-[#087e69]">
                    Market operations
                </p>
                <h1 class="m-0 text-2xl font-bold tracking-tight text-[#192235]">
                    {{ $title }}
                </h1>
                <p class="mt-2 mb-0 text-sm text-slate-500">
                    {{ $description }}
                </p>
            </div>

            <a class="inline-flex items-center rounded-md bg-[#087e69] px-4 py-2.5 text-xs font-semibold text-white no-underline hover:bg-[#066b59]" href="{{ route('dashboard') }}">
                Back to dashboard 
                <span class="ml-2 text-base">→</span>
            </a>
        </div>

        <div class="grid min-h-72 place-items-center py-10 text-center">
            <div>
                <div class="mx-auto mb-4 grid size-14 place-items-center rounded-full bg-emerald-50 text-2xl text-[#087e69]">
                    ▤
                </div>
                <h2 class="m-0 text-lg font-semibold text-[#192235]">
                    {{ $title }} workspace
                </h2>
                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">
                    This workspace is ready for market records, operational reporting, and staff actions.
                </p>
            </div>
        </div>
    </section>
</x-layouts.admin>
