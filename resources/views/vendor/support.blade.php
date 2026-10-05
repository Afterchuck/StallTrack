<x-layouts.vendor title="Help & Support" active="support">
    <div class="mx-auto max-w-[1180px] px-5 py-8 md:px-8 md:py-10">
        <p class="m-0 text-[10px] font-semibold uppercase tracking-[.12em] text-emerald-700">Vendor portal</p>
        <h1 class="mt-2 text-[28px] font-bold tracking-[-.03em] text-slate-900">Help &amp; Support</h1>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">Send a question or report an issue to Market Administration. You can follow the reply and request status below.</p>

        @if (session('success'))
            <div class="mt-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('success') }}</div>
        @endif

        <div class="mt-7 grid items-start gap-6 lg:grid-cols-2">
            <section class="rounded-md border border-slate-200 bg-white p-6">
                <h2 class="text-lg font-bold text-slate-900">Contact Market Administration</h2>
                <p class="mt-1 text-sm leading-5 text-slate-500">Describe what you need help with. Please do not include passwords or bank details.</p>

                <form class="mt-6 grid gap-4" method="POST" action="{{ route('vendor.support.store') }}">
                    @csrf
                    <label class="grid gap-2 text-sm font-semibold text-slate-700" for="topic">
                        Topic
                        <select class="min-h-10 rounded-md border border-slate-300 bg-white px-3 text-sm font-normal text-slate-800 focus:border-emerald-700 focus:outline-none focus:ring-1 focus:ring-emerald-700" id="topic" name="topic" required>
                            <option value="">Choose a topic</option>
                            @foreach (['Billing & payments', 'Stall & lease', 'Account access', 'Technical issue', 'Other'] as $topic)
                                <option value="{{ $topic }}" @selected(old('topic') === $topic)>{{ $topic }}</option>
                            @endforeach
                        </select>
                        @error('topic')<span class="text-xs font-normal text-rose-600">{{ $message }}</span>@enderror
                    </label>

                    <label class="grid gap-2 text-sm font-semibold text-slate-700" for="subject">
                        Subject
                        <input class="min-h-10 rounded-md border border-slate-300 px-3 text-sm font-normal text-slate-800 placeholder:text-slate-400 focus:border-emerald-700 focus:outline-none focus:ring-1 focus:ring-emerald-700" id="subject" name="subject" placeholder="Briefly describe your issue" value="{{ old('subject') }}" maxlength="150" required>
                        @error('subject')<span class="text-xs font-normal text-rose-600">{{ $message }}</span>@enderror
                    </label>

                    <label class="grid gap-2 text-sm font-semibold text-slate-700" for="message">
                        Message
                        <textarea class="min-h-32 rounded-md border border-slate-300 px-3 py-2 text-sm font-normal text-slate-800 placeholder:text-slate-400 focus:border-emerald-700 focus:outline-none focus:ring-1 focus:ring-emerald-700" id="message" name="message" placeholder="Include the details the admin will need to help you." maxlength="5000" required>{{ old('message') }}</textarea>
                        @error('message')<span class="text-xs font-normal text-rose-600">{{ $message }}</span>@enderror
                    </label>

                    <button class="w-fit rounded-md bg-[#087e69] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#066b59]" type="submit">Send support request</button>
                </form>
            </section>

            <section class="overflow-hidden rounded-md border border-slate-200 bg-white">
                <div class="border-b border-slate-200 px-6 py-5">
                    <h2 class="text-lg font-bold text-slate-900">Your requests</h2>
                    <p class="mt-1 text-sm text-slate-500">Check the status and read admin replies.</p>
                </div>

                <div class="p-6">
                    @forelse ($supportRequests as $supportRequest)
                        <article class="border-b border-slate-200 py-4 first:pt-0 last:border-b-0 last:pb-0">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">{{ $supportRequest->topic }}</p>
                                    <h3 class="mt-1 font-semibold text-slate-900">{{ $supportRequest->subject }}</h3>
                                </div>
                                <span @class([
                                    'rounded px-2 py-1 text-xs font-semibold',
                                    'bg-emerald-100 text-emerald-700' => $supportRequest->status === 'Resolved',
                                    'bg-amber-100 text-amber-700' => $supportRequest->status !== 'Resolved',
                                ])>{{ $supportRequest->status }}</span>
                            </div>
                            <p class="mt-2 whitespace-pre-wrap break-words text-sm text-slate-600">{{ $supportRequest->message }}</p>
                            @if ($supportRequest->admin_reply)
                                <div class="mt-3 rounded-md bg-slate-50 p-3 text-sm text-slate-700">
                                    <strong class="block text-slate-900">Market Administration</strong>
                                    <p class="mt-1 whitespace-pre-wrap break-words">{{ $supportRequest->admin_reply }}</p>
                                </div>
                            @endif
                            <p class="mt-3 text-xs text-slate-500">Sent {{ $supportRequest->created_at->format('M d, Y') }}</p>
                        </article>
                    @empty
                        <div class="py-7 text-center">
                            <h3 class="font-semibold text-slate-900">No support requests yet</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-500">Requests you send will appear here so you can track their progress.</p>
                        </div>
                    @endforelse
                </div>

                @if ($supportRequests instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    <div class="border-t border-slate-200 px-6 py-4">{{ $supportRequests->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</x-layouts.vendor>
