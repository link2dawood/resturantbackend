<?php

namespace App\Events;

use App\Models\User;
use App\Models\Store;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class ManagerAssignedToStores
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $manager;
    public Collection $stores;
    public User $assignedBy;
    public bool $isNewManager;
    public ?Collection $previousStores;

    /**
     * Create a new event instance.
     *
     * @param User $manager The manager being assigned
     * @param Collection $stores The stores being assigned
     * @param User $assignedBy The user making the assignment
     * @param bool $isNewManager Whether this is a new manager creation
     * @param Collection|null $previousStores Previously assigned stores (for updates)
     */
    public function __construct(
        User $manager, 
        Collection $stores, 
        User $assignedBy, 
        bool $isNewManager = false,
        ?Collection $previousStores = null
    ) {
        $this->manager = $manager;
        $this->stores = $stores;
        $this->assignedBy = $assignedBy;
        $this->isNewManager = $isNewManager;
        $this->previousStores = $previousStores;
    }

    /**
     * Get newly assigned stores (not previously assigned)
     */
    public function getNewlyAssignedStores(): Collection
    {
        if (!$this->previousStores) {
            return $this->stores;
        }

        $previousStoreIds = $this->previousStores->pluck('id')->toArray();
        return $this->stores->reject(function ($store) use ($previousStoreIds) {
            return in_array($store->id, $previousStoreIds);
        });
    }

    /**
     * Get removed stores (previously assigned but no longer)
     */
    public function getRemovedStores(): Collection
    {
        if (!$this->previousStores) {
            return collect();
        }

        $currentStoreIds = $this->stores->pluck('id')->toArray();
        return $this->previousStores->reject(function ($store) use ($currentStoreIds) {
            return in_array($store->id, $currentStoreIds);
        });
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('manager-assignments'),
        ];
    }
}