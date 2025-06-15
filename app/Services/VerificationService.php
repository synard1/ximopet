<?php

namespace App\Services;

use App\Models\VerificationRule;
use App\Models\ModelVerification;
use App\Models\VerificationLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class VerificationService
{
    /**
     * Verify a model instance
     *
     * @param Model $model
     * @param User $verifier
     * @param array $documents
     * @param string|null $notes
     * @return ModelVerification
     */
    public function verify(Model $model, User $verifier, array $documents = [], ?string $notes = null): ModelVerification
    {
        return DB::transaction(function () use ($model, $verifier, $documents, $notes) {
            // Create or update verification record
            $verification = ModelVerification::updateOrCreate(
                [
                    'model_type' => get_class($model),
                    'model_id' => $model->id,
                ],
                [
                    'status' => 'verified',
                    'verified_by' => $verifier->id,
                    'verified_at' => now(),
                    'verification_notes' => $notes,
                    'verified_data' => $model->toArray(),
                    'verified_documents' => $documents,
                    'is_locked' => true
                ]
            );

            // Log verification
            $this->logVerification($model, $verifier, 'verify', $notes, [
                'documents' => $documents
            ]);

            return $verification;
        });
    }

    /**
     * Reject verification
     *
     * @param Model $model
     * @param User $verifier
     * @param string $notes
     * @return ModelVerification
     */
    public function reject(Model $model, User $verifier, string $notes): ModelVerification
    {
        return DB::transaction(function () use ($model, $verifier, $notes) {
            $verification = ModelVerification::updateOrCreate(
                [
                    'model_type' => get_class($model),
                    'model_id' => $model->id,
                ],
                [
                    'status' => 'rejected',
                    'verified_by' => $verifier->id,
                    'verified_at' => now(),
                    'verification_notes' => $notes,
                    'is_locked' => false
                ]
            );

            $this->logVerification($model, $verifier, 'reject', $notes);

            return $verification;
        });
    }

    /**
     * Unlock a verified model
     *
     * @param Model $model
     * @param User $user
     * @param string $notes
     * @return ModelVerification
     */
    public function unlock(Model $model, User $user, string $notes): ModelVerification
    {
        return DB::transaction(function () use ($model, $user, $notes) {
            $verification = ModelVerification::where('model_type', get_class($model))
                ->where('model_id', $model->id)
                ->firstOrFail();

            $verification->update([
                'is_locked' => false,
                'status' => 'unlocked'
            ]);

            $this->logVerification($model, $user, 'unlock', $notes);

            return $verification;
        });
    }

    /**
     * Check if model can be modified
     *
     * @param Model $model
     * @return bool
     */
    public function canModify(Model $model): bool
    {
        $verification = ModelVerification::where('model_type', get_class($model))
            ->where('model_id', $model->id)
            ->first();

        return !$verification || !$verification->is_locked;
    }

    /**
     * Get verification status
     *
     * @param Model $model
     * @return string|null
     */
    public function getStatus(Model $model): ?string
    {
        $verification = ModelVerification::where('model_type', get_class($model))
            ->where('model_id', $model->id)
            ->first();

        return $verification ? $verification->status : null;
    }

    /**
     * Log verification action
     *
     * @param Model $model
     * @param User $user
     * @param string $action
     * @param string|null $notes
     * @param array $context
     * @return VerificationLog
     */
    private function logVerification(
        Model $model,
        User $user,
        string $action,
        ?string $notes = null,
        array $context = []
    ): VerificationLog {
        return VerificationLog::create([
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'user_id' => $user->id,
            'action' => $action,
            'notes' => $notes,
            'context' => $context
        ]);
    }
}
