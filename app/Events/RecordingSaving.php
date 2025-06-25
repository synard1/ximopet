<?php

namespace App\Events;

use App\Services\Recording\ModularPayloadBuilder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a recording is being saved
 * 
 * This event allows external components to add their data
 * to the recording payload before it's saved.
 */
class RecordingSaving
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $livestockId;
    public string $date;
    public ModularPayloadBuilder $payloadBuilder;
    public array $context;

    /**
     * Create a new event instance.
     * 
     * @param string $livestockId
     * @param string $date
     * @param ModularPayloadBuilder $payloadBuilder
     * @param array $context Additional context data
     */
    public function __construct(string $livestockId, string $date, ModularPayloadBuilder $payloadBuilder, array $context = [])
    {
        $this->livestockId = $livestockId;
        $this->date = $date;
        $this->payloadBuilder = $payloadBuilder;
        $this->context = $context;
    }

    /**
     * Get the livestock ID
     * 
     * @return string
     */
    public function getLivestockId(): string
    {
        return $this->livestockId;
    }

    /**
     * Get the recording date
     * 
     * @return string
     */
    public function getDate(): string
    {
        return $this->date;
    }

    /**
     * Get the payload builder
     * 
     * @return ModularPayloadBuilder
     */
    public function getPayloadBuilder(): ModularPayloadBuilder
    {
        return $this->payloadBuilder;
    }

    /**
     * Get context data
     * 
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Get specific context value
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getContextValue(string $key, $default = null)
    {
        return $this->context[$key] ?? $default;
    }

    /**
     * Add context data
     * 
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function addContext(string $key, $value): self
    {
        $this->context[$key] = $value;
        return $this;
    }
}
