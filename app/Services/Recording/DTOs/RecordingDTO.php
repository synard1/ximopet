<?php

namespace App\Services\Recording\DTOs;

/**
 * A Data Transfer Object for passing recording data from the component to the service layer.
 *
 * Using a DTO ensures a clear and stable contract for data, preventing issues
 * with unstructured arrays and improving code readability and robustness.
 *
 * @version 1.0
 * @since 2025-07-09
 */
class RecordingDTO
{
    public string $livestockId;
    public string $date;
    public ?int $mortality;
    public ?int $culling;
    public ?float $weightToday;
    public ?int $salesQuantity;
    public ?float $salesPrice;
    public ?float $totalSales;
    public array $itemQuantities;
    public array $supplyQuantities;

    // Add any other properties from the component that are needed for saving
    public array $livestockConfig;
    public bool $isManualDepletionEnabled;
    public bool $isManualFeedUsageEnabled;
    public string $recordingMethod;
    public ?string $feedUsageId;
    public ?string $supplyUsageId;

    public function __construct(array $data)
    {
        $this->livestockId = $data['livestockId'];
        $this->date = $data['date'];
        $this->mortality = $data['mortality'] ?? null;
        $this->culling = $data['culling'] ?? null;
        $this->weightToday = $data['weight_today'] ?? null;
        $this->salesQuantity = $data['sales_quantity'] ?? null;
        $this->salesPrice = $data['sales_price'] ?? null;
        $this->totalSales = $data['total_sales'] ?? null;
        $this->itemQuantities = $data['itemQuantities'] ?? [];
        $this->supplyQuantities = $data['supplyQuantities'] ?? [];
        $this->livestockConfig = $data['livestockConfig'] ?? [];
        $this->isManualDepletionEnabled = $data['isManualDepletionEnabled'] ?? false;
        $this->isManualFeedUsageEnabled = $data['isManualFeedUsageEnabled'] ?? false;
        $this->recordingMethod = $data['recordingMethod'] ?? 'total';
        $this->feedUsageId = $data['feedUsageId'] ?? null;
        $this->supplyUsageId = $data['supplyUsageId'] ?? null;
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
