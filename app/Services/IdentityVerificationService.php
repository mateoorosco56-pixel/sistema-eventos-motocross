<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class IdentityVerificationService
{
    /**
     * Academic prototype validation for a pilot identity check.
     * It is intentionally local-only and does not claim bank-level biometric security.
     */
    public function verifyPilotIdentity(?string $documentPath, ?string $selfiePath): array
    {
        $documentExists = $documentPath && Storage::disk('local')->exists($documentPath);
        $selfieExists = $selfiePath && Storage::disk('local')->exists($selfiePath);

        if (! $documentExists || ! $selfieExists) {
            return [
                'status' => 'rechazada',
                'result' => 'Faltan la fotografía del documento o la selfie.',
                'verified_at' => null,
            ];
        }

        $documentFullPath = Storage::disk('local')->path($documentPath);
        $selfieFullPath = Storage::disk('local')->path($selfiePath);

        $documentSize = @filesize($documentFullPath);
        $selfieSize = @filesize($selfieFullPath);

        if ($documentSize === false || $selfieSize === false || $documentSize < 1024 || $selfieSize < 1024) {
            return [
                'status' => 'rechazada',
                'result' => 'Las imágenes no cumplen el tamaño mínimo requerido para la verificación.',
                'verified_at' => null,
            ];
        }

        $documentInfo = @getimagesize($documentFullPath);
        $selfieInfo = @getimagesize($selfieFullPath);

        if (! is_array($documentInfo) || ! is_array($selfieInfo)) {
            return [
                'status' => 'rechazada',
                'result' => 'Las imágenes no son válidas o no se pudieron procesar.',
                'verified_at' => null,
            ];
        }

        return [
            'status' => 'verificada',
            'result' => 'Se validó la presencia de documento y selfie con una comprobación local de prototipo académico.',
            'verified_at' => now(),
        ];
    }
}
