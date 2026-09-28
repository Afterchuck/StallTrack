<x-layouts.auth title="Create an account">
    <header class="flex min-h-16 items-center justify-between border-y border-[#dbe5f3] bg-white px-10 max-[520px]:px-[18px]">
        <a class="flex items-center gap-2.5 text-base text-[#17243a] no-underline" href="{{ route('login') }}">
            <span class="grid size-[30px] place-items-center rounded bg-[#007d5a] text-[17px] text-white">
                ▣
            </span>
            <strong>
                Stall
                <span class="text-[#007d5a]">
                    Track
                </span>
            </strong>
        </a>
        <p class="m-0 text-[13px] text-[#687587] max-[520px]:text-[11px]">
            Already have an account? 
            <a class="font-bold text-[#007d5a] no-underline" href="{{ route('login') }}">
                Log in
            </a>
        </p>
    </header>

    <main class="mx-auto my-7 mb-9 w-full max-w-[500px] rounded-[6px] border border-[#d6e1ef] bg-white px-[30px] pt-8 pb-[26px] shadow-[0_2px_7px_rgba(15,28,46,.03)] max-[520px]:my-5 max-[520px]:mb-[30px] max-[520px]:mx-4 max-[520px]:w-auto max-[520px]:px-[22px] max-[520px]:pt-7 max-[520px]:pb-6">
        <div class="mx-auto mb-[14px] grid size-10 place-items-center rounded-[5px] bg-[#007d5a] text-[21px] text-white">
            ▣
        </div>
        <h1 class="m-0 text-center text-2xl font-bold text-[#17243a]">
            Create an Account
        </h1>
        <p class="mt-[7px] mb-[25px] text-center text-[13px] text-[#687587]">
            Enter your details to get started with StallTrack
        </p>

        @if ($errors->any())
            <div class="rounded border border-[#f0caca] bg-[#fff5f5] px-3 py-2.5 text-[13px] text-[#a03333]">
                Please check the details and try again.
            </div>
        @endif

        <form method="POST" action="{{ route('register.store') }}" class="grid gap-[17px]">
            @csrf

            <fieldset class="grid grid-cols-2 overflow-hidden rounded-[5px] border border-[#dfe7f3] bg-[#f2f6fe] p-1">
                <legend class="sr-only">Account type</legend>
                @foreach (['admin' => 'Market Staff / Admin', 'vendor' => 'Vendor / Stallholder'] as $value => $label)
                    <label class="cursor-pointer rounded-[3px] px-2 py-3 text-center text-xs font-bold text-[#687587] transition has-[:checked]:bg-white has-[:checked]:text-[#007d5a] has-[:checked]:shadow-sm">
                        <input class="sr-only" name="role" type="radio" value="{{ $value }}" @checked(old('role', 'admin') === $value)>
                        {{ $label }}
                    </label>
                @endforeach
            </fieldset>

            <div class="grid grid-cols-2 gap-3 max-[520px]:grid-cols-1">
                @foreach ([['first_name', 'First Name', 'Maria Clara'], ['last_name', 'Last Name', 'Del Rosario']] as [$id, $label, $placeholder])
                    <div class="grid gap-[6px]">
                        <label class="text-xs font-bold text-[#455269]" for="{{ $id }}">
                            {{ $label }}
                        </label>
                        <input class="h-11 w-full rounded-[5px] border border-[#dfe7f3] bg-[#f2f6fe] px-3 text-sm text-[#172033] outline-none focus:border-[#007d5a] focus:bg-white focus:ring-3 focus:ring-[#007d5a]/12" id="{{ $id }}" name="{{ $id }}" value="{{ old($id) }}" placeholder="{{ $placeholder }}" required @if ($id === 'first_name') autofocus @endif>
                    </div>
                @endforeach
            </div>

            @foreach ([
                ['email', 'Email Address', 'email', 'you@example.com'],
                ['mobile_number', 'Mobile Number', 'tel', '9171234567'],
                ['password', 'Password', 'password', 'Create a password'],
                ['password_confirmation', 'Confirm Password', 'password', 'Confirm your password']
            ] as [$id, $label, $type, $placeholder])
                <div class="grid gap-[6px]">
                    <label class="text-xs font-bold text-[#455269]" for="{{ $id }}">
                        {{ $label }}
                    </label>
                        @if ($id === 'password' || $id === 'password_confirmation')
                            <div class="relative">
                                <input
                                    class="h-11 w-full rounded-[5px] border border-[#dfe7f3] bg-[#f2f6fe] px-3 pr-12 text-sm text-[#172033] outline-none focus:border-[#007d5a] focus:bg-white focus:ring-3 focus:ring-[#007d5a]/12"
                                    id="{{ $id }}"
                                    name="{{ $id }}"
                                    type="password"
                                    value=""
                                    placeholder="{{ $placeholder }}"
                                    required
                                >
                                <button
                                    type="button"
                                    class="password-toggle absolute inset-y-0 right-2 my-auto rounded text-[11px] font-bold text-[#007d5a]"
                                    data-target="{{ $id }}"
                                    aria-label="Show password"
                                    aria-pressed="false"
                                >
                                    Show
                                </button>
                            </div>
                        @elseif ($id === 'mobile_number')
                            <div class="flex h-11 items-center rounded-[5px] border border-[#dfe7f3] bg-[#f2f6fe] px-3 text-sm text-[#172033] focus-within:border-[#007d5a] focus-within:bg-white focus-within:ring-3 focus-within:ring-[#007d5a]/12">
                                <span class="mr-2 border-r border-[#dfe7f3] pr-2 text-[#687587]">+63</span>
                                <input
                                    class="h-full min-w-0 flex-1 border-0 bg-transparent p-0 text-sm text-[#172033] outline-none"
                                    id="{{ $id }}"
                                    name="{{ $id }}"
                                    type="tel"
                                    inputmode="numeric"
                                    maxlength="10"
                                    pattern="[0-9]{10}"
                                    value="{{ old($id) }}"
                                    placeholder="{{ $placeholder }}"
                                    title="Enter 10 digits after +63"
                                    required
                                >
                            </div>
                        @else
                            <input
                                class="h-11 w-full rounded-[5px] border border-[#dfe7f3] bg-[#f2f6fe] px-3 text-sm text-[#172033] outline-none focus:border-[#007d5a] focus:bg-white focus:ring-3 focus:ring-[#007d5a]/12"
                                id="{{ $id }}"
                                name="{{ $id }}"
                                type="{{ $type }}"
                                value="{{ old($id) }}"
                                placeholder="{{ $placeholder }}"
                                required
                            >
                        @endif
                        @if ($id === 'mobile_number')
                            <p class="text-xs text-[#687587]">Enter 10 digits after +63.</p>
                            @error('mobile_number')
                                <p class="text-xs text-[#a03333]" role="alert">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>
            @endforeach

            <label class="flex items-center gap-[6px] text-xs leading-[1.3] text-[#687587]">
                <input class="size-3.5 accent-[#007d5a]" name="terms" type="checkbox" value="1" @checked(old('terms')) required>
                I agree to the 
                <a class="text-[#007d5a] no-underline" href="#">
                    Terms of Service
                </a> 
                and 
                <a class="text-[#007d5a] no-underline" href="#">
                    Privacy Policy
                </a>
            </label>

            <button class="min-h-[46px] w-full cursor-pointer rounded-[5px] border-0 bg-[#007d5a] text-[13px] font-bold text-white transition hover:bg-[#006548]" type="submit">
                Create Account
            </button>
        </form>

        <div class="mt-[22px] text-center text-xs text-[#718097]">
            Already have an account? 
            <a class="font-bold text-[#007d5a] no-underline hover:underline" href="{{ route('login') }}">
                Log in
            </a>
        </div>
    </main>

</x-layouts.auth>
