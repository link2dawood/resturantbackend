<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class ManagerCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $manager;

    public Collection $assignedStores;

    public User $createdBy;

    public string $temporaryPassword;

    /**
     * Create a new event instance.
     */
    public function __construct(User $manager, Collection $assignedStores, User $createdBy, string $temporaryPassword)
    {
        $this->manager = $manager;
        $this->assignedStores = $assignedStores;
        $this->createdBy = $createdBy;
        $this->temporaryPassword = $temporaryPassword;
    }
}
