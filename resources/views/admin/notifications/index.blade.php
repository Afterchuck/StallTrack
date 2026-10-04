<x-layouts.admin title="Admin Notifications" active="notifications">
    <section class="mx-auto max-w-4xl">
        <div class="mb-6">
            <p class="m-0 text-[10px] font-semibold uppercase tracking-[.12em] text-emerald-700">Admin updates</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Notifications</h1>
            <p class="mt-2 text-sm text-slate-500">Messages sent to you by vendors appear here.</p>
        </div>

        <section class="overflow-hidden rounded-md border border-slate-200 bg-white">
            <div class="divide-y divide-slate-100">
                @forelse ($notifications as $notification)
                    @php($requestExists = \App\Models\VendorSupportRequest::whereKey($notification->data['support_request_id'] ?? 0)->exists())
                    <article class="flex flex-wrap items-center justify-between gap-4 px-5 py-4 {{ $notification->read_at ? 'bg-white' : 'bg-emerald-50/60' }}">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="m-0 text-sm font-semibold text-slate-900">{{ $notification->data['title'] ?? 'Vendor message' }}</h2>
                                @unless ($notification->read_at)
                                    <span class="rounded-full bg-amber-400 px-2 py-0.5 text-[10px] font-bold text-slate-900">New</span>
                                @endunless
                            </div>
                            <p class="mb-0 mt-1 text-sm text-slate-600">{{ $notification->data['message'] ?? 'A vendor sent a message.' }}</p>
                            <time class="mt-2 block text-xs text-slate-400" datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->format('M j, Y g:i A') }}</time>
                        </div>
                        @if ($requestExists)
                            <a class="shrink-0 rounded-md bg-[#087e69] px-4 py-2 text-sm font-semibold text-white no-underline hover:bg-[#066b59]" href="{{ route('admin.support.show', $notification->data['support_request_id']) }}">Open message</a>
                        @else
                            <span class="shrink-0 text-xs text-slate-400">Support request was deleted</span>
                        @endif
                    </article>
                @empty
                    <p class="m-0 px-5 py-12 text-center text-sm text-slate-500">No vendor messages yet.</p>
                @endforelse
            </div>
            @if ($notifications->hasPages())
                <div class="border-t border-slate-200 px-5 py-4">{{ $notifications->links() }}</div>
            @endif
        </section>
    </section>
</x-layouts.admin>
