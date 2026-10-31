<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Nombres de la base de datos

Las tablas propias del sistema usan nombres en español: `eventos`, `categorias`, `participantes`, `inscripciones`, `solicitudes_organizador`, `categoria_evento`, `premios_evento`, `ordenes_salida_evento` y `circuitos`. Las tablas estándar de Laravel, como `users`, `jobs` y `notifications`, conservan sus nombres.

La migración `2026_10_12_000000_renombrar_tablas_del_sistema` cambia los nombres existentes sin borrar sus registros, actualiza los tipos de notificación guardados y permite revertir esos cambios. Las migraciones anteriores se mantienen intactas para conservar el historial de la base de datos.

## Avisos de nuevos eventos por correo

Al crear un evento programado o publicar uno existente, el sistema envía un correo a las cuentas con rol `usuario` y `piloto`, además de guardar la notificación interna. Para entregar los correos, configura un transporte SMTP en `.env` (`MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` y `MAIL_FROM_ADDRESS`). La configuración de ejemplo usa `MAIL_MAILER=log`, que registra los correos en los logs en lugar de enviarlos.

## Verificación local de identidad de pilotos

El registro público crea cuentas con rol `piloto` y solicita nombres, apellidos, número de carnet, fotos del anverso y reverso del carnet y una fotografía actual del rostro. Los archivos se guardan en el disco privado `local` de Laravel. Tesseract realiza OCR en español e inglés en ambas caras y OpenCV Zoo YuNet/SFace detecta y compara los rostros usando el anverso y la selfie. No se envían imágenes a servicios externos y no se realiza prueba de vida.

Para configurar el procesamiento en Windows con XAMPP:

1. Instala Python 3.10–3.12 y Tesseract OCR. Instala los datos de idioma `spa` y `eng` y comprueba que `tesseract --list-langs` los muestra.
2. Desde la raíz del proyecto, crea un entorno Python e instala las dependencias fijadas:

   ```powershell
   py -3.11 -m venv .venv-identity
   .\.venv-identity\Scripts\python.exe -m pip install -r requirements-identity.txt
   ```

3. Descarga los modelos ONNX `face_detection_yunet_2023mar.onnx` y `face_recognition_sface_2021dec.onnx` de los directorios oficiales [YuNet](https://github.com/opencv/opencv_zoo/tree/main/models/face_detection_yunet) y [SFace](https://github.com/opencv/opencv_zoo/tree/main/models/face_recognition_sface). Guárdalos en `storage/app/private/identity-models/` y no en `public/`.
4. Configura en `.env` las rutas absolutas correspondientes a tu instalación:

   ```dotenv
   IDENTITY_PYTHON_BINARY="C:\xampp\htdocs\motocross\motocross\.venv-identity\Scripts\python.exe"
   TESSERACT_BINARY="C:\Program Files\Tesseract-OCR\tesseract.exe"
   IDENTITY_YUNET_MODEL="C:\xampp\htdocs\motocross\motocross\storage\app\private\identity-models\face_detection_yunet_2023mar.onnx"
   IDENTITY_SFACE_MODEL="C:\xampp\htdocs\motocross\motocross\storage\app\private\identity-models\face_recognition_sface_2021dec.onnx"
   ```
   El usuario que ejecuta Apache/PHP debe poder ejecutar Python y Tesseract, leer los modelos y escribir en `storage/app/private/`.

5. Haz una copia de seguridad de la base de datos y luego ejecuta `php artisan migrate` para agregar los campos de identidad y revisión.

La migración conserva las columnas y archivos existentes, pero cambia el estado heredado `verificada` a `pending_review`: el verificador previo solo comprobaba la presencia de imágenes y no comparaba identidades. Esos pilotos deberán ser revisados nuevamente por un administrador antes de inscribirse.

La identidad solo se aprueba automáticamente si el OCR confirma el número de carnet ingresado, se detecta exactamente un rostro de al menos `40 × 40` píxeles en el anverso y la selfie, la confianza de detección es de al menos `0.75` y la similitud coseno SFace es al menos `0.363`, umbral de referencia usado por [la demostración oficial de OpenCV Zoo](https://github.com/opencv/opencv_zoo/blob/main/models/face_recognition_sface/demo.py) para esta combinación de modelos. Para ayudar con fotos oscuras o pequeñas, se prueban versiones con contraste mejorado y ampliadas; estos ajustes no reducen los requisitos de coincidencia del número, del rostro ni de similitud. Una lectura dudosa, rostro ambiguo, diferencia, error o falta de dependencia deja el caso en `pending_review`; el administrador puede aprobarlo o rechazarlo con motivo. El umbral no garantiza ausencia de falsos positivos o falsos negativos: antes de usarlo en producción debe evaluarse con carnets y fotografías representativos, con consentimiento y revisión de privacidad. Tesseract usa licencia Apache 2.0; los modelos YuNet y SFace incluyen sus licencias en OpenCV Zoo (MIT y Apache 2.0, respectivamente). No hay tarifas por API, aunque la instalación y el mantenimiento local sí requieren recursos del servidor.
