<x-layouts.auth title="Vendor registration">
    <header class="registration-topbar"><a class="registration-brand" href="{{ route('login') }}"><span>▣</span><strong>Stall<span>Track</span></strong></a><p>Already have an account? <a href="{{ route('login') }}">Log in</a></p></header>
    <main class="auth-card register-card">
        <div class="register-icon">▣</div>
        <h1>Create an Account</h1>
        <p class="subtitle">Enter your details to get started with StallTrack</p>

        @if ($errors->any())
            <div class="form-alert">Please check the details and try again.</div>
        @endif

        <form method="POST" action="{{ route('register.store') }}" class="auth-form register-form">
            @csrf
            <div class="register-name-grid"><div class="field"><label for="first_name">First Name</label><input id="first_name" name="first_name" value="{{ old('first_name') }}" placeholder="Maria Clara" required autofocus></div><div class="field"><label for="last_name">Last Name</label><input id="last_name" name="last_name" value="{{ old('last_name') }}" placeholder="Del Rosario" required></div></div>
            <div class="field"><label for="email">Email Address</label><input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="you@example.com" required></div>
            <div class="field"><label for="mobile_number">Mobile Number</label><input id="mobile_number" name="mobile_number" type="tel" value="{{ old('mobile_number') }}" placeholder="+63 917 123 4567" required></div>
            <div class="field"><label for="password">Password</label><input id="password" name="password" type="password" placeholder="Create a password" required></div>
            <div class="field"><label for="password_confirmation">Confirm Password</label><input id="password_confirmation" name="password_confirmation" type="password" placeholder="Confirm your password" required></div>
            <label class="terms-check"><input type="checkbox" required> I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></label>
            <input type="hidden" id="name" name="name" value="{{ old('name') }}">
            <button class="primary-button" type="submit">Create Account</button>
        </form>

        <div class="register-footer">Already have an account? <a href="{{ route('login') }}">Log in</a></div>
    </main>
    <script>document.querySelector('.register-form').addEventListener('submit', function () { document.querySelector('#name').value = [document.querySelector('#first_name').value, document.querySelector('#last_name').value].filter(Boolean).join(' '); });</script>
</x-layouts.auth>
