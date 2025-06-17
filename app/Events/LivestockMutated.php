<?php

namespace App\Events;

use App\Models\Mutation;
use App\Models\MutationItem;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LivestockMutated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $mutation;
    public $mutationItem;

    /**
     * Create a new event instance.
     */
    public function __construct(Mutation $mutation, MutationItem $mutationItem)
    {
        $this->mutation = $mutation;
        $this->mutationItem = $mutationItem;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('livestock-mutation.' . $this->mutation->id),
        ];
    }

    /**
     * Get the broadcast event name.
     */
    public function broadcastAs(): string
    {
        return 'livestock.mutated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'mutation_id' => $this->mutation->id,
            'source_livestock' => $this->mutation->fromLivestock->name,
            'destination_livestock' => $this->mutation->toLivestock->name,
            'quantity' => $this->mutationItem->quantity,
            'weight' => $this->mutationItem->weight,
            'batch_info' => $this->mutationItem->batch_metadata,
            'date' => $this->mutation->date,
        ];
    }
}
