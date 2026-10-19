<div>
    <!-- Let all your things have their places; let each part of your business have its time. - Benjamin Franklin -->
</div>
<x-layouts.auth title="Reset password">
    <main class="w-full max-w-[460px] rounded-2xl border border-[#dce8e2] bg-white px-6 py-9 shadow-[0_24px_80px_rgba(13,49,36,.12)] sm:px-10">
        <a href="{{ route('login') }}" class="text-sm font-semibold text-[#087b59] no-underline hover:underline">&larr; Back to sign in</a>
        <p class="mt-8 text-xs font-bold uppercase tracking-[.16em] text-[#087b59]">Account recovery</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-[-.04em] text-[#172b24]">Choose a new password</h1>
        <p class="mt-2 text-sm leading-6 text-[#718078]">Set a new password for the account that received this reset link.</p>

        @if ($errors->any())
            <div class="mt-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="mt-7 grid gap-5">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <label class="grid gap-2 text-sm font-semibold text-[#34463d]" for="email">Email address
                <input class="h-12 w-full rounded-lg border border-[#d7e2dc] bg-[#fbfdfc] px-4 text-sm font-normal text-[#23352c] outline-none transition focus:border-[#087b59] focus:bg-white focus:ring-4 focus:ring-[#087b59]/10" id="email" name="email" type="email" value="{{ old('email', $email) }}" autocomplete="email" required>
            </label>
            <label class="grid gap-2 text-sm font-semibold text-[#34463d]" for="password">New password
                <input class="h-12 w-full rounded-lg border border-[#d7e2dc] bg-[#fbfdfc] px-4 text-sm font-normal text-[#23352c] outline-none transition focus:border-[#087b59] focus:bg-white focus:ring-4 focus:ring-[#087b59]/10" id="password" name="password" type="password" autocomplete="new-password" minlength="12" maxlength="72" required>
            </label>
            <label class="grid gap-2 text-sm font-semibold text-[#34463d]" for="password_confirmation">Confirm new password
                <input class="h-12 w-full rounded-lg border border-[#d7e2dc] bg-[#fbfdfc] px-4 text-sm font-normal text-[#23352c] outline-none transition focus:border-[#087b59] focus:bg-white focus:ring-4 focus:ring-[#087b59]/10" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="12" maxlength="72" required>
            </label>
            <p class="-mt-2 text-xs text-[#718078]">Use at least 12 characters.</p>
            <button class="flex h-12 w-full cursor-pointer items-center justify-center rounded-lg border-0 bg-[#087b59] text-sm font-semibold text-white shadow-sm transition hover:bg-[#066749] focus:outline-none focus:ring-4 focus:ring-[#087b59]/20" type="submit">Reset password</button>
        </form>
    </main>
</x-layouts.auth>
