<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SupplyMetadataService
{
    /**
     * Build metadata for supply purchase
     */
    public function buildPurchaseMetadata(array $data): array
    {
        return [
            'source_type' => 'purchase',
            'purchase_id' => $data['purchase_id'] ?? null,
            'invoice_number' => $data['invoice_number'] ?? null,
            'supplier_id' => $data['supplier_id'] ?? null,
            'supplier_name' => $data['supplier_name'] ?? null,
            'batch_code' => $data['batch_code'] ?? $this->generateBatchCode('PUR'),
            'purchase_date' => $data['purchase_date'] ?? now()->toDateString(),
            'delivery_date' => $data['delivery_date'] ?? null,
            'payment_terms' => $data['payment_terms'] ?? null,
            'notes' => $data['notes'] ?? null,
            'quality_check' => [
                'passed' => $data['quality_passed'] ?? true,
                'checked_by' => $data['quality_checked_by'] ?? null,
                'checked_at' => $data['quality_checked_at'] ?? now()->toISOString(),
                'notes' => $data['quality_notes'] ?? null
            ],
            'history' => [
                [
                    'date' => now()->toISOString(),
                    'action' => 'purchase_created',
                    'user_id' => \Illuminate\Support\Facades\Auth::id(),
                    'quantity' => $data['quantity'] ?? 0,
                    'notes' => 'Initial purchase record'
                ]
            ],
            'tags' => $data['tags'] ?? [],
            'custom_fields' => $data['custom_fields'] ?? []
        ];
    }

    /**
     * Build metadata for supply mutation (inbound)
     */
    public function buildInboundMutationMetadata(array $data): array
    {
        return [
            'source_type' => 'mutation',
            'mutation_id' => $data['mutation_id'] ?? null,
            'mutation_type' => $data['mutation_type'] ?? 'transfer',
            'from_farm_id' => $data['from_farm_id'] ?? null,
            'from_coop_id' => $data['from_coop_id'] ?? null,
            'from_livestock_id' => $data['from_livestock_id'] ?? null,
            'batch_code' => $data['batch_code'] ?? $this->generateBatchCode('MUT'),
            'original_batch' => $data['original_batch'] ?? null,
            'original_purchase_id' => $data['original_purchase_id'] ?? null,
            'mutation_date' => $data['mutation_date'] ?? now()->toDateString(),
            'reason' => $data['reason'] ?? null,
            'notes' => $data['notes'] ?? null,
            'approval' => [
                'approved_by' => $data['approved_by'] ?? null,
                'approved_at' => $data['approved_at'] ?? null,
                'verification_required' => $data['verification_required'] ?? false,
                'verified_by' => $data['verified_by'] ?? null,
                'verified_at' => $data['verified_at'] ?? null
            ],
            'history' => [
                [
                    'date' => now()->toISOString(),
                    'action' => 'inbound_mutation_created',
                    'user_id' => \Illuminate\Support\Facades\Auth::id(),
                    'quantity' => $data['quantity'] ?? 0,
                    'from_farm' => $data['from_farm_id'] ?? null,
                    'notes' => 'Inbound mutation record'
                ]
            ],
            'tags' => $data['tags'] ?? [],
            'custom_fields' => $data['custom_fields'] ?? []
        ];
    }

    /**
     * Build metadata for supply mutation (outbound)
     */
    public function buildOutboundMutationMetadata(array $data): array
    {
        return [
            'source_type' => 'mutation',
            'mutation_id' => $data['mutation_id'] ?? null,
            'mutation_type' => $data['mutation_type'] ?? 'transfer',
            'to_farm_id' => $data['to_farm_id'] ?? null,
            'to_coop_id' => $data['to_coop_id'] ?? null,
            'to_livestock_id' => $data['to_livestock_id'] ?? null,
            'batch_code' => $data['batch_code'] ?? $this->generateBatchCode('MUT'),
            'mutation_date' => $data['mutation_date'] ?? now()->toDateString(),
            'reason' => $data['reason'] ?? null,
            'notes' => $data['notes'] ?? null,
            'approval' => [
                'approved_by' => $data['approved_by'] ?? null,
                'approved_at' => $data['approved_at'] ?? null,
                'verification_required' => $data['verification_required'] ?? false,
                'verified_by' => $data['verified_by'] ?? null,
                'verified_at' => $data['verified_at'] ?? null
            ],
            'history' => [
                [
                    'date' => now()->toISOString(),
                    'action' => 'outbound_mutation_created',
                    'user_id' => \Illuminate\Support\Facades\Auth::id(),
                    'quantity' => $data['quantity'] ?? 0,
                    'to_farm' => $data['to_farm_id'] ?? null,
                    'notes' => 'Outbound mutation record'
                ]
            ],
            'tags' => $data['tags'] ?? [],
            'custom_fields' => $data['custom_fields'] ?? []
        ];
    }

    /**
     * Add history entry to existing metadata
     */
    public function addHistoryEntry(array $metadata, string $action, array $data = []): array
    {
        $historyEntry = [
            'date' => now()->toISOString(),
            'action' => $action,
            'user_id' => \Illuminate\Support\Facades\Auth::id(),
            'quantity' => $data['quantity'] ?? null,
            'notes' => $data['notes'] ?? null
        ];

        // Add additional fields based on action
        switch ($action) {
            case 'quantity_used':
                $historyEntry['usage_id'] = $data['usage_id'] ?? null;
                $historyEntry['livestock_id'] = $data['livestock_id'] ?? null;
                break;
            case 'quantity_reserved':
                $historyEntry['reservation_id'] = $data['reservation_id'] ?? null;
                $historyEntry['reserved_for'] = $data['reserved_for'] ?? null;
                break;
            case 'quality_check':
                $historyEntry['quality_result'] = $data['quality_result'] ?? null;
                $historyEntry['checked_by'] = $data['checked_by'] ?? null;
                break;
        }

        $metadata['history'][] = $historyEntry;

        // Keep only last 50 history entries to prevent metadata bloat
        if (count($metadata['history']) > 50) {
            $metadata['history'] = array_slice($metadata['history'], -50);
        }

        return $metadata;
    }

    /**
     * Update approval information in metadata
     */
    public function updateApprovalMetadata(array $metadata, array $approvalData): array
    {
        $metadata['approval'] = array_merge($metadata['approval'] ?? [], $approvalData);

        return $this->addHistoryEntry($metadata, 'approval_updated', [
            'notes' => 'Approval information updated'
        ]);
    }

    /**
     * Update quality check information in metadata
     */
    public function updateQualityMetadata(array $metadata, array $qualityData): array
    {
        $metadata['quality_check'] = array_merge($metadata['quality_check'] ?? [], $qualityData);

        return $this->addHistoryEntry($metadata, 'quality_check_updated', [
            'notes' => 'Quality check information updated'
        ]);
    }

    /**
     * Add tags to metadata
     */
    public function addTags(array $metadata, array $tags): array
    {
        $existingTags = $metadata['tags'] ?? [];
        $metadata['tags'] = array_unique(array_merge($existingTags, $tags));

        return $this->addHistoryEntry($metadata, 'tags_added', [
            'notes' => 'Tags added: ' . implode(', ', $tags)
        ]);
    }

    /**
     * Add custom fields to metadata
     */
    public function addCustomFields(array $metadata, array $customFields): array
    {
        $metadata['custom_fields'] = array_merge($metadata['custom_fields'] ?? [], $customFields);

        return $this->addHistoryEntry($metadata, 'custom_fields_added', [
            'notes' => 'Custom fields added'
        ]);
    }

    /**
     * Generate unique batch code
     */
    public function generateBatchCode(string $prefix): string
    {
        return $prefix . '-' . Str::upper(Str::random(8)) . '-' . now()->format('Ymd');
    }

    /**
     * Validate metadata structure
     */
    public function validateMetadata(array $metadata): array
    {
        $errors = [];
        $warnings = [];

        // Required fields validation
        if (empty($metadata['source_type'])) {
            $errors[] = 'source_type is required';
        }

        if (empty($metadata['history'])) {
            $errors[] = 'history is required';
        }

        // Source type specific validation
        switch ($metadata['source_type']) {
            case 'purchase':
                if (empty($metadata['purchase_id'])) {
                    $warnings[] = 'purchase_id is recommended for purchase records';
                }
                break;
            case 'mutation':
                if (empty($metadata['mutation_id'])) {
                    $warnings[] = 'mutation_id is recommended for mutation records';
                }
                break;
        }

        // History validation
        if (!empty($metadata['history'])) {
            foreach ($metadata['history'] as $index => $entry) {
                if (empty($entry['action'])) {
                    $errors[] = "History entry {$index} missing action";
                }
                if (empty($entry['date'])) {
                    $errors[] = "History entry {$index} missing date";
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * Get metadata summary for display
     */
    public function getMetadataSummary(array $metadata): array
    {
        return [
            'source_type' => $metadata['source_type'] ?? 'unknown',
            'batch_code' => $metadata['batch_code'] ?? 'N/A',
            'created_date' => $metadata['history'][0]['date'] ?? 'N/A',
            'last_activity' => end($metadata['history'])['date'] ?? 'N/A',
            'total_activities' => count($metadata['history'] ?? []),
            'tags' => $metadata['tags'] ?? [],
            'approval_status' => $this->getApprovalStatus($metadata),
            'quality_status' => $this->getQualityStatus($metadata)
        ];
    }

    /**
     * Get approval status from metadata
     */
    private function getApprovalStatus(array $metadata): string
    {
        $approval = $metadata['approval'] ?? [];

        if (!empty($approval['approved_by'])) {
            return 'approved';
        }

        if (!empty($approval['verification_required'])) {
            return 'pending_verification';
        }

        return 'pending_approval';
    }

    /**
     * Get quality status from metadata
     */
    private function getQualityStatus(array $metadata): string
    {
        $quality = $metadata['quality_check'] ?? [];

        if (isset($quality['passed'])) {
            return $quality['passed'] ? 'passed' : 'failed';
        }

        return 'not_checked';
    }
}
