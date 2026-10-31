<style>
    .amc-login-page,
    .amc-register-page {
        min-height: 100vh;
        padding: 16px;
        background: #edf2f5;
        font-family: "Segoe UI", sans-serif;
    }

    .amc-login-container,
    .amc-register-container {
        max-width: 980px;
        min-height: 0;
        border: 1px solid #dbe4e9;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 8px 32px rgba(10, 37, 56, .13);
    }

    .amc-login-brand,
    .amc-register-brand {
        background-image: linear-gradient(180deg, rgba(4, 27, 43, .1), rgba(4, 27, 43, .76)), url("{{ asset('imagenes/WhatsApp Image 2026-10-01 at 19.06.13 (3).jpeg') }}");
        background-position: center;
        background-size: cover;
    }

    .amc-login-brand::before,
    .amc-login-brand::after,
    .amc-register-brand::before,
    .amc-register-brand::after {
        display: none;
    }

    .amc-login-brand .amc-logo,
    .amc-register-brand .amc-logo {
        border-left-color: #f45b0b;
    }

    .amc-brand-line { background: #f45b0b; }
    .amc-login-form,
    .amc-register-form { background: #fff; }
    .amc-login-form .amc-welcome,
    .amc-register-form .amc-welcome,
    .amc-label-row a,
    .amc-register a,
    .amc-register-actions > a { color: #e85a0c; }

    .amc-login-form .amc-input-wrapper input:focus,
    .amc-register-form .amc-input-wrapper input:focus {
        border-color: #f45b0b;
        box-shadow: 0 0 0 3px rgba(244, 91, 11, .13);
    }

    .amc-login-button,
    .amc-register-button {
        border-color: #f45b0b;
        border-radius: 5px;
        background: #f45b0b;
        color: #fff;
    }

    .amc-login-button:hover,
    .amc-register-button:hover { background: #db4c06; }

    @media (max-width: 700px) {
        .amc-login-page,
        .amc-register-page { display: block; padding: 0; }

        .amc-login-container,
        .amc-register-container {
            display: flex;
            flex-direction: column;
            width: min(100%, 420px);
            min-height: 100vh;
            margin: 0 auto;
            border: 0;
            border-radius: 0;
            box-shadow: none;
        }

        .amc-login-brand,
        .amc-register-brand {
            flex: 0 0 205px;
            min-height: 205px;
            justify-content: flex-end;
            padding: 1.15rem 1.35rem;
        }

        .amc-login-brand .amc-logo,
        .amc-register-brand .amc-logo {
            margin-bottom: .65rem;
            padding-left: .6rem;
            border-left-width: 3px;
            font-size: 1.55rem;
        }

        .amc-brand-content h1 { margin-bottom: .2rem; font-size: 1.8rem; }
        .amc-brand-content h2 { max-width: 290px; margin-bottom: .25rem; font-size: .82rem; }
        .amc-brand-content p,
        .amc-login-brand small,
        .amc-register-brand small,
        .amc-brand-line { display: none; }
        .amc-login-form,
        .amc-register-form { flex: 1; padding: 1.25rem; }
        .amc-form-header { margin-bottom: 1rem; }
        .amc-form-header h2 { font-size: 1.35rem; }
        .amc-field label { font-size: .76rem; }
        .amc-input-wrapper input,
        .amc-input-wrapper select { height: 42px; border-radius: 5px; font-size: .76rem; }
        .amc-login-button,
        .amc-register-button { min-height: 40px; }
    }
</style>