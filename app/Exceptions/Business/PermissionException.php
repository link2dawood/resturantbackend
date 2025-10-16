<?php

namespace App\Exceptions\Business;

use App\Enums\UserRole;
use Exception;

class PermissionException extends Exception
{
    public static function insufficientRole(UserRole $userRole, UserRole $requiredRole): self
    {
        return new self(
            "Insufficient permissions. User role '{$userRole->value}' does not meet required role '{$requiredRole->value}'.",
            403
        );
    }

    public static function cannotManageRole(UserRole $userRole, UserRole $targetRole): self
    {
        return new self(
            "User with role '{$userRole->value}' cannot manage users with role '{$targetRole->value}'.",
            403
        );
    }

    public static function cannotAssignToStore(int $userId, int $storeId): self
    {
        return new self(
            "User {$userId} cannot be assigned to store {$storeId}. Check store ownership and user role.",
            403
        );
    }

    public static function accountSuspended(int $userId, ?string $reason = null): self
    {
        $message = "User account {$userId} has been suspended";
        if ($reason) {
            $message .= ": {$reason}";
        }

        return new self($message, 403);
    }

    public static function sessionExpired(): self
    {
        return new self('Your session has expired. Please log in again.', 401);
    }

    public static function maxLoginAttemptsExceeded(string $email, int $remainingMinutes): self
    {
        return new self(
            "Too many login attempts for {$email}. Please try again in {$remainingMinutes} minutes.",
            429
        );
    }
}
