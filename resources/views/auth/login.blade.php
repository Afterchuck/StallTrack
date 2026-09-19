<x-layouts.auth title="Sign in">
    <main class="w-full max-w-[348px] rounded-md border border-[#ded6d6] bg-white px-8 pt-8 pb-7 shadow-[0_2px_8px_rgba(0,0,0,.025)] sm:px-8">
        <div class="mx-auto mb-5 grid size-9 place-items-center rounded-full border border-[#e1dddd] bg-[#fafafa] text-base leading-none text-black" aria-hidden="true">
            ▦
        </div>
        <h1 class="m-0 text-center text-xl font-bold tracking-[-.04em] text-[#222]">
            Public Market
        </h1>
        <p class="mt-1 text-center text-[13px] text-[#625c5c]">
            Management Portal
        </p>

        @if ($errors->any())
            <div class="mt-5 rounded border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="mt-7 grid gap-5">
            @csrf
            <div class="grid gap-1.5">
                <label class="font-mono text-[10px] font-bold uppercase tracking-[.12em] text-[#5d5757]" for="email">
                    Email address
                </label>
                <div class="relative">
                    <span class="pointer-events-none absolute top-1/2 left-2.5 -translate-y-1/2 text-lg text-[#797171]">
                        ✉
                    </span>
                    <input class="h-[38px] w-full rounded-[2px] border border-[#d8cdcd] bg-white py-0 pr-3 pl-8 text-[13px] text-[#333] placeholder:text-[#9b9393] outline-none transition focus:border-black focus:ring-2 focus:ring-black/10" id="email" name="email" type="email" value="{{ old('email') }}" placeholder="admin@publicmarket.gov" required autofocus>
                </div>
            </div>
            <div class="grid gap-1.5">
                <div class="flex items-center justify-between">
                    <label class="font-mono text-[10px] font-bold uppercase tracking-[.12em] text-[#5d5757]" for="password">
                        Password
                    </label>
                    <a class="font-mono text-[10px] font-bold tracking-[.04em] text-[#464040] no-underline hover:underline" href="#">
                        Forgot Password?
                    </a>
                </div>
                <div class="relative">
                    <span class="pointer-events-none absolute top-1/2 left-2.5 -translate-y-1/2 text-lg text-[#797171]">
                        ▣
                    </span>
                    <input class="h-[38px] w-full rounded-[2px] border border-[#d8cdcd] bg-white py-0 pr-3 pl-8 text-[13px] text-[#333] outline-none transition focus:border-black focus:ring-2 focus:ring-black/10" id="password" name="password" type="password" placeholder="••••••••" required>
                </div>
            </div>
            <button class="mt-1 h-[38px] w-full cursor-pointer rounded-[2px] border-0 bg-black font-mono text-[10px] font-bold uppercase tracking-[.15em] text-white transition hover:bg-neutral-800" type="submit">
                Sign in 
                <span class="ml-1.5 text-base">
                    →
                </span>
            </button>
        </form>

        <div class="mt-9 text-center font-mono text-[10px] leading-[1.35] tracking-[.07em] text-[#716969]">
            <p class="m-0">
                Authorized Personnel Only.
            </p>
            <p class="m-0">
                Privacy Policy 
                <span class="px-1">
                    •
                </span>
                Terms of Service
            </p>
        </div>
    </main>
</x-layouts.auth>