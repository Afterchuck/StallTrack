<x-layouts.vendor title="Help & Support" active="support">
    <div class="mx-auto max-w-[1180px] px-5 py-8 md:px-8 md:py-10">
        <section>
            <p class="m-0 text-[10px] font-semibold tracking-[.12em] text-emerald-700">VENDOR PORTAL</p>
            <h1 class="mt-2 text-3xl font-bold tracking-[-.03em] text-slate-900">Help &amp; Support</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">Send a question or report an issue to Market Administration. You can follow the reply and request status below.</p>
        </section>

        @if (session('success'))
            <div class="mt-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('success') }}</div>
        @endif

        <div class="mt-7 grid items-start gap-7 xl:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
            <section class="rounded-md border border-slate-200 bg-white p-5 sm:p-7">
                <h2 class="m-0 text-lg font-bold text-slate-900">Contact Market Administration</h2>
                <p class="mt-1.5 text-sm text-slate-500">Describe what you need help with. Please don’t include passwords or bank details.</p>

                <form class="mt-6 grid gap-5" method="POST" action="{{ route('vendor.support.store') }}">
                    @csrf
                    <label class="grid gap-2 text-sm font-semibold text-slate-700">
                        Topic
                        <select class="rounded-md border border-slate-300 bg-white px-3 py-2.5 font-normal" name="category" required>
                            <option value="">Choose a topic</option>
                            @foreach (['Account', 'Stall', 'Payments', 'Rentals and contracts', 'Other'] as $category)
                                <option value="{{ $category }}" @selected(old('category') === $category)>{{ $category }}</option>
                            @endforeach
                        </select>
                        @error('category') <span class="text-xs font-normal text-rose-700">{{ $message }}</span> @enderror
                    </label>

                    <label class="grid gap-2 text-sm font-semibold text-slate-700">
                        Subject
                        <input class="rounded-md border border-slate-300 px-3 py-2.5 font-normal" name="subject" maxlength="160" value="{{ old('subject') }}" placeholder="Briefly describe your issue" required>
                        @error('subject') <span class="text-xs font-normal text-rose-700">{{ $message }}</span> @enderror
                    </label>

                    <label class="grid gap-2 text-sm font-semibold text-slate-700">
                        Message
                        <textarea class="min-h-36 rounded-md border border-slate-300 px-3 py-2.5 font-normal" name="message" maxlength="5000" minlength="10" placeholder="Include the details the admin will need to help you." required>{{ old('message') }}</textarea>
                        @error('message') <span class="text-xs font-normal text-rose-700">{{ $message }}</span> @enderror
                    </label>

                    <button class="justify-self-start rounded-md bg-[#087e69] px-5 py-3 text-sm font-semibold text-white hover:bg-[#066b59]" type="submit">Send support request</button>
                </form>
            </section>

            <section class="overflow-hidden rounded-md border border-slate-200 bg-white">
                <header class="border-b border-slate-200 px-5 py-5 sm:px-6">
                    <h2 class="m-0 text-lg font-bold text-slate-900">Your requests</h2>
                    <p class="mb-0 mt-1 text-sm text-slate-500">Check the status and read admin replies.</p>
                </header>
                <div class="divide-y divide-slate-100">
                    @forelse ($supportRequests as $supportRequest)
                        <article class="px-5 py-5 sm:px-6">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="m-0 text-[10px] font-semibold uppercase tracking-wide text-slate-400">{{ $supportRequest->category }} · {{ $supportRequest->created_at->format('M j, Y') }}</p>
                                    <h3 class="mb-0 mt-1.5 text-base font-bold text-slate-900">{{ $supportRequest->subject }}</h3>
                                </div>
                                <span @class([
                                    'rounded-full px-3 py-1 text-xs font-semibold',
                                    'bg-amber-50 text-amber-800' => $supportRequest->status === 'Open',
                                    'bg-sky-50 text-sky-800' => $supportRequest->status === 'In Progress',
                                    'bg-emerald-50 text-emerald-800' => $supportRequest->status === 'Resolved',
                                ])>{{ $supportRequest->status }}</span>
                            </div>
                            <p class="mb-0 mt-3 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $supportRequest->message }}</p>
                            @if ($supportRequest->admin_response)
                                <div class="mt-4 rounded-md border border-emerald-100 bg-emerald-50/70 p-4">
                                    <p class="m-0 text-xs font-bold text-emerald-900">Reply from Market Administration</p>
                                    <p class="mb-0 mt-2 whitespace-pre-line text-sm leading-6 text-emerald-900">{{ $supportRequest->admin_response }}</p>
                                    @if ($supportRequest->responded_at)
                                        <time class="mt-2 block text-xs text-emerald-700" datetime="{{ $supportRequest->responded_at->toIso8601String() }}">{{ $supportRequest->responded_at->format('M j, Y g:i A') }}</time>
                                    @endif
                                </div>
                            @else
                                <p class="mb-0 mt-3 text-xs text-slate-400">Waiting for an admin reply.</p>
                            @endif
                        </article>
                    @empty
                        <div class="px-6 py-14 text-center">
                            <h3 class="m-0 text-base font-bold text-slate-900">No support requests yet</h3>
                            <p class="mb-0 mt-2 text-sm text-slate-500">Requests you send will appear here so you can track their progress.</p>
                        </div>
                    @endforelse
                </div>
                @if ($supportRequests->hasPages())
                    <div class="border-t border-slate-200 px-5 py-4 sm:px-6">{{ $supportRequests->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</x-layouts.vendor>
