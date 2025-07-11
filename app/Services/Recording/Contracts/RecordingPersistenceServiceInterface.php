<?php

declare(strict_types=1);

namespace App\Services\Recording\Contracts;

use App\Services\Recording\DTOs\RecordingDTO;
use App\Services\Recording\DTOs\ServiceResult;

/**
 * Interface for services that handle persisting recording-related data.
 * 
 * This contract defines the API for saving recording data, ensuring that
 * the component can delegate the persistence logic without being coupled
 * to a specific implementation.
 *
 * @version 1.0
 * @since 2025-07-09
 */
interface RecordingPersistenceServiceInterface
{
    /**
     * Save the recording data.
     *
     * @param RecordingDTO $recordingDTO The data transfer object containing all necessary data.
     * @return ServiceResult A result object indicating success or failure.
     */
    public function saveRecording(RecordingDTO $recordingDTO): ServiceResult;
}
