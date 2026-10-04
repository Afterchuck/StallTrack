<x-layouts.admin title="Support Inbox" active="support">
    <section>
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="m-0 text-[10px] font-semibold uppercase tracking-[.12em] text-emerald-700">Vendor services</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Support inbox</h1>
                <p class="mt-2 text-sm text-slate-500">Review vendor questions and send replies from one place.</p>
            </div>
            <form class="flex items-center gap-2" method="GET" action="{{ route('admin.support.index') }}">
                <label class="sr-only" for="support-status">Filter by status</label>
                <select class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm" id="support-status" name="status">
                    <option value="">All statuses</option>
                    @foreach (['Open', 'In Progress', 'Resolved'] as $option)
                        <option value="{{ $option }}" @selected($status === $option)>{{ $option }}</option>
                    @endforeach
                </select>
                <button class="rounded-md bg-[#087e69] px-4 py-2 text-sm font-semibold text-white" type="submit">Filter</button>
            </form>
        </div>

        <section class="mt-7 overflow-hidden rounded-md border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] border-collapse text-left">
                    <thead><tr class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500"><th class="px-5 py-3">Request</th><th class="px-5 py-3">Vendor</th><th class="px-5 py-3">Received</th><th class="px-5 py-3">Status</th><th class="px-5 py-3"><span class="sr-only">Action</span></th></tr></thead>
                    <tbody>
                        @forelse ($supportRequests as $supportRequest)
                            <tr class="border-t border-slate-100">
                                <td class="px-5 py-4"><a class="font-semibold text-emerald-800 no-underline hover:underline" href="{{ route('admin.support.show', $supportRequest) }}">{{ $supportRequest->subject }}</a><small class="mt-1 block text-xs text-slate-500">{{ $supportRequest->category }}</small></td>
                                <td class="px-5 py-4 text-sm text-slate-700">{{ $supportRequest->user->name }}<small class="mt-1 block text-xs text-slate-500">{{ $supportRequest->user->email }}</small></td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ $supportRequest->created_at->format('M j, Y g:i A') }}</td>
                                <td class="px-5 py-4"><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $supportRequest->status }}</span></td>
                                <td class="px-5 py-4 text-right"><a class="text-sm font-semibold text-emerald-800 no-underline hover:underline" href="{{ route('admin.support.show', $supportRequest) }}">Review</a></td>
                            </tr>
                        @empty
                            <tr><td class="px-5 py-14 text-center text-sm text-slate-500" colspan="5">No support requests match this filter.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($supportRequests->hasPages())
                <div class="border-t border-slate-200 px-5 py-4">{{ $supportRequests->links() }}</div>
            @endif
        </section>
    </section>
</x-layouts.admin>
