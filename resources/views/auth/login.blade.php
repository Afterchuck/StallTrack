<x-layouts.auth title="Sign in">
    <main class="grid min-h-[min(720px,calc(100vh-48px))] w-full max-w-[1120px] grid-cols-1 overflow-hidden rounded-2xl border border-[#dce8e2] bg-white shadow-[0_24px_80px_rgba(13,49,36,.12)] md:grid-cols-2">
        <section class="relative hidden overflow-hidden bg-[#073b2d] px-10 py-11 text-white md:flex md:flex-col md:justify-between lg:px-14 lg:py-14">
            <div class="relative z-10 flex items-center gap-3">
                <span class="grid size-11 place-items-center rounded-xl bg-[#0b8a62] shadow-lg shadow-black/10" aria-hidden="true">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none">
                        <path d="M3 10.5 12 4l9 6.5v9a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 19.5v-9Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
                        <path d="M7 21v-7h10v7M7 10h.01M12 10h.01M17 10h.01" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
                    </svg>
                </span>
                <span>
                    <strong class="block text-lg leading-tight tracking-tight">StallTrack</strong>
                    <span class="mt-0.5 block text-xs text-emerald-100/75">Public Market Portal</span>
                </span>
            </div>

            <div class="relative z-10 my-12 max-w-md">
                <p class="mb-5 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[.16em] text-emerald-100">
                    <span class="size-1.5 rounded-full bg-[#85e0ad]"></span>
                    Market operations
                </p>
                <h2 class="text-4xl font-semibold leading-[1.12] tracking-[-.04em] lg:text-5xl">
                    Your market,<br>
                    <span class="text-[#91dfb5]">in good order.</span>
                </h2>
                <p class="mt-5 max-w-sm text-sm leading-6 text-emerald-50/75">
                    Manage stalls, vendor accounts, and rental payments from one secure workspace.
                </p>
            </div>

            <div class="relative z-10 flex items-center justify-between border-t border-white/15 pt-5 text-xs text-emerald-50/65">
                <span>Market administration</span>
                <span>Secure staff and vendor access</span>
            </div>

            <div class="pointer-events-none absolute -right-28 -bottom-36 size-[430px] rounded-full border border-white/10"></div>
            <div class="pointer-events-none absolute -right-12 -bottom-20 size-[300px] rounded-full border border-white/10"></div>
            <div class="pointer-events-none absolute right-10 -bottom-5 size-[170px] rounded-full bg-[#0b8a62]/25 blur-3xl"></div>
            <div class="pointer-events-none absolute top-28 -right-12 h-44 w-44 rounded-full bg-emerald-300/10 blur-3xl"></div>
        </section>

        <section class="flex items-center justify-center px-6 py-10 sm:px-10 md:px-12 lg:px-16">
            <div class="w-full max-w-[390px]">
                <div class="mb-10 flex items-center gap-3 md:hidden">
                    <span class="grid size-10 place-items-center rounded-xl bg-[#087b59] text-white" aria-hidden="true">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none">
                            <path d="M3 10.5 12 4l9 6.5v9a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 19.5v-9Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
                            <path d="M7 21v-7h10v7M7 10h.01M12 10h.01M17 10h.01" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
                        </svg>
                    </span>
                    <span>
                        <strong class="block text-base leading-tight text-[#172b24]">StallTrack</strong>
                        <span class="mt-0.5 block text-xs text-[#728078]">Public Market Portal</span>
                    </span>
                </div>

                <p class="text-xs font-bold uppercase tracking-[.16em] text-[#087b59]">Welcome back</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-[-.04em] text-[#172b24] sm:text-[34px]">Sign in to your account</h1>
                <p class="mt-2 text-sm leading-6 text-[#718078]">Enter your market account details to continue.</p>

                @if (session('status'))
                    <div class="mt-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mt-6 flex gap-3 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
                        <svg class="mt-0.5 size-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm0-11a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 7Zm0 7a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                        </svg>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.store') }}" class="mt-8 grid gap-5">
                    @csrf
                    <div class="grid gap-2">
                        <label class="text-sm font-semibold text-[#34463d]" for="email">Email address</label>
                        <div class="relative">
                            <svg class="pointer-events-none absolute top-1/2 left-3.5 size-[18px] -translate-y-1/2 text-[#819087]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.7" />
                                <path d="m4 7 8 6 8-6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <input class="h-12 w-full rounded-lg border border-[#d7e2dc] bg-[#fbfdfc] py-0 pr-4 pl-11 text-sm text-[#23352c] placeholder:text-[#a1ada6] outline-none transition focus:border-[#087b59] focus:bg-white focus:ring-4 focus:ring-[#087b59]/10" id="email" name="email" type="email" value="{{ old('email') }}" placeholder="you@example.com" autocomplete="email" required autofocus>
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <div class="flex items-center justify-between gap-3">
                            <label class="text-sm font-semibold text-[#34463d]" for="password">Password</label>
                        </div>
                        <div class="relative">
                            <svg class="pointer-events-none absolute top-1/2 left-3.5 size-[18px] -translate-y-1/2 text-[#819087]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <rect x="4" y="10" width="16" height="11" rx="2" stroke="currentColor" stroke-width="1.7" />
                                <path d="M8 10V7a4 4 0 1 1 8 0v3m-4 5v2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
                            </svg>
                            <input class="h-12 w-full rounded-lg border border-[#d7e2dc] bg-[#fbfdfc] py-0 pr-16 pl-11 text-sm text-[#23352c] placeholder:text-[#a1ada6] outline-none transition focus:border-[#087b59] focus:bg-white focus:ring-4 focus:ring-[#087b59]/10" id="password" name="password" type="password" placeholder="Enter your password" autocomplete="current-password" required>
                            <button type="button" class="password-toggle absolute inset-y-0 right-3 my-auto rounded px-1 text-xs font-semibold text-[#087b59] hover:text-[#055a41] focus:outline-none focus:ring-2 focus:ring-[#087b59]/30" data-target="password" aria-label="Show password" aria-pressed="false">Show</button>
                        </div>
                    </div>

                    <button class="mt-1 flex h-12 w-full cursor-pointer items-center justify-center gap-2 rounded-lg border-0 bg-[#087b59] text-sm font-semibold text-white shadow-sm transition hover:bg-[#066749] focus:outline-none focus:ring-4 focus:ring-[#087b59]/20" type="submit">
                        Sign in
                        <svg class="size-4" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M4 10h12m-5-5 5 5-5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                </form>

                <p class="mt-7 text-center text-sm text-[#718078]">
                    New to StallTrack?
                    @if (config('security.registration_enabled'))
                        <a href="{{ route('register') }}" class="ml-1 font-semibold text-[#087b59] underline decoration-[#087b59]/30 underline-offset-4 hover:decoration-[#087b59]">Create an account</a>
                    @else
                        <span>Contact market administration for account access.</span>
                    @endif
                </p>

                <div class="mt-9 flex items-center justify-center gap-2 border-t border-[#edf1ee] pt-5 text-xs text-[#839087]">
                    <svg class="size-3.5 text-[#087b59]" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 1.75 3.5 4v4.86c0 4.16 2.68 7.98 6.5 9.39 3.82-1.41 6.5-5.23 6.5-9.39V4L10 1.75Zm2.78 6.97a.75.75 0 0 0-1.06-1.06L9.25 10.13l-.97-.97a.75.75 0 1 0-1.06 1.06l1.5 1.5a.75.75 0 0 0 1.06 0l3-3Z" clip-rule="evenodd" />
                    </svg>
                    Secure access for market staff and vendors
                </div>
            </div>
        </section>
    </main>
</x-layouts.auth>
