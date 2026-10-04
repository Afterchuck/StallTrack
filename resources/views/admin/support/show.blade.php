<x-layouts.admin title="Review Support Request" active="support">
    <div class="mx-auto grid max-w-[1100px] gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
        <section class="rounded-md border border-slate-200 bg-white p-6 sm:p-8">
            <a class="text-sm font-semibold text-emerald-800 no-underline hover:underline" href="{{ route('admin.support.index') }}">← Back to support inbox</a>
            <div class="mt-6 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="m-0 text-[10px] font-semibold uppercase tracking-wide text-slate-400">{{ $supportRequest->category }} · {{ $supportRequest->created_at->format('M j, Y g:i A') }}</p>
                    <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">{{ $supportRequest->subject }}</h1>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $supportRequest->status }}</span>
            </div>
            <div class="mt-6 rounded-md bg-slate-50 p-5">
                <p class="m-0 text-xs font-semibold text-slate-500">Message from {{ $supportRequest->user->name }}</p>
                <p class="mb-0 mt-3 whitespace-pre-line text-sm leading-6 text-slate-800">{{ $supportRequest->message }}</p>
            </div>
            @if ($supportRequest->admin_response)
                <div class="mt-5 rounded-md border border-emerald-100 bg-emerald-50 p-5">
                    <p class="m-0 text-xs font-semibold text-emerald-900">Previous reply{{ $supportRequest->responder ? ' from '.$supportRequest->responder->name : '' }}</p>
                    <p class="mb-0 mt-3 whitespace-pre-line text-sm leading-6 text-emerald-950">{{ $supportRequest->admin_response }}</p>
                    @if ($supportRequest->responded_at)
                        <time class="mt-2 block text-xs text-emerald-700" datetime="{{ $supportRequest->responded_at->toIso8601String() }}">{{ $supportRequest->responded_at->format('M j, Y g:i A') }}</time>
                    @endif
                </div>
            @endif
        </section>

        <aside class="h-fit rounded-md border border-slate-200 bg-white p-6">
            <h2 class="m-0 text-lg font-bold text-slate-900">Message the vendor</h2>
            <p class="mb-0 mt-1 text-sm text-slate-500">Your reply will appear in the vendor’s Help &amp; Support page and send them a notification.</p>
            @if (session('success'))
                <div class="mt-4 rounded border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800" role="status">{{ session('success') }}</div>
            @endif
            <dl class="my-5 grid gap-3 border-b border-slate-100 pb-5 text-sm"><div><dt class="text-slate-500">Vendor</dt><dd class="m-0 mt-1 font-semibold text-slate-800">{{ $supportRequest->user->name }}</dd></div><div><dt class="text-slate-500">Email</dt><dd class="m-0 mt-1 font-semibold text-slate-800">{{ $supportRequest->user->email }}</dd></div></dl>
            <form class="grid gap-4" method="POST" action="{{ route('admin.support.update', $supportRequest) }}">
                @csrf
                @method('PATCH')
                <label class="grid gap-2 text-sm font-semibold text-slate-700">Request status
                    <select class="rounded-md border border-slate-300 bg-white px-3 py-2.5 font-normal" name="status" required>
                        @foreach (['Open', 'In Progress', 'Resolved'] as $status)
                            <option value="{{ $status }}" @selected(old('status', $supportRequest->status) === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                    @error('status') <span class="text-xs font-normal text-rose-700">{{ $message }}</span> @enderror
                </label>
                <label class="grid gap-2 text-sm font-semibold text-slate-700">Reply to vendor
                    <textarea class="min-h-40 rounded-md border border-slate-300 px-3 py-2.5 font-normal" name="admin_response" maxlength="5000" placeholder="Write a reply or update for the vendor.">{{ old('admin_response', $supportRequest->admin_response) }}</textarea>
                    @error('admin_response') <span class="text-xs font-normal text-rose-700">{{ $message }}</span> @enderror
                </label>
                <button class="rounded-md bg-[#087e69] px-4 py-3 text-sm font-semibold text-white hover:bg-[#066b59]" type="submit">Send reply &amp; notify vendor</button>
            </form>
            <form class="mt-4" method="POST" action="{{ route('admin.support.destroy', $supportRequest) }}" data-confirm="Delete this support request permanently?">
                @csrf @method('DELETE')
                <button class="w-full rounded-md border border-rose-200 bg-white px-4 py-3 text-sm font-semibold text-rose-700 hover:bg-rose-50" type="submit">Delete request</button>
            </form>
        </aside>
    </div>
</x-layouts.admin>
