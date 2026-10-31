<div class="amc-register-page">

```
<div class="amc-register-container">

    {{-- Lado izquierdo --}}
    <div class="amc-register-brand" style="--auth-motocross-image: url('{{ asset('imagenes/WhatsApp Image 2026-10-01 at 19.06.13 (4).jpeg') }}')">

        <div class="amc-logo">
            AMC
        </div>

        <div class="amc-brand-content">
            <h1>AMC</h1>

            <h2>Asociación de Motociclismo de Cochabamba</h2>

            <p>
                Crea tu cuenta para acceder a la plataforma
                de gestión de eventos de motocross.
            </p>
        </div>

        <div class="amc-brand-line"></div>

        <small>
            Plataforma de gestión de eventos de motocross
        </small>

    </div>


    {{-- Lado derecho --}}
    <div class="amc-register-form">

        <div class="amc-form-header">

            <span class="amc-welcome">
                Crear cuenta
            </span>

            <h2>Registrarse</h2>

            <p>
                Completa tus datos para crear una cuenta
            </p>

        </div>


        <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data">
            @csrf


            {{-- Nombre --}}
            <div class="amc-field">

                <label for="first_name">
                    Nombre
                </label>

                <div class="amc-input-wrapper">

                    <span class="amc-input-icon">
                        👤
                    </span>

                    <input
                        id="first_name"
                        type="text"
                        name="first_name"
                        value="{{ old('first_name') }}"
                        class="@error('first_name') amc-input-error @enderror"
                        placeholder="Ingresa tu nombre"
                        required
                        autofocus
                        autocomplete="given-name"
                    >

                </div>

                @error('first_name')
                    <div class="amc-error">
                        {{ $message }}
                    </div>
                @enderror

            </div>

            <div class="amc-field">

                <label for="last_name">
                    Apellido
                </label>

                <div class="amc-input-wrapper">

                    <span class="amc-input-icon">
                        👤
                    </span>

                    <input
                        id="last_name"
                        type="text"
                        name="last_name"
                        value="{{ old('last_name') }}"
                        class="@error('last_name') amc-input-error @enderror"
                        placeholder="Ingresa tu apellido"
                        required
                        autocomplete="family-name"
                    >

                </div>

                @error('last_name')
                    <div class="amc-error">
                        {{ $message }}
                    </div>
                @enderror

            </div>

            <div class="amc-field">
                <label for="identity_document_number">Número de carnet de identidad</label>
                <div class="amc-input-wrapper">
                    <span class="amc-input-icon">▤</span>
                    <input
                        id="identity_document_number"
                        type="text"
                        name="identity_document_number"
                        value="{{ old('identity_document_number') }}"
                        class="@error('identity_document_number') amc-input-error @enderror"
                        placeholder="Ingresa tu número de carnet"
                        maxlength="30"
                        required
                        autocomplete="off"
                    >
                </div>
                @error('identity_document_number')
                    <div class="amc-error">{{ $message }}</div>
                @enderror
            </div>


            {{-- Correo --}}
            <div class="amc-field">

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
            <div class="amc-field">

                <label for="password">
                    Contraseña
                </label>

                <div class="amc-input-wrapper">

                    <span class="amc-input-icon">
                        🔒
                    </span>

                    <input
                        id="password"
                        type="password"
                        name="password"
                        class="@error('password') amc-input-error @enderror"
                        placeholder="Crea una contraseña"
                        required
                        autocomplete="new-password"
                    >

                </div>

                @error('password')
                    <div class="amc-error">
                        {{ $message }}
                    </div>
                @enderror

            </div>


            {{-- Confirmar contraseña --}}
            <div class="amc-field">

                <label for="password_confirmation">
                    Confirmar contraseña
                </label>

                <div class="amc-input-wrapper">

                    <span class="amc-input-icon">
                        🔒
                    </span>

                    <input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        class="@error('password_confirmation') amc-input-error @enderror"
                        placeholder="Repite tu contraseña"
                        required
                        autocomplete="new-password"
                    >

                </div>

                @error('password_confirmation')
                    <div class="amc-error">
                        {{ $message }}
                    </div>
                @enderror

            </div>


            <div id="pilot-verification-fields" class="amc-field">
                <label for="document_photo">
                    Fotografía del anverso (frente) del carnet
                </label>
                <div class="amc-input-wrapper amc-file-wrapper">
                    <input
                        id="document_photo"
                        type="file"
                        name="document_photo"
                        accept="image/jpeg,image/png,image/webp"
                        required
                        class="@error('document_photo') amc-input-error @enderror"
                    >
                </div>
                @error('document_photo')
                    <div class="amc-error">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div id="pilot-document-back-field" class="amc-field">
                <label for="document_back_photo">
                    Fotografía del reverso del carnet
                </label>
                <div class="amc-input-wrapper amc-file-wrapper">
                    <input
                        id="document_back_photo"
                        type="file"
                        name="document_back_photo"
                        accept="image/jpeg,image/png,image/webp"
                        required
                        class="@error('document_back_photo') amc-input-error @enderror"
                    >
                </div>
                @error('document_back_photo')
                    <div class="amc-error">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div id="pilot-selfie-fields" class="amc-field">
                <label for="selfie_photo">
                    Selfie actual
                </label>
                <div class="amc-input-wrapper amc-file-wrapper">
                    <input
                        id="selfie_photo"
                        type="file"
                        name="selfie_photo"
                        accept="image/jpeg,image/png,image/webp"
                        required
                        class="@error('selfie_photo') amc-input-error @enderror"
                    >
                </div>
                @error('selfie_photo')
                    <div class="amc-error">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="amc-field">
                <label class="d-flex align-items-start gap-2">
                    <input type="checkbox" name="identity_processing_consent" value="1" required>
                    <span>
                        Autorizo el procesamiento local de ambas caras de mi carnet y fotografías para validar mi identidad. Si el OCR o la comparación facial no son concluyentes, mi solicitud quedará pendiente de revisión por un administrador. No se realiza una prueba de vida.
                    </span>
                </label>
                @error('identity_processing_consent')
                    <div class="amc-error">{{ $message }}</div>
                @enderror
            </div>

            {{-- Botones --}}
            <div class="amc-register-actions">

                <a href="{{ route('login') }}">
                    ¿Ya tienes una cuenta?
                </a>

                <button
                    type="submit"
                    class="amc-register-button"
                >
                    Crear cuenta
                </button>

            </div>

        </form>

    </div>

</div>
```

<style>

    * {
        box-sizing: border-box;
    }


    .amc-register-page {
        min-height: 100vh;
        width: 100%;

        background:
            radial-gradient(
                circle at 15% 20%,
                rgba(220, 0, 0, 0.18),
                transparent 30%
            ),
            radial-gradient(
                circle at 85% 80%,
                rgba(255, 70, 0, 0.10),
                transparent 30%
            ),
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


    .amc-register-container {
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

    .amc-register-brand {
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


    .amc-register-brand::before {
        content: "";

        position: absolute;

        width: 420px;
        height: 420px;

        border: 1px solid rgba(255, 30, 30, 0.25);

        border-radius: 50%;

        right: -180px;
        bottom: -170px;
    }


    .amc-register-brand::after {
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


    .amc-register-brand small {
        position: relative;
        z-index: 2;

        color: #777;

        font-size: 12px;
    }


    /* ==========================
       FORMULARIO
    ========================== */

    .amc-register-form {
        background: #ffffff;

        padding: 45px 70px;

        display: flex;
        flex-direction: column;
        justify-content: center;
    }


    .amc-form-header {
        margin-bottom: 25px;
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

    .amc-field {
        margin-bottom: 16px;
    }


    .amc-field label {
        display: block;

        margin-bottom: 7px;

        color: #333;

        font-size: 14px;

        font-weight: 600;
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

        height: 46px;

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
        margin-top: 5px;

        color: #dc3545;

        font-size: 12px;
    }


    /* ==========================
       ACCIONES
    ========================== */

    .amc-register-actions {
        display: flex;

        align-items: center;

        justify-content: space-between;

        gap: 15px;

        margin-top: 22px;
    }


    .amc-register-actions a {
        color: #e10600;

        font-size: 12px;

        font-weight: 600;

        text-decoration: none;
    }


    .amc-register-actions a:hover {
        text-decoration: underline;
    }


    .amc-register-button {
        height: 48px;

        padding: 0 28px;

        border: none;

        border-radius: 10px;

        background: #e10600;

        color: white;

        font-size: 14px;

        font-weight: 700;

        cursor: pointer;

        transition:
            transform 0.2s ease,
            background 0.2s ease,
            box-shadow 0.2s ease;
    }


    .amc-register-button:hover {
        background: #bd0500;

        transform: translateY(-1px);

        box-shadow:
            0 8px 20px rgba(225, 6, 0, 0.25);
    }


    .amc-register-button:active {
        transform: translateY(0);
    }


    /* ==========================
       RESPONSIVE
    ========================== */

    @media (max-width: 850px) {

        .amc-register-container {
            grid-template-columns: 1fr;

            max-width: 520px;
        }


        .amc-register-brand {
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


        .amc-register-form {
            padding: 40px;
        }

    }


    @media (max-width: 500px) {

        .amc-register-page {
            padding: 15px;
        }


        .amc-register-container {
            border-radius: 15px;
        }


        .amc-register-brand {
            padding: 35px 25px;

            min-height: 250px;
        }


        .amc-register-form {
            padding: 35px 25px;
        }


        .amc-form-header h2 {
            font-size: 27px;
        }


        .amc-register-actions {
            flex-direction: column-reverse;

            align-items: stretch;
        }


        .amc-register-actions a {
            text-align: center;
        }


        .amc-register-button {
            width: 100%;
        }

    }

</style>
@include('layouts.tema-autenticacion-piloto')
