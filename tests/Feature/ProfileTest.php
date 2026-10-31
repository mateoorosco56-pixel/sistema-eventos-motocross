<?php

use App\Models\User;
use App\Services\VerificacionIdentidadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function createValidPngBytes(): string
{
    $width = 50;
    $height = 50;
    $raw = "";

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

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk();
});

test('pilot profile page shows logout without the collapsed hamburger menu', function () {
    $pilot = User::factory()->create(['role' => 'piloto']);

    $this->actingAs($pilot)
        ->get('/profile')
        ->assertOk()
        ->assertSee('Cerrar sesión')
        ->assertSee('Fotografía del reverso del carnet')
        ->assertSee('action="' . route('logout') . '"', false)
        ->assertDontSee('navbar-toggler', false);
});

test('pilots can submit identity verification documents', function () {
    Storage::fake('local');

    $user = User::factory()->create([
        'role' => 'piloto',
        'verification_status' => 'pending_review',
    ]);

    $pngContent = createValidPngBytes();
    $document = UploadedFile::fake()->createWithContent('document.png', $pngContent, 'image/png');
    $documentBack = UploadedFile::fake()->createWithContent('document-back.png', $pngContent, 'image/png');
    $selfie = UploadedFile::fake()->createWithContent('selfie.png', $pngContent, 'image/png');
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

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->post('/profile/verification', [
            'first_name' => 'Test',
            'last_name' => 'Pilot',
            'identity_document_number' => '1234567',
            'document_photo' => $document,
            'document_back_photo' => $documentBack,
            'selfie_photo' => $selfie,
            'identity_processing_consent' => '1',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    expect($user->verification_status)->toBe('verified')
        ->and($user->verification_method)->toBe('automatic_ocr_face')
        ->and($user->identity_document_number)->toBe('1234567')
        ->and(Storage::disk('local')->exists($user->document_back_path))->toBeTrue()
        ->and($user->verified_at)->not->toBeNull();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('regular users cannot submit pilot identity verification', function () {
    Storage::fake('local');

    $user = User::factory()->create(['role' => 'usuario']);

    $pngContent = createValidPngBytes();
    $document = UploadedFile::fake()->createWithContent('document.png', $pngContent, 'image/png');
    $documentBack = UploadedFile::fake()->createWithContent('document-back.png', $pngContent, 'image/png');
    $selfie = UploadedFile::fake()->createWithContent('selfie.png', $pngContent, 'image/png');

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->post('/profile/verification', [
            'document_photo' => $document,
            'document_back_photo' => $documentBack,
            'selfie_photo' => $selfie,
        ]);

    $response->assertStatus(403);
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrorsIn('userDeletion', 'password')
        ->assertRedirect('/profile');

    $this->assertNotNull($user->fresh());
});
