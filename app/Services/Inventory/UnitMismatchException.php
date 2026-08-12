<?php

namespace App\Services\Inventory;

use RuntimeException;

/**
 * Thrown when a quantity's unit has no explicit conversion to an inventory
 * item's base unit. The variance engine never silently coerces units.
 */
class UnitMismatchException extends RuntimeException
{
}
