<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OwnerCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $owner;

    public string $temporaryPassword;

    public User $createdBy;

    /**
     * Create a new event instance.
     */
    public function __construct(User $owner, string $temporaryPassword, User $createdBy)
    {
        $this->owner = $owner;
        $this->temporaryPassword = $temporaryPassword;
        $this->createdBy = $createdBy;
    }
}
