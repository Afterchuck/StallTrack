//
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('input[name="mobile_number"]').forEach((input) => {
        const keepDigitsOnly = () => {
            input.value = input.value.replace(/\D/g, '').slice(0, 10);
        };

        input.addEventListener('input', keepDigitsOnly);
        keepDigitsOnly();
    });

    document.querySelectorAll('.password-toggle').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.target);

            if (!input) {
                return;
            }

            const isHidden = input.type === 'password';

            input.type = isHidden ? 'text' : 'password';
            button.textContent = isHidden ? 'Hide' : 'Show';
            button.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
            button.setAttribute('aria-pressed', String(isHidden));
        });
    });
});
