<div>
    <!-- Simplicity is an acquired taste. - Katharine Gerould -->
</div>
<x-layouts.auth title="Forgot password">
    <main class="w-full max-w-[460px] rounded-2xl border border-[#dce8e2] bg-white px-6 py-9 shadow-[0_24px_80px_rgba(13,49,36,.12)] sm:px-10">
        <a href="{{ route('login') }}" class="text-sm font-semibold text-[#087b59] no-underline hover:underline">&larr; Back to sign in</a>
        <p class="mt-8 text-xs font-bold uppercase tracking-[.16em] text-[#087b59]">Account recovery</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-[-.04em] text-[#172b24]">Forgot your password?</h1>
        <p class="mt-2 text-sm leading-6 text-[#718078]">Enter the email address for your StallTrack account. We’ll send a secure link to reset the password.</p>

        @if (session('status'))
            <div class="mt-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mt-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="mt-7 grid gap-5">
            @csrf
            <label class="grid gap-2 text-sm font-semibold text-[#34463d]" for="email">Email address
                <input class="h-12 w-full rounded-lg border border-[#d7e2dc] bg-[#fbfdfc] px-4 text-sm font-normal text-[#23352c] placeholder:text-[#a1ada6] outline-none transition focus:border-[#087b59] focus:bg-white focus:ring-4 focus:ring-[#087b59]/10" id="email" name="email" type="email" value="{{ old('email') }}" placeholder="you@example.com" autocomplete="email" required autofocus>
            </label>
            <button class="flex h-12 w-full cursor-pointer items-center justify-center rounded-lg border-0 bg-[#087b59] text-sm font-semibold text-white shadow-sm transition hover:bg-[#066749] focus:outline-none focus:ring-4 focus:ring-[#087b59]/20" type="submit">Send password reset link</button>
        </form>
    </main>
</x-layouts.auth>
