<?php

namespace App\Services\Recording;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * UUID Helper Service for Recording Services
 * 
 * Provides standardized UUID handling across all recording services
 * Ensures consistency in UUID validation, conversion, and formatting
 */
class UuidHelperService
{
    /**
     * UUID validation rules
     */
    private const UUID_VALIDATION_RULES = [
        'required' => 'required|uuid',
        'nullable' => 'nullable|uuid',
        'array' => 'array',
        'array.*' => 'uuid',
    ];

    /**
     * Validate if a value is a valid UUID
     */
    public function isValidUuid(?string $value): bool
    {
        if (is_null($value)) {
            return false;
        }

        return Str::isUuid($value);
    }

    /**
     * Validate multiple UUID values
     */
    public function validateUuids(array $data, array $rules = []): array
    {
        $validationRules = array_merge(self::UUID_VALIDATION_RULES, $rules);

        $validator = Validator::make($data, $validationRules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Generate a new UUID
     */
    public function generateUuid(): string
    {
        return Str::uuid()->toString();
    }

    /**
     * Ensure a value is a valid UUID, generate if needed
     */
    public function ensureUuid(?string $value): string
    {
        if ($this->isValidUuid($value)) {
            return $value;
        }

        return $this->generateUuid();
    }

    /**
     * Convert array of values to UUIDs
     */
    public function convertToUuids(array $values): array
    {
        return array_map(function ($value) {
            return $this->ensureUuid($value);
        }, $values);
    }

    /**
     * Filter valid UUIDs from array
     */
    public function filterValidUuids(array $values): array
    {
        return array_filter($values, function ($value) {
            return $this->isValidUuid($value);
        });
    }

    /**
     * Validate UUID foreign key relationships
     */
    public function validateUuidForeignKey(string $uuid, string $table, string $column = 'id'): bool
    {
        if (!$this->isValidUuid($uuid)) {
            return false;
        }

        try {
            $exists = \DB::table($table)
                ->where($column, $uuid)
                ->exists();

            return $exists;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Validate multiple UUID foreign key relationships
     */
    public function validateUuidForeignKeys(array $uuids, string $table, string $column = 'id'): array
    {
        $validUuids = $this->filterValidUuids($uuids);

        if (empty($validUuids)) {
            return [];
        }

        try {
            $existingUuids = \DB::table($table)
                ->whereIn($column, $validUuids)
                ->pluck($column)
                ->toArray();

            return $existingUuids;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Format UUID for database storage
     */
    public function formatUuidForDatabase(?string $uuid): ?string
    {
        if (is_null($uuid)) {
            return null;
        }

        // Remove any formatting and ensure lowercase
        $cleanUuid = strtolower(preg_replace('/[^a-f0-9-]/', '', $uuid));

        // Validate the cleaned UUID
        if (!$this->isValidUuid($cleanUuid)) {
            return null;
        }

        return $cleanUuid;
    }

    /**
     * Format UUID for display
     */
    public function formatUuidForDisplay(?string $uuid): ?string
    {
        if (is_null($uuid)) {
            return null;
        }

        $formattedUuid = $this->formatUuidForDatabase($uuid);

        if (is_null($formattedUuid)) {
            return null;
        }

        // Return in standard UUID format (8-4-4-4-12)
        return vsprintf('%s-%s-%s-%s-%s', str_split($formattedUuid, 4));
    }

    /**
     * Create UUID mapping for legacy ID conversion
     */
    public function createUuidMapping(int $oldId, string $newUuid, string $tableName): bool
    {
        try {
            \DB::table('uuid_mappings')->insertOrIgnore([
                'old_id' => $oldId,
                'new_uuid' => $newUuid,
                'table_name' => $tableName,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get UUID from legacy ID mapping
     */
    public function getUuidFromLegacyId(int $oldId, string $tableName): ?string
    {
        try {
            $mapping = \DB::table('uuid_mappings')
                ->where('old_id', $oldId)
                ->where('table_name', $tableName)
                ->first();

            return $mapping?->new_uuid;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get legacy ID from UUID mapping
     */
    public function getLegacyIdFromUuid(string $uuid, string $tableName): ?int
    {
        try {
            $mapping = \DB::table('uuid_mappings')
                ->where('new_uuid', $uuid)
                ->where('table_name', $tableName)
                ->first();

            return $mapping?->old_id;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Validate recording data UUIDs
     */
    public function validateRecordingDataUuids(array $data): array
    {
        $uuidFields = [
            'livestock_id',
            'farm_id',
            'coop_id',
            'company_id',
            'user_id',
            'created_by',
            'updated_by',
        ];

        $validationRules = [];
        foreach ($uuidFields as $field) {
            if (isset($data[$field])) {
                $validationRules[$field] = 'nullable|uuid';
            }
        }

        return $this->validateUuids($data, $validationRules);
    }

    /**
     * Clean and validate UUID array for batch operations
     */
    public function cleanUuidArray(array $uuids): array
    {
        $cleanedUuids = [];

        foreach ($uuids as $uuid) {
            $cleanedUuid = $this->formatUuidForDatabase($uuid);
            if ($cleanedUuid) {
                $cleanedUuids[] = $cleanedUuid;
            }
        }

        return array_unique($cleanedUuids);
    }

    /**
     * Create UUID validation rule for specific field
     */
    public function createUuidValidationRule(string $field, bool $required = true): array
    {
        $rule = $required ? 'required|uuid' : 'nullable|uuid';

        return [$field => $rule];
    }

    /**
     * Validate UUID format without database check
     */
    public function validateUuidFormat(string $uuid): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid) === 1;
    }

    /**
     * Get UUID validation error message
     */
    public function getUuidValidationErrorMessage(string $field): string
    {
        return "The {$field} must be a valid UUID format.";
    }

    /**
     * Log UUID validation errors for debugging
     */
    public function logUuidValidationError(string $field, $value, string $context = ''): void
    {
        \Log::warning('UUID validation failed', [
            'field' => $field,
            'value' => $value,
            'context' => $context,
            'timestamp' => now(),
        ]);
    }
}
