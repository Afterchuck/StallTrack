<x-layouts.auth title="Sign in">
    <main class="auth-card login-card">
        <div class="brand-mark" aria-hidden="true">▦</div>
        <h1>Public Market</h1>
        <p class="subtitle">Management Portal</p>

        @if ($errors->any())
            <div class="form-alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="auth-form">
            @csrf
            <div class="field">
                <div class="field-heading"><label for="email">Email address</label></div>
                <div class="input-wrap"><span class="input-icon">✉</span><input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="user@gmail.com" required autofocus></div>
            </div>
            <div class="field">
                <div class="field-heading"><label for="password">Password</label><a href="#">Forgot Password?</a></div>
                <div class="input-wrap"><span class="input-icon">▣</span><input id="password" name="password" type="password" placeholder="••••••••" required></div>
            </div>
            <button class="primary-button" type="submit">Sign in <span>→</span></button>
        </form>

        <div class="auth-footer"><p>Authorized Personnel Only.</p><p>Privacy Policy <span>·</span> Terms of Service</p></div>
        <p class="switch-auth">Are you a vendor? <a href="{{ route('register') }}">Register here</a></p>
    </main>
</x-layouts.auth>
