
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Configuración de administrador</title>
</head>
<body>
    <h1>Activar cuenta de administrador</h1>

    <form method="POST" action="{{ url('/configurar-admin-temporal') }}">
        @csrf

        <label for="token">Clave de configuración:</label>

        <input
            type="password"
            id="token"
            name="token"
            required
            autocomplete="off"
        >

        <button type="submit">
            Asignar rol de administrador
        </button>
    </form>
</body>
</html>
