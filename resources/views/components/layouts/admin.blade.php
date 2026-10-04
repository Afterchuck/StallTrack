<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'StallTrack' }} · StallTrack</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-[#f7f9fc] font-[Arial,Helvetica,sans-serif] text-[#192235]">
    @php($unreadVendorMessagesCount = auth()->user()->unreadNotifications()->where('type', \App\Notifications\VendorMessageNotification::class)->count())
    <header class="sticky top-0 z-40 flex min-h-16 flex-wrap items-center gap-7 bg-[#0d1a31] px-4 text-white shadow-md md:flex-nowrap md:px-8">
        <a class="flex items-center gap-2.5 text-lg font-bold text-white no-underline" href="{{ route('dashboard') }}">
            <span class="grid size-5 place-items-center rounded bg-[#08b98a] text-[11px]">▥</span>
            <strong>Stall<span class="text-[#08d29c]">Track</span></strong>
        </a>

        <nav class="order-3 flex w-full items-center gap-2 overflow-x-auto md:order-none md:w-auto" aria-label="Main navigation">
            @foreach (['dashboard' => ['Dashboard', route('dashboard')], 'vendors' => ['Vendors', route('vendors.index')], 'stalls' => ['Stalls', route('stalls')], 'rentals' => ['Rentals', route('rentals')], 'payments' => ['Collections', route('payments')], 'reports' => ['Reports', route('reports')], 'notifications' => ['Notifications', route('admin.notifications')]] as $key => [$label, $url])
                <a @class(['rounded-md px-4 py-2.5 text-sm font-medium text-slate-300 no-underline transition hover:bg-white/10 hover:text-white', 'bg-[#087e69] text-white' => ($active ?? 'dashboard') === $key]) href="{{ $url }}">
                    {{ $label }}
                    @if ($key === 'notifications' && $unreadVendorMessagesCount > 0)
                        <span class="ml-1 rounded-full bg-amber-400 px-2 py-0.5 text-[10px] font-bold text-slate-900" aria-label="{{ $unreadVendorMessagesCount }} unread vendor messages">{{ $unreadVendorMessagesCount }} new</span>
                    @endif
                </a>
            @endforeach
        </nav>

        <details class="relative ml-auto">
            <summary class="flex cursor-pointer list-none items-center gap-2">
                <div class="text-right">
                    <strong class="block text-xs">{{ auth()->user()->name ?? 'Eleanor Vance' }}</strong>
                    <small class="block text-[10px] text-[#08d29c]">Market Admin</small>
                </div>
                <span class="grid size-8 place-items-center rounded-full bg-[#087e69] text-xs font-bold">
                    {{ strtoupper(substr(auth()->user()->name ?? 'E', 0, 1)) }}
                </span>
            </summary>

            <div class="absolute top-[calc(100%+8px)] right-0 z-30 w-36 rounded border border-slate-200 bg-white p-1 shadow-lg">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="w-full cursor-pointer rounded border-0 bg-transparent px-3 py-2 text-left text-xs text-slate-700 hover:bg-emerald-50" type="submit">
                        ↪ Log out
                    </button>
                </form>
            </div>
        </details>
    </header>

    <main class="mx-auto w-full max-w-[1280px] flex-1 px-4 py-6 md:px-8 md:py-8">
        {{ $slot }}
    </main>

    <footer class="flex flex-col gap-3 border-t border-slate-200 bg-white px-4 py-4 text-[10px] text-slate-400 sm:flex-row sm:items-center sm:justify-between md:px-8">
        <span>
            © 2026 StallTrack Systems. All rights reserved.
        </span>
        <span class="flex flex-wrap gap-x-4 gap-y-2">
            <a class="text-slate-400 no-underline" href="#">
                Privacy Policy
            </a>
            <a class="text-slate-400 no-underline" href="#">
                Terms of Service
            </a>
            <a class="text-slate-400 no-underline" href="#">
                System Support
            </a>
        </span>
    </footer>

    <div id="deleteConfirmModal" class="app-modal-overlay hidden" role="dialog" aria-modal="true" aria-labelledby="deleteConfirmTitle" aria-describedby="deleteConfirmMessage">
        <div class="app-modal-panel max-w-md">
            <div class="app-modal-header items-center">
                <div class="flex items-center gap-3">
                    <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-rose-100 text-rose-700">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path d="M12 9v4m0 4h.01M10.3 3.9l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.7-3.1l-8-14a2 2 0 0 0-3.4 0Z" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                    <div>
                        <h2 id="deleteConfirmTitle" class="m-0 text-base font-bold text-slate-900">Confirm deletion</h2>
                        <p class="mt-1 mb-0 text-xs text-slate-500">This action may not be reversible.</p>
                    </div>
                </div>
                <button id="deleteConfirmClose" type="button" class="app-modal-close" aria-label="Close dialog">&times;</button>
            </div>
            <div class="app-modal-body">
                <p id="deleteConfirmMessage" class="m-0 leading-relaxed text-slate-600">Are you sure you want to delete this item?</p>
            </div>
            <div class="app-modal-footer justify-end gap-3">
                <button id="deleteConfirmCancel" type="button" class="app-btn-cancel">Cancel</button>
                <button id="deleteConfirmSubmit" type="button" class="inline-flex items-center gap-2 rounded-lg bg-rose-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-800 focus:outline-none focus:ring-4 focus:ring-rose-700/20">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M3 6h18m-2 0-.9 14H5.9L5 6m4 0V4h6v2m-5 4v6m4-6v6" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>
                    Delete
                </button>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const modal = document.getElementById('deleteConfirmModal');
            const message = document.getElementById('deleteConfirmMessage');
            const confirmButton = document.getElementById('deleteConfirmSubmit');
            const cancelButton = document.getElementById('deleteConfirmCancel');
            const closeButton = document.getElementById('deleteConfirmClose');
            let pendingForm = null;

            function closeDeleteConfirmation() {
                modal.classList.add('hidden');
                pendingForm = null;
            }

            document.addEventListener('submit', (event) => {
                const form = event.target.closest('form[data-confirm]');
                if (!form || form.dataset.confirmBypass === 'true') {
                    return;
                }

                event.preventDefault();
                pendingForm = form;
                message.textContent = form.dataset.confirm;
                modal.classList.remove('hidden');
                cancelButton.focus();
            });

            confirmButton.addEventListener('click', () => {
                if (pendingForm) {
                    const form = pendingForm;
                    closeDeleteConfirmation();
                    form.dataset.confirmBypass = 'true';
                    form.submit();
                }
            });

            cancelButton.addEventListener('click', closeDeleteConfirmation);
            closeButton.addEventListener('click', closeDeleteConfirmation);
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeDeleteConfirmation();
                }
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeDeleteConfirmation();
                }
            });
        })();
    </script>
</body>
</html>
