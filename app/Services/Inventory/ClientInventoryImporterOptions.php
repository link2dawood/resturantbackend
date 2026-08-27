<?php

namespace App\Services\Inventory;

/** Import switches, kept as an object so the command signature stays readable. */
class ClientInventoryImporterOptions
{
    public function __construct(
        public bool $createVendors = true,
        public bool $createCategories = true,
    ) {
    }
}
