<x-layouts.auth title="Vendor registration">
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

        <form method="POST" action="{{ route('register.store') }}" class="register-form grid gap-[17px]">
            @csrf
            
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

            @foreach ([['email', 'Email Address', 'email', 'you@example.com'], ['mobile_number', 'Mobile Number', 'tel', '+63 917 123 4567'], ['password', 'Password', 'password', 'Create a password'], ['password_confirmation', 'Confirm Password', 'password', 'Confirm your password']] as [$id, $label, $type, $placeholder])
                <div class="grid gap-[6px]">
                    <label class="text-xs font-bold text-[#455269]" for="{{ $id }}">
                        {{ $label }}
                    </label>
                    <input class="h-11 w-full rounded-[5px] border border-[#dfe7f3] bg-[#f2f6fe] px-3 text-sm text-[#172033] outline-none focus:border-[#007d5a] focus:bg-white focus:ring-3 focus:ring-[#007d5a]/12" id="{{ $id }}" name="{{ $id }}" type="{{ $type }}" value="{{ $type === 'password' ? '' : old($id) }}" placeholder="{{ $placeholder }}" required>
                </div>
            @endforeach

            <label class="flex items-center gap-[6px] text-xs leading-[1.3] text-[#687587]">
                <input class="size-3.5 accent-[#007d5a]" type="checkbox" required>
                I agree to the 
                <a class="text-[#007d5a] no-underline" href="#">
                    Terms of Service
                </a> 
                and 
                <a class="text-[#007d5a] no-underline" href="#">
                    Privacy Policy
                </a>
            </label>

            <input type="hidden" id="name" name="name" value="{{ old('name') }}">

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

    <script>
        document.querySelector('.register-form').addEventListener('submit', function () { 
            document.querySelector('#name').value = [
                document.querySelector('#first_name').value, 
                document.querySelector('#last_name').value
            ].filter(Boolean).join(' '); 
        });
    </script>
</x-layouts.auth>