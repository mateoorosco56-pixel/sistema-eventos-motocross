<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Exception\ProcessStartFailedException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use JsonException;

class VerificacionIdentidadService
{
    /**
     * @return array{status: string, method: string, result: string, verified_at: \Illuminate\Support\Carbon|null, reviewed_at: \Illuminate\Support\Carbon}
     */
    public function verifyPilotIdentity(
        ?string $documentPath,
        ?string $documentBackPath,
        ?string $selfiePath,
        string $identityDocumentNumber,
        string $firstName,
        string $lastName
    ): array {
        if (
            ! $documentPath
            || ! $documentBackPath
            || ! $selfiePath
            || ! Storage::disk('local')->exists($documentPath)
            || ! Storage::disk('local')->exists($documentBackPath)
            || ! Storage::disk('local')->exists($selfiePath)
        ) {
            return $this->pendingReview('Faltan imágenes de ambas caras del carnet o del rostro para la revisión.');
        }

        $process = new Process([
            (string) config('identity_verification.python_binary'),
            base_path('scripts/verify-pilot-identity.py'),
            Storage::disk('local')->path($documentPath),
            Storage::disk('local')->path($documentBackPath),
            Storage::disk('local')->path($selfiePath),
            (string) config('identity_verification.tesseract_binary'),
            (string) config('identity_verification.yunet_model'),
            (string) config('identity_verification.sface_model'),
        ]);
        $process->setInput(json_encode([
            'identity_document_number' => $identityDocumentNumber,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'face_similarity_threshold' => config('identity_verification.face_similarity_threshold'),
            'face_detector_confidence_threshold' => config('identity_verification.face_detector_confidence_threshold'),
        ], JSON_THROW_ON_ERROR));
        $process->setTimeout(60);

        try {
            $process->run();
        } catch (ProcessStartFailedException|ProcessTimedOutException $exception) {
            Log::warning('La verificación automática de identidad no pudo ejecutarse.', [
                'reason' => $exception->getMessage(),
            ]);

            return $this->pendingReview('No se pudo completar la revisión automática. El administrador revisará tu solicitud.');
        }

        if (! $process->isSuccessful()) {
            Log::warning('La verificación automática de identidad terminó con error.', [
                'exit_code' => $process->getExitCode(),
                'error' => trim($process->getErrorOutput()),
            ]);

            return $this->pendingReview('No se pudo completar la revisión automática. El administrador revisará tu solicitud.');
        }

        if (trim($process->getErrorOutput()) !== '') {
            Log::warning('La verificación automática de identidad emitió un aviso.', [
                'error' => trim($process->getErrorOutput()),
            ]);
        }

        try {
            $result = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            Log::warning('La respuesta de la verificación automática de identidad no es válida.', [
                'reason' => $exception->getMessage(),
            ]);

            return $this->pendingReview('No se pudo completar la revisión automática. El administrador revisará tu solicitud.');
        }

        if (! is_array($result) || ! in_array($result['status'] ?? null, ['verified', 'pending_review'], true)) {
            Log::warning('La respuesta de la verificación automática de identidad tiene un estado inesperado.');

            return $this->pendingReview('No se pudo completar la revisión automática. El administrador revisará tu solicitud.');
        }

        if ($result['status'] === 'verified') {
            return [
                'status' => 'verified',
                'method' => 'automatic_ocr_face',
                'result' => (string) ($result['reason'] ?? 'OCR y comparación facial completados.'),
                'verified_at' => now(),
                'reviewed_at' => now(),
            ];
        }

        $reason = trim((string) ($result['reason'] ?? 'No fue posible confirmar la identidad automáticamente.'));

        return $this->pendingReview($reason);
    }

    /**
     * @return array{status: string, method: string, result: string, verified_at: null, reviewed_at: \Illuminate\Support\Carbon}
     */
    private function pendingReview(string $reason): array
    {
        return [
            'status' => 'pending_review',
            'method' => 'automatic_review',
            'result' => $reason,
            'verified_at' => null,
            'reviewed_at' => now(),
        ];
    }
}
