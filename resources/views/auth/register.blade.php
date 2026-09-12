<x-layouts.auth title="Vendor registration">
    <main class="auth-card register-card">
        <div class="wordmark">storefront</div>
        <h1>Public Market</h1>
        <p class="subtitle">Management Portal Registration</p>

        @if ($errors->any())
            <div class="form-alert">Please check the highlighted details and try again.</div>
        @endif

        <form method="POST" action="{{ route('register.store') }}" class="auth-form">
            @csrf
            <div class="field"><label for="name">Full name</label><input id="name" name="name" type="text" value="{{ old('name') }}" placeholder="Enter your full name" required autofocus></div>
            <div class="field"><label for="email">Work email</label><input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="Enter your work email" required></div>
            <div class="field"><label for="password">Password</label><input id="password" name="password" type="password" placeholder="Create a strong password" required></div>
            <div class="field"><label for="password_confirmation">Confirm password</label><input id="password_confirmation" name="password_confirmation" type="password" placeholder="Re-enter your password" required></div>
            <button class="primary-button" type="submit">Create account <span>→</span></button>
        </form>

        <div class="register-footer">Already registered? <a href="{{ route('login') }}">Sign in instead</a></div>
    </main>
</x-layouts.auth>
