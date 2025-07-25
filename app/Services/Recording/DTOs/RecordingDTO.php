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
    public ?float $salesWeight;
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
    public bool $withHistoryFeedUsage;
    public bool $withHistoryFeedUsageDetail;
    public bool $withHistorySupplyUsage;
    public bool $withHistorySupplyUsageDetail;
    public bool $withHistoryDepletion;
    public bool $withHistoryDepletionDetail;
    public bool $withHistoryMutation;
    public bool $withHistoryMutationDetail;

    // Validation/locking for each section
    public array $validationStatus = [
        'sales' => [
            'is_validated' => false,
            'validated_by' => null,
            'validated_at' => null,
            'locked' => false,
            'locked_by' => null,
            'locked_at' => null,
        ],
        'depletion' => [
            'is_validated' => false,
            'validated_by' => null,
            'validated_at' => null,
            'locked' => false,
            'locked_by' => null,
            'locked_at' => null,
        ],
        'feed_usage' => [
            'is_validated' => false,
            'validated_by' => null,
            'validated_at' => null,
            'locked' => false,
            'locked_by' => null,
            'locked_at' => null,
        ],
        'supply_usage' => [
            'is_validated' => false,
            'validated_by' => null,
            'validated_at' => null,
            'locked' => false,
            'locked_by' => null,
            'locked_at' => null,
        ],
    ];
    // Snapshot data akhir yang sudah divalidasi per section
    public array $validatedData = [
        'sales' => null,
        'depletion' => null,
        'feed_usage' => null,
        'supply_usage' => null,
    ];

    public function __construct(array $data)
    {
        $this->livestockId = $data['livestockId'];
        $this->date = $data['date'];
        $this->mortality = $data['mortality'] ?? null;
        $this->culling = $data['culling'] ?? null;
        $this->weightToday = $data['weight_today'] ?? null;
        $this->salesQuantity = $data['sales_quantity'] ?? null;
        $this->salesWeight = $data['sales_weight'] ?? null;
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
        $this->withHistoryFeedUsage = $data['withHistoryFeedUsage'] ?? false;
        $this->withHistoryFeedUsageDetail = $data['withHistoryFeedUsageDetail'] ?? false;
        $this->withHistorySupplyUsage = $data['withHistorySupplyUsage'] ?? false;
        $this->withHistorySupplyUsageDetail = $data['withHistorySupplyUsageDetail'] ?? false;
        $this->withHistoryDepletion = $data['withHistoryDepletion'] ?? false;
        $this->withHistoryDepletionDetail = $data['withHistoryDepletionDetail'] ?? false;
        $this->withHistoryMutation = $data['withHistoryMutation'] ?? false;
        $this->withHistoryMutationDetail = $data['withHistoryMutationDetail'] ?? false;
        $this->validationStatus = $data['validationStatus'] ?? $this->validationStatus;
        $this->validatedData = $data['validatedData'] ?? $this->validatedData;
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
