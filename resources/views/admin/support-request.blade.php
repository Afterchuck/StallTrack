<x-layouts.admin title="Support request" active="notifications">
    <div class="mx-auto grid max-w-3xl gap-5">
        <a href="{{ route('admin.notifications') }}" class="text-sm font-semibold text-emerald-700">Back to notifications</a>
        @if (session('success'))
            <p role="status" class="rounded-md bg-emerald-50 p-4 text-emerald-800">{{ session('success') }}</p>
        @endif
        <section class="grid gap-3 rounded-md border border-slate-200 bg-white p-6">
            <p class="text-sm text-emerald-700">{{ $supportRequest->topic }} · {{ $supportRequest->status }}</p>
            <h1 class="break-words text-2xl font-bold">{{ $supportRequest->subject }}</h1>
            <p class="text-sm text-slate-500">{{ $supportRequest->vendor->name }} · Stall {{ $supportRequest->vendor->stall_number ?: 'unassigned' }} · {{ $supportRequest->created_at->format('M d, Y') }}</p>
            <p class="whitespace-pre-wrap break-words text-sm leading-6">{{ $supportRequest->message }}</p>
        </section>
        <form method="POST" action="{{ route('admin.support.update', $supportRequest) }}" class="grid gap-4 rounded-md border border-slate-200 bg-white p-6">
            @csrf
            @method('PATCH')
            <h2 class="text-lg font-bold">Reply to vendor</h2>
            <p class="text-sm text-slate-500">Your saved reply and status appear in the vendor's Help &amp; Support page. Saving again replaces the current reply.</p>
            <label for="admin_reply" class="font-semibold">Reply</label>
            <textarea id="admin_reply" name="admin_reply" rows="6" maxlength="5000" required class="rounded-md border border-slate-300 p-3">{{ old('admin_reply', $supportRequest->admin_reply) }}</textarea>
            @error('admin_reply')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
            <label for="status" class="font-semibold">Status</label>
            <select id="status" name="status" required class="rounded-md border border-slate-300 p-3">
                @foreach (['Open', 'In progress', 'Resolved'] as $status)
                    <option value="{{ $status }}" @selected(old('status', $supportRequest->status) === $status)>{{ $status }}</option>
                @endforeach
            </select>
            @error('status')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
            <button type="submit" class="w-fit rounded-md bg-[#087e69] px-5 py-3 font-semibold text-white">Save reply &amp; status</button>
        </form>
    </div>
</x-layouts.admin>
