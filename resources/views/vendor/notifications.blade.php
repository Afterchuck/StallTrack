<x-layouts.vendor title="Notifications" active="notifications">
    <div class="mx-auto max-w-[1180px] px-5 py-8 md:px-8 md:py-10">
        <section class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="m-0 text-[10px] font-semibold tracking-[.12em] text-emerald-700">VENDOR PORTAL</p>
                <h1 class="mt-2 text-3xl font-bold tracking-[-.03em] text-slate-900">Notifications</h1>
                <p class="mt-2 text-sm text-slate-500">Updates from Market Administration about your stall, rental, and payments.</p>
            </div>
            @if (auth()->user()->unreadNotifications()->exists())
                <form method="POST" action="{{ route('vendor.notifications.read-all') }}">
                    @csrf
                    <button class="rounded-md border border-slate-300 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50" type="submit">Mark all as read</button>
                </form>
            @endif
        </section>

        @if (session('success'))
            <div class="mt-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('success') }}</div>
        @endif

        <section class="mt-7 overflow-hidden rounded-md border border-slate-200 bg-white" aria-label="Your notifications">
            @forelse ($notifications as $notification)
                @php($details = $notification->data)
                <article @class(['flex flex-wrap items-start gap-4 border-b border-slate-100 px-5 py-5 last:border-b-0 sm:px-6', 'bg-emerald-50/50' => is_null($notification->read_at)])>
                    <span @class(['mt-1.5 size-2 shrink-0 rounded-full', 'bg-emerald-600' => is_null($notification->read_at), 'bg-transparent' => ! is_null($notification->read_at)]) aria-hidden="true"></span>
                    <div class="min-w-0 flex-1">
                        <h2 class="m-0 text-sm font-bold text-slate-900">{{ $details['title'] ?? 'Account update' }}</h2>
                        <p class="mb-0 mt-1.5 text-sm leading-6 text-slate-600">{{ $details['message'] ?? 'Market Administration updated your account.' }}</p>
                        <time class="mt-2 block text-xs text-slate-400" datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->format('M j, Y g:i A') }}</time>
                    </div>
                    @if (is_null($notification->read_at))
                        <form method="POST" action="{{ route('vendor.notifications.read', $notification->id) }}">
                            @csrf
                            <button class="rounded border border-emerald-200 bg-white px-3 py-2 text-xs font-semibold text-emerald-800 hover:bg-emerald-50" type="submit">Mark as read</button>
                        </form>
                    @else
                        <span class="rounded bg-slate-100 px-2.5 py-1.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500">Read</span>
                    @endif
                </article>
            @empty
                <div class="px-6 py-16 text-center">
                    <span class="mx-auto grid size-12 place-items-center rounded-full bg-emerald-50 text-emerald-700" aria-hidden="true">
                        <svg class="size-6 fill-none stroke-current stroke-[1.7]" viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" /></svg>
                    </span>
                    <h2 class="mt-4 text-base font-bold text-slate-900">You’re all caught up</h2>
                    <p class="mt-1 text-sm text-slate-500">Admin updates about your account will show up here.</p>
                </div>
            @endforelse
        </section>

        @if ($notifications->hasPages())
            <div class="mt-5">{{ $notifications->links() }}</div>
        @endif
    </div>
</x-layouts.vendor>
