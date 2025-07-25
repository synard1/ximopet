<?php

declare(strict_types=1);

namespace App\Services\Recording\Exceptions;

/**
 * RecordingCalculationException
 * 
 * Exception thrown when recording calculations fail.
 * Contains calculation-specific error information.
 */
class RecordingCalculationException extends RecordingException
{
    protected string $errorCode = 'RECORDING_CALCULATION_ERROR';
    protected ?string $calculationType = null;
    protected array $inputData = [];

    public function __construct(
        string $message = 'Recording calculation failed',
        ?string $calculationType = null,
        array $inputData = [],
        array $context = []
    ) {
        parent::__construct($message, 500, null, $context);
        $this->calculationType = $calculationType;
        $this->inputData = $inputData;
    }

    /**
     * Get calculation type
     */
    public function getCalculationType(): ?string
    {
        return $this->calculationType;
    }

    /**
     * Get input data
     */
    public function getInputData(): array
    {
        return $this->inputData;
    }

    /**
     * Get calculation-specific error details
     */
    public function getCalculationErrorDetails(): array
    {
        return array_merge($this->getErrorDetails(), [
            'calculation_type' => $this->calculationType,
            'input_data' => $this->inputData
        ]);
    }
}
