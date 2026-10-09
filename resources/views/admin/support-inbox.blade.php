<x-layouts.admin title="Notifications" active="notifications">
    <div class="grid gap-6">
        <h1 class="text-2xl font-bold">Notifications &amp; support</h1>
        @if (session('success'))
            <p role="status" class="rounded-md bg-emerald-50 p-4 text-emerald-800">{{ session('success') }}</p>
        @endif
        <section class="rounded-md border border-slate-200 bg-white p-5">
            <h2 class="text-lg font-bold">Your notifications</h2>
            <div class="notification-list" tabindex="0" aria-label="Support notifications">
            @forelse ($notifications as $notification)
                <article class="border-b border-slate-200 py-4">
                    <div class="flex flex-wrap justify-between gap-2">
                        <h3 class="font-semibold">{{ $notification->data['title'] }}</h3>
                        <span class="text-xs text-emerald-700">{{ $notification->read_at ? 'Read' : 'Unread' }}</span>
                    </div>
                    <p class="mt-2 break-words text-sm">{{ $notification->data['vendor_name'] }}: {{ $notification->data['subject'] }}</p>
                    <form method="POST" action="{{ route('admin.notifications.read', $notification->id) }}" class="mt-3">
                        @csrf
                        <button type="submit" class="rounded-md bg-[#087e69] px-4 py-2 text-sm font-semibold text-white">Open request{{ $notification->read_at ? '' : ' & mark as read' }}</button>
                    </form>
                </article>
            @empty
                <p class="py-5 text-sm text-slate-500">No support notifications yet.</p>
            @endforelse
            </div>
            {{ $notifications->withQueryString()->links() }}
        </section>
        <section class="rounded-md border border-slate-200 bg-white p-5">
            <h2 class="text-lg font-bold">All support requests</h2>
            <p class="mt-1 text-sm text-slate-500">Includes requests submitted before notifications were enabled.</p>
            @forelse ($supportRequests as $supportRequest)
                <a href="{{ route('admin.support.show', $supportRequest) }}" class="flex flex-wrap justify-between gap-3 border-b border-slate-200 py-4 text-sm hover:text-emerald-700">
                    <span class="min-w-0 break-words"><strong>{{ $supportRequest->subject }}</strong><span class="mt-1 block text-slate-500">{{ $supportRequest->vendor->name }} · {{ $supportRequest->created_at->format('M d, Y') }}</span></span>
                    <span>{{ $supportRequest->status }}</span>
                </a>
            @empty
                <p class="py-5 text-sm text-slate-500">No support requests yet.</p>
            @endforelse
            {{ $supportRequests->withQueryString()->links() }}
        </section>
    </div>
</x-layouts.admin>
