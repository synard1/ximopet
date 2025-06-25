<?php

namespace App\Services\Recording;

/**
 * Interface for Recording Payload Components
 * 
 * This interface defines the contract for all components that can contribute
 * data to a recording payload. Each component should implement this interface
 * to ensure consistent data structure and metadata handling.
 */
interface PayloadComponentInterface
{
    /**
     * Get the unique name for this component
     * 
     * @return string The component name (e.g., 'manual_feed_usage', 'manual_depletion')
     */
    public function getComponentName(): string;

    /**
     * Get the component's data structure
     * 
     * @return array The component data including source, method, items, etc.
     */
    public function getComponentData(): array;

    /**
     * Get the component's metadata
     * 
     * @return array Metadata for traceability and audit
     */
    public function getComponentMetadata(): array;

    /**
     * Validate the component's data
     * 
     * @return bool Whether the component data is valid
     */
    public function validateComponentData(): bool;

    /**
     * Get validation errors
     * 
     * @return array Array of validation error messages
     */
    public function getValidationErrors(): array;

    /**
     * Check if component has data to contribute
     * 
     * @return bool Whether the component has data
     */
    public function hasData(): bool;

    /**
     * Get the priority for this component (lower number = higher priority)
     * 
     * @return int Priority value
     */
    public function getPriority(): int;
}
