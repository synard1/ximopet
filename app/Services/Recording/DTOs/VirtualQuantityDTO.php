<?php

namespace App\Services\Recording\DTOs;

class VirtualQuantityDTO
{
    public function __construct(
        public readonly string $livestockId,
    public readonly string $date,
        public readonly int $quantity,
        public readonly float $weight,
        public readonly string $calculationMethod,
        public readonly array $metadata = [],
        public readonly array $realStockReference = [],
        public readonly ?string $status = 'draft'
    ) {}

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            livestockId: $data['livestock_id'] ?? '',
            date: $data['date'] ?? '',
            quantity: (int) ($data['quantity'] ?? 0),
            weight: (float) ($data['weight'] ?? 0),
            calculationMethod: $data['calculation_method'] ?? 'projection',
            metadata: $data['metadata'] ?? [],
            realStockReference: $data['real_stock_reference'] ?? [],
            status: $data['status'] ?? 'draft'
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'livestock_id' => $this->livestockId,
            'date' => $this->date,
            'quantity' => $this->quantity,
            'weight' => $this->weight,
            'calculation_method' => $this->calculationMethod,
            'metadata' => $this->metadata,
            'real_stock_reference' => $this->realStockReference,
            'status' => $this->status
        ];
    }

    /**
     * Get calculation metadata
     */
    public function getCalculationMetadata(): array
    {
        return $this->metadata['calculation_data'] ?? [];
    }

    /**
     * Get real stock summary
     */
    public function getRealStockSummary(): array
    {
        return [
            'total_available' => $this->realStockReference['total_available'] ?? 0,
            'total_initial_quantity' => $this->realStockReference['total_initial_quantity'] ?? 0,
            'total_depletion' => $this->realStockReference['total_depletion'] ?? 0,
            'total_sales' => $this->realStockReference['total_sales'] ?? 0,
            'batches_count' => $this->realStockReference['batches_count'] ?? 0
        ];
    }

    /**
     * Check if virtual quantity is within real stock limits
     */
    public function isWithinStockLimits(): bool
    {
        $realAvailable = $this->realStockReference['total_available'] ?? 0;
        return $this->quantity <= $realAvailable;
    }

    /**
     * Get stock utilization percentage
     */
    public function getStockUtilizationPercentage(): float
    {
        $realAvailable = $this->realStockReference['total_available'] ?? 0;
        if ($realAvailable <= 0) {
            return 0;
        }
        return ($this->quantity / $realAvailable) * 100;
    }

    /**
     * Validate virtual quantity data
     */
    public function validate(): array
    {
        $errors = [];

        if (empty($this->livestockId)) {
            $errors[] = 'Livestock ID is required';
        }

        if (empty($this->date)) {
            $errors[] = 'Date is required';
        }

        if ($this->quantity < 0) {
            $errors[] = 'Quantity cannot be negative';
        }

        if ($this->weight < 0) {
            $errors[] = 'Weight cannot be negative';
        }

        if (!in_array($this->calculationMethod, ['projection', 'estimate', 'forecast'])) {
            $errors[] = 'Invalid calculation method';
        }

        return $errors;
    }

    /**
     * Check if virtual quantity is valid
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }
}
