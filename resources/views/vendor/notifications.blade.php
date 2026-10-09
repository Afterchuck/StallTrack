<x-layouts.vendor title="Notifications" active="notifications">
    <section class="mx-auto flex max-w-4xl flex-col gap-4 p-5 md:p-8">
        <h1 class="text-2xl font-bold">Your notifications</h1>
        <p class="text-sm text-slate-500">Rent bills and reminders from market administration. Amounts below reflect the time sent; open the bill for your current balance.</p>
        <div class="notification-list" tabindex="0" aria-label="Billing notifications">
        @forelse ($notifications as $notification)
            <article class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="font-bold">{{ $notification->data['title'] }}</h2>
                    <span class="text-xs {{ $notification->read_at ? 'text-slate-500' : 'font-semibold text-emerald-700' }}">{{ $notification->read_at ? 'Read' : 'Unread' }} · {{ $notification->created_at->format('M d, Y H:i') }} UTC</span>
                </div>
                <p class="mt-2">Stall {{ $notification->data['stall_number'] }} · {{ $notification->data['period'] }}</p>
                <p class="mt-2 font-semibold">Amount to pay when sent: ₱{{ number_format((float) $notification->data['balance'], 2) }} · Due {{ $notification->data['due_date'] }}</p>
                <form method="POST" action="{{ route('vendor.notifications.read', $notification->id) }}" class="mt-4">
                    @csrf
                    <button type="submit" class="rounded-md bg-[#087e69] px-4 py-2 text-sm font-semibold text-white">Open bill{{ $notification->read_at ? '' : ' & mark as read' }}</button>
                </form>
            </article>
        @empty
            <p class="rounded-xl border border-slate-200 bg-white p-5">No billing notifications yet.</p>
        @endforelse
        </div>
        {{ $notifications->links() }}
    </section>
</x-layouts.vendor>
