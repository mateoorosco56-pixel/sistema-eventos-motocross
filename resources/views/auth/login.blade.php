<div class="amc-login-page">

    <div class="amc-login-container">

        {{-- Lado izquierdo --}}
        <div class="amc-login-brand" style="--auth-motocross-image: url('{{ asset('imagenes/WhatsApp Image 2026-10-01 at 19.06.13 (4).jpeg') }}')">

            <div class="amc-logo">
                AMC
            </div>

            <div class="amc-brand-content">
                <h1>AMC</h1>

                <h2>Asociación de Motociclismo de Cochabamba</h2>

                <p>
                    Gestión y publicación de eventos de motocross,
                    participantes y resultados.
                </p>
            </div>

            <div class="amc-brand-line"></div>

            <small>
                Plataforma de gestión de eventos de motocross
            </small>

        </div>


        {{-- Lado derecho --}}
        <div class="amc-login-form">

            <div class="amc-form-header">

                <span class="amc-welcome">
                    Bienvenido
                </span>

                <h2>Iniciar sesión</h2>

                <p>
                    Ingresa tus datos para continuar
                </p>

            </div>


            <form method="POST" action="{{ route('login') }}">
                @csrf


                {{-- Correo --}}
                <div class="amc-field mb-4">

                    <label for="email">
                        Correo electrónico
                    </label>

                    <div class="amc-input-wrapper">

                        <span class="amc-input-icon">
                            ✉
                        </span>

                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            class="@error('email') amc-input-error @enderror"
                            placeholder="ejemplo@correo.com"
                            required
                            autofocus
                            autocomplete="username"
                        >

                    </div>

                    @error('email')
                        <div class="amc-error">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                {{-- Contraseña --}}
                <div class="amc-field mb-3">

                    <div class="amc-label-row">

                        <label for="password">
                            Contraseña
                        </label>

                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}">
                                ¿Olvidaste tu contraseña?
                            </a>
                        @endif

                    </div>


                    <div class="amc-input-wrapper">

                        <span class="amc-input-icon">
                            🔒
                        </span>

                        <input
                            id="password"
                            type="password"
                            name="password"
                            class="@error('password') amc-input-error @enderror"
                            placeholder="Ingresa tu contraseña"
                            required
                            autocomplete="current-password"
                        >

                    </div>

                    @error('password')
                        <div class="amc-error">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                {{-- Recordarme --}}
                <div class="amc-remember mb-4">

                    <label>

                        <input
                            id="remember_me"
                            type="checkbox"
                            name="remember"
                        >

                        <span>
                            Recordarme
                        </span>

                    </label>

                </div>


                {{-- Botón --}}
                <button
                    type="submit"
                    class="amc-login-button"
                >
                    Iniciar sesión
                </button>


                {{-- Registro --}}
                @if (Route::has('register'))

                    <div class="amc-register">

                        <span>
                            ¿No tienes una cuenta?
                        </span>

                        <a href="{{ route('register') }}">
                            Regístrate aquí
                        </a>

                    </div>

                @endif

            </form>

        </div>

    </div>

</div>


<style>

    * {
        box-sizing: border-box;
    }

    .amc-login-page {
        min-height: 100vh;
        width: 100%;
        background:
            radial-gradient(circle at 15% 20%, rgba(220, 0, 0, 0.18), transparent 30%),
            radial-gradient(circle at 85% 80%, rgba(255, 70, 0, 0.10), transparent 30%),
            #0b0b0b;

        display: flex;
        align-items: center;
        justify-content: center;

        padding: 30px 20px;

        font-family:
            Inter,
            -apple-system,
            BlinkMacSystemFont,
            "Segoe UI",
            sans-serif;
    }


    .amc-login-container {
        width: 100%;
        max-width: 1050px;

        min-height: 620px;

        display: grid;
        grid-template-columns: 1fr 1fr;

        background: #151515;

        border-radius: 22px;

        overflow: hidden;

        border: 1px solid rgba(255, 255, 255, 0.08);

        box-shadow:
            0 25px 70px rgba(0, 0, 0, 0.65);
    }


    /* ==========================
       LADO AMC
    ========================== */

    .amc-login-brand {
        position: relative;

        display: flex;
        flex-direction: column;
        justify-content: center;

        padding: 65px;

        color: white;

        overflow: hidden;

        background:
            linear-gradient(
                145deg,
                #050505 0%,
                #171717 55%,
                #260000 100%
            );
    }


    .amc-login-brand::before {
        content: "";

        position: absolute;

        width: 420px;
        height: 420px;

        border: 1px solid rgba(255, 30, 30, 0.25);

        border-radius: 50%;

        right: -180px;
        bottom: -170px;
    }


    .amc-login-brand::after {
        content: "";

        position: absolute;

        width: 280px;
        height: 280px;

        border: 1px solid rgba(255, 60, 0, 0.15);

        border-radius: 50%;

        right: -100px;
        bottom: -90px;
    }


    .amc-logo {
        position: relative;
        z-index: 2;

        display: inline-flex;

        width: fit-content;

        font-size: 42px;
        font-weight: 900;

        letter-spacing: 4px;

        color: #ffffff;

        border-left: 6px solid #e10600;

        padding-left: 16px;

        margin-bottom: 45px;
    }


    .amc-brand-content {
        position: relative;
        z-index: 2;
    }


    .amc-brand-content h1 {
        margin: 0 0 8px;

        font-size: 58px;

        line-height: 1;

        font-weight: 900;

        letter-spacing: -2px;
    }


    .amc-brand-content h2 {
        max-width: 380px;

        margin: 0 0 20px;

        font-size: 20px;

        line-height: 1.4;

        font-weight: 500;

        color: #eeeeee;
    }


    .amc-brand-content p {
        max-width: 400px;

        margin: 0;

        color: #a9a9a9;

        font-size: 15px;

        line-height: 1.7;
    }


    .amc-brand-line {
        position: relative;
        z-index: 2;

        width: 65px;
        height: 4px;

        background: #e10600;

        margin: 35px 0 18px;
    }


    .amc-login-brand small {
        position: relative;
        z-index: 2;

        color: #777;

        font-size: 12px;
    }


    /* ==========================
       FORMULARIO
    ========================== */

    .amc-login-form {
        background: #ffffff;

        padding: 65px 70px;

        display: flex;
        flex-direction: column;
        justify-content: center;
    }


    .amc-form-header {
        margin-bottom: 35px;
    }


    .amc-welcome {
        display: inline-block;

        margin-bottom: 8px;

        color: #e10600;

        font-size: 13px;

        font-weight: 700;

        text-transform: uppercase;

        letter-spacing: 1.5px;
    }


    .amc-form-header h2 {
        margin: 0 0 8px;

        color: #171717;

        font-size: 32px;

        font-weight: 800;
    }


    .amc-form-header p {
        margin: 0;

        color: #777;

        font-size: 14px;
    }


    /* Campos */

    .amc-field label {
        display: block;

        margin-bottom: 8px;

        color: #333;

        font-size: 14px;

        font-weight: 600;
    }


    .amc-label-row {
        display: flex;

        justify-content: space-between;

        align-items: center;

        gap: 10px;
    }


    .amc-label-row a {
        color: #e10600;

        font-size: 12px;

        text-decoration: none;

        font-weight: 600;
    }


    .amc-label-row a:hover {
        text-decoration: underline;
    }


    .amc-input-wrapper {
        position: relative;
    }


    .amc-input-icon {
        position: absolute;

        left: 15px;
        top: 50%;

        transform: translateY(-50%);

        font-size: 16px;

        opacity: 0.65;

        z-index: 2;
    }


    .amc-input-wrapper input {
        width: 100%;

        height: 50px;

        padding: 0 15px 0 45px;

        border: 1px solid #dddddd;

        border-radius: 10px;

        background: #fafafa;

        color: #222;

        font-size: 14px;

        outline: none;

        transition:
            border-color 0.2s ease,
            box-shadow 0.2s ease,
            background 0.2s ease;
    }


    .amc-input-wrapper input:focus {
        background: #ffffff;

        border-color: #e10600;

        box-shadow:
            0 0 0 3px rgba(225, 6, 0, 0.10);
    }


    .amc-input-wrapper input::placeholder {
        color: #aaa;
    }


    .amc-input-wrapper input.amc-input-error {
        border-color: #dc3545;
    }


    .amc-error {
        margin-top: 6px;

        color: #dc3545;

        font-size: 12px;
    }


    /* Recordarme */

    .amc-remember label {
        display: flex;

        align-items: center;

        gap: 8px;

        cursor: pointer;

        color: #666;

        font-size: 13px;
    }


    .amc-remember input {
        width: 16px;
        height: 16px;

        accent-color: #e10600;

        cursor: pointer;
    }


    /* Botón */

    .amc-login-button {
        width: 100%;

        height: 52px;

        border: none;

        border-radius: 10px;

        background: #e10600;

        color: white;

        font-size: 15px;

        font-weight: 700;

        cursor: pointer;

        transition:
            transform 0.2s ease,
            background 0.2s ease,
            box-shadow 0.2s ease;
    }


    .amc-login-button:hover {
        background: #bd0500;

        transform: translateY(-1px);

        box-shadow:
            0 8px 20px rgba(225, 6, 0, 0.25);
    }


    .amc-login-button:active {
        transform: translateY(0);
    }


    /* Registro */

    .amc-register {
        text-align: center;

        margin-top: 28px;

        color: #777;

        font-size: 13px;
    }


    .amc-register a {
        color: #e10600;

        font-weight: 700;

        text-decoration: none;

        margin-left: 4px;
    }


    .amc-register a:hover {
        text-decoration: underline;
    }


    /* ==========================
       RESPONSIVE
    ========================== */

    @media (max-width: 850px) {

        .amc-login-container {
            grid-template-columns: 1fr;

            max-width: 520px;
        }


        .amc-login-brand {
            min-height: 280px;

            padding: 40px;
        }


        .amc-logo {
            margin-bottom: 25px;

            font-size: 34px;
        }


        .amc-brand-content h1 {
            font-size: 42px;
        }


        .amc-brand-content h2 {
            font-size: 17px;
        }


        .amc-login-form {
            padding: 45px 40px;
        }

    }


    @media (max-width: 500px) {

        .amc-login-page {
            padding: 15px;
        }


        .amc-login-container {
            border-radius: 15px;
        }


        .amc-login-brand {
            padding: 35px 25px;

            min-height: 250px;
        }


        .amc-login-form {
            padding: 35px 25px;
        }


        .amc-form-header h2 {
            font-size: 27px;
        }

    }

</style>
@include('layouts.tema-autenticacion-piloto')