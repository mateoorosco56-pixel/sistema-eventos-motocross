<?php

use App\Services\VerificacionIdentidadService;
use Illuminate\Support\Facades\Storage;

it('sends missing private identity images to manual review instead of approving them', function () {
    Storage::fake('local');

    $result = app(VerificacionIdentidadService::class)->verifyPilotIdentity(
        'verification-documents/missing.png',
        'verification-documents/missing-back.png',
        'verification-selfies/missing.png',
        '1234567',
        'Test',
        'Pilot'
    );

    expect($result['status'])->toBe('pending_review')
        ->and($result['method'])->toBe('automatic_review')
        ->and($result['verified_at'])->toBeNull()
        ->and($result['reviewed_at'])->not->toBeNull();
});
