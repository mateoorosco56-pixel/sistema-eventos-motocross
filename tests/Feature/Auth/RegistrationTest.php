<?php

use App\Models\User;
use App\Services\VerificacionIdentidadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function createRegistrationPngBytes(): string
{
    $width = 50;
    $height = 50;
    $raw = '';

    for ($y = 0; $y < $height; $y++) {
        $raw .= "\x00";

        for ($x = 0; $x < $width; $x++) {
            $raw .= chr(($x * 5) % 256)
                . chr(($y * 7 + $x * 3) % 256)
                . chr(($x * 11 + $y * 13) % 256);
        }
    }

    $compressed = gzcompress($raw, 9);
    $makeChunk = function (string $type, string $data): string {
        return pack('N', strlen($data))
            . $type
            . $data
            . pack('N', crc32($type . $data));
    };

    $ihdr = $makeChunk('IHDR', pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0));
    $idat = $makeChunk('IDAT', $compressed);
    $iend = $makeChunk('IEND', '');

    return "\x89PNG\r\n\x1a\n" . $ihdr . $idat . $iend;
}

test('registration screen can be rendered', function () {
    /** @var \Tests\TestCase $this */
    $response = $this->get('/register');

    $response->assertStatus(200)
        ->assertSee('Nombre')
        ->assertSee('Apellido')
        ->assertSee('Número de carnet de identidad')
        ->assertSee('Fotografía del anverso (frente) del carnet')
        ->assertSee('Fotografía del reverso del carnet')
        ->assertSee('No se realiza una prueba de vida.')
        ->assertSeeInOrder(['name="first_name"', 'name="last_name"'])
        ->assertDontSee('Tipo de cuenta')
        ->assertDontSee('value="usuario"');
});

test('new users can register', function () {
    /** @var \Tests\TestCase $this */
    Storage::fake('local');
    $pngContent = createRegistrationPngBytes();
    $this->mock(VerificacionIdentidadService::class)
        ->shouldReceive('verifyPilotIdentity')
        ->once()
        ->andReturn([
            'status' => 'verified',
            'method' => 'automatic_ocr_face',
            'result' => 'OCR y comparación facial completados.',
            'verified_at' => now(),
            'reviewed_at' => now(),
        ]);

    $response = $this->post('/register', [
        'first_name' => 'Test',
        'last_name' => 'User',
        'identity_document_number' => '1234567',
        'email' => 'test@example.com',
        'role' => 'usuario',
        'password' => 'password',
        'password_confirmation' => 'password',
        'identity_processing_consent' => '1',
        'document_photo' => UploadedFile::fake()->createWithContent('document.png', $pngContent, 'image/png'),
        'document_back_photo' => UploadedFile::fake()->createWithContent('document-back.png', $pngContent, 'image/png'),
        'selfie_photo' => UploadedFile::fake()->createWithContent('selfie.png', $pngContent, 'image/png'),
    ]);

    $this->assertAuthenticated();
    $user = User::where('email', 'test@example.com')->first();
    $this->assertAuthenticatedAs($user);
    expect($user->role)->toBe('piloto')
        ->and($user->name)->toBe('Test User')
        ->and($user->verification_status)->toBe('verified')
        ->and($user->verification_method)->toBe('automatic_ocr_face')
        ->and($user->identity_document_number)->toBe('1234567')
        ->and($user->identity_processing_consent_at)->not->toBeNull()
        ->and(Storage::disk('local')->exists($user->document_path))->toBeTrue()
        ->and(Storage::disk('local')->exists($user->document_back_path))->toBeTrue()
        ->and(Storage::disk('local')->exists($user->selfie_path))->toBeTrue();
    $response->assertRedirect(route('dashboard', absolute: false));
    $this->get('/dashboard')->assertOk()->assertSee('Panel del piloto');
});

test('ambiguous identity data stays pending manual review', function () {
    /** @var \Tests\TestCase $this */
    Storage::fake('local');
    $pngContent = createRegistrationPngBytes();
    $this->mock(VerificacionIdentidadService::class)
        ->shouldReceive('verifyPilotIdentity')
        ->once()
        ->andReturn([
            'status' => 'pending_review',
            'method' => 'automatic_review',
            'result' => 'Los datos OCR no coinciden.',
            'verified_at' => null,
            'reviewed_at' => now(),
        ]);

    $this->post('/register', [
        'first_name' => 'Test',
        'last_name' => 'User',
        'identity_document_number' => '12-345 67',
        'email' => 'review@example.com',
        'role' => 'administrador',
        'password' => 'password',
        'password_confirmation' => 'password',
        'identity_processing_consent' => '1',
        'document_photo' => UploadedFile::fake()->createWithContent('document.png', $pngContent, 'image/png'),
        'document_back_photo' => UploadedFile::fake()->createWithContent('document-back.png', $pngContent, 'image/png'),
        'selfie_photo' => UploadedFile::fake()->createWithContent('selfie.png', $pngContent, 'image/png'),
    ])->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'review@example.com')->firstOrFail();
    expect($user->role)->toBe('piloto')
        ->and($user->identity_document_number)->toBe('1234567')
        ->and($user->verification_status)->toBe('pending_review')
        ->and($user->verified_at)->toBeNull();
});

test('new pilot accounts default to pending identity review', function () {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create(['role' => 'piloto']);

    expect($user->verification_status)->toBe('pending_review');
});

test('registration requires consent for local identity processing', function () {
    /** @var \Tests\TestCase $this */
    $pngContent = createRegistrationPngBytes();

    $this->post('/register', [
        'first_name' => 'Test',
        'last_name' => 'User',
        'identity_document_number' => '1234567',
        'email' => 'consent@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'document_photo' => UploadedFile::fake()->createWithContent('document.png', $pngContent, 'image/png'),
        'document_back_photo' => UploadedFile::fake()->createWithContent('document-back.png', $pngContent, 'image/png'),
        'selfie_photo' => UploadedFile::fake()->createWithContent('selfie.png', $pngContent, 'image/png'),
    ])
        ->assertSessionHasErrors('identity_processing_consent');

    $this->assertDatabaseMissing('users', ['email' => 'consent@example.com']);
});

test('registration requires both sides of the identity document', function () {
    /** @var \Tests\TestCase $this */
    $pngContent = createRegistrationPngBytes();

    $this->post('/register', [
        'first_name' => 'Test',
        'last_name' => 'User',
        'identity_document_number' => '1234567',
        'email' => 'missing-back@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'identity_processing_consent' => '1',
        'document_photo' => UploadedFile::fake()->createWithContent('document.png', $pngContent, 'image/png'),
        'selfie_photo' => UploadedFile::fake()->createWithContent('selfie.png', $pngContent, 'image/png'),
    ])->assertSessionHasErrors('document_back_photo');

    $this->assertDatabaseMissing('users', ['email' => 'missing-back@example.com']);
});
