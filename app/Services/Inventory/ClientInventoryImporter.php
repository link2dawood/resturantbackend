<?php

namespace App\Services\Inventory;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Store;
use App\Models\Vendor;
use App\Models\VendorPrice;
use App\Services\Inventory\ItemVendorMapper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;

/**
 * Phase 5 Part 1 Task 15 — one-time import of the client's real order guide.
 *
 * Reads the columns their sheet actually uses:
 *   Inventory ID | Name | Supplier | Category | Pack Size | VendorID # | Cost
 *
 * Everything is planned first and written second, so the dry run (the default)
 * and the real run walk identical code and the preview cannot lie about what a
 * commit would do.
 *
 * Vendors the client retired (Sysco, Cisco, K&M, Nogales) are recognised and
 * mapped to their replacement rather than being recreated.
 */
class ClientInventoryImporter
{
    public function __construct(private ?ItemVendorMapper $mapper = null)
    {
        $this->mapper ??= app(ItemVendorMapper::class);
    }

    /** Retired vendor => replacement. */
    public const RETIRED_VENDORS = [
        'sysco' => 'Restaurant Depot',
        'sysco foods' => 'Restaurant Depot',
        'cisco' => 'Restaurant Depot',
        'k&m distributors' => 'Restaurant Depot',
        'k & m distributors' => 'Restaurant Depot',
        'nogales produce' => 'Restaurant Depot',
        'nogales' => 'Restaurant Depot',
    ];

    /** Sheet spellings that mean an existing vendor. */
    public const VENDOR_ALIASES = [
        'coke' => 'Coca-Cola',
        'coca cola' => 'Coca-Cola',
        'coca-cola' => 'Coca-Cola',
        'sams club' => "Sam's Club",
        "sam's club" => "Sam's Club",
        'sams' => "Sam's Club",
        'restaurant depot' => 'Restaurant Depot',
        'lisanti' => 'Lisanti',
        'walmart' => 'Walmart',
        'heb' => 'HEB',
        'h-e-b' => 'HEB',
    ];

    /** @var array<string, list<string>> canonical => accepted headings (lowercased) */
    private array $aliases = [
        'name' => ['name', 'item', 'item name', 'description', 'product'],
        // "Which Vendor(s)" may hold several, comma separated. See splitVendors().
        'vendor' => [
            'supplier', 'suppliers', 'vendor', 'vendors', 'vendor name',
            'which vendor(s)', 'which vendors', 'which vendor(s) supply it',
            'vendor(s)', 'supplied by',
        ],
        'category' => ['category', 'group', 'section'],
        'pack_size' => [
            'portions per unit', 'pack size', 'pack', 'portions per box',
            'units per purchase', 'portions', 'size',
        ],
        'portion_size' => ['portion size', 'serving size', 'portion'],
        'portion_unit' => ['portion unit', 'serving unit', 'size unit'],
        'vendor_sku' => ['vendorid #', 'vendorid#', 'vendor id', 'vendor sku', 'sku', 'item #', 'item number'],
        'cost' => ['cost', 'price', 'unit cost', 'case cost'],
        'unit' => ['unit', 'purchase unit', 'order unit', 'uom'],
    ];

    /** Headings that identify the vendor-contact tab. */
    private array $vendorSheetAliases = [
        'name' => ['vendor name', 'vendor', 'name', 'supplier'],
        'contact_name' => ['contact person', 'contact name', 'contact'],
        'contact_phone' => ['phone', 'telephone', 'contact phone'],
        'contact_email' => ['email', 'e-mail', 'contact email'],
        'website' => ['website', 'web site', 'url', 'web'],
    ];

    /** Headings that identify the store-list tab. */
    private array $storeSheetAliases = [
        'name' => ['store name', 'store', 'location'],
        'address' => ['address', 'street address'],
        'manager_name' => ['manager name', 'manager'],
        'phone' => ['phone', 'store phone'],
    ];

    /**
     * Read the file and describe exactly what a commit would do. Writes nothing.
     *
     * @return array<string, mixed>
     */
    public function plan(string $path, Store $store): array
    {
        $sheets = $this->classifySheets($path);
        $rows = $sheets['items'] === null ? collect() : $this->mapToCanonical($sheets['items']);

        // A workbook may legitimately carry only the vendor-contact or store tab,
        // so an absent item sheet is only fatal when there is nothing else.
        if ($rows->isEmpty() && empty($sheets['vendors']) && empty($sheets['stores'])) {
            throw new RuntimeException(
                'No usable sheet found. The item tab needs a Name or Item Name column '
                .'alongside Category, Unit or Portions per Unit.'
            );
        }

        $existingVendors = Vendor::withTrashed()->get()->keyBy(fn ($v) => mb_strtolower($v->vendor_name));
        $existingCategories = InventoryCategory::all()->keyBy(fn ($c) => mb_strtolower($c->name));
        $existingItems = InventoryItem::withTrashed()->where('store_id', $store->id)->get()
            ->keyBy(fn ($i) => mb_strtolower($i->name));

        $plan = [
            'store' => $store,
            'rows_read' => $rows->count(),
            'vendors_to_create' => [],
            'vendors_matched' => [],
            'vendors_retired' => [],
            'categories_to_create' => [],
            'items_to_create' => [],
            'items_to_update' => [],
            'mappings' => 0,
            'multi_vendor_items' => 0,
            'prices' => 0,
            'vendor_contacts' => [],
            'stores_matched' => [],
            'stores_unmatched' => [],
            'errors' => [],
            'rows' => [],
        ];

        $seenNames = [];

        foreach ($rows as $row) {
            $line = $row['_line'];
            $name = trim((string) ($row['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $errors = [];
            $key = mb_strtolower($name);

            if (isset($seenNames[$key])) {
                $errors[] = "Duplicated in the file (also line {$seenNames[$key]}).";
            }
            $seenNames[$key] = $line;

            // ---- vendors. One cell can name several, comma separated, which is
            // the multi-vendor rule the whole system is built around. The first
            // one listed becomes the preferred vendor.
            $vendorNames = [];
            $retiredFrom = [];

            foreach ($this->splitVendors((string) ($row['vendor'] ?? '')) as $rawVendor) {
                $lower = mb_strtolower($rawVendor);

                if (isset(self::RETIRED_VENDORS[$lower])) {
                    $resolved = self::RETIRED_VENDORS[$lower];
                    $retiredFrom[] = $rawVendor;
                    $label = $rawVendor.' -> '.$resolved;
                    if (! in_array($label, $plan['vendors_retired'], true)) {
                        $plan['vendors_retired'][] = $label;
                    }
                } else {
                    $resolved = self::VENDOR_ALIASES[$lower] ?? $rawVendor;
                }

                if (in_array($resolved, $vendorNames, true)) {
                    continue; // two spellings of the same vendor on one row
                }

                $vendorNames[] = $resolved;
                $vendorKey = mb_strtolower($resolved);

                if ($existingVendors->has($vendorKey)) {
                    if (! in_array($resolved, $plan['vendors_matched'], true)) {
                        $plan['vendors_matched'][] = $resolved;
                    }
                } elseif (! in_array($resolved, $plan['vendors_to_create'], true)) {
                    $plan['vendors_to_create'][] = $resolved;
                }
            }

            // ---- category
            $categoryName = trim((string) ($row['category'] ?? ''));

            if ($categoryName === '') {
                $errors[] = 'Category is missing.';
            } elseif (! $existingCategories->has(mb_strtolower($categoryName))
                && ! in_array($categoryName, $plan['categories_to_create'], true)) {
                $plan['categories_to_create'][] = $categoryName;
            }

            // ---- pack size. Not fatal: the sheet leaves it blank for loose
            // items, so default to 1 and say so rather than dropping the row.
            $packSize = $this->numeric($row['pack_size'] ?? null);

            if ($packSize === null || $packSize <= 0) {
                $packSize = 1.0;
                $errors[] = 'Pack size missing or unreadable, defaulting to 1.';
            }

            $cost = $this->numeric($row['cost'] ?? null);
            $unit = trim((string) ($row['unit'] ?? '')) ?: 'case';

            $portionSize = $this->numeric($row['portion_size'] ?? null);
            $portionUnit = trim((string) ($row['portion_unit'] ?? '')) ?: null;

            if ($portionSize !== null && $portionSize <= 0) {
                $portionSize = null;
                $errors[] = 'Portion size is not a positive number, ignored.';
            }

            if ($portionSize !== null && $portionUnit === null) {
                $errors[] = 'Portion size has no unit, so it was ignored. Add a Portion Unit column (oz, lb).';
                $portionSize = null;
            }

            $isUpdate = $existingItems->has($key);
            $plan[$isUpdate ? 'items_to_update' : 'items_to_create'][] = $name;

            $plan['mappings'] += count($vendorNames);

            if (count($vendorNames) > 1) {
                $plan['multi_vendor_items']++;
            }

            if ($cost !== null && $cost > 0) {
                $plan['prices'] += count($vendorNames);
            }

            $plan['rows'][] = [
                'line' => $line,
                'name' => $name,
                'vendors' => $vendorNames,
                'retired_from' => $retiredFrom,
                'category' => $categoryName,
                'pack_size' => $packSize,
                'portion_size' => $portionSize,
                'portion_unit' => $portionUnit,
                'unit' => $unit,
                'vendor_sku' => trim((string) ($row['vendor_sku'] ?? '')) ?: null,
                'cost' => $cost,
                'action' => $isUpdate ? 'update' : 'create',
                'errors' => $errors,
            ];

            foreach ($errors as $error) {
                $plan['errors'][] = "Line {$line} ({$name}): {$error}";
            }
        }

        foreach ($sheets['vendors'] as $sheet) {
            $contacts = $this->applyVendorContacts($sheet, commit: false);
            $plan['vendor_contacts'] = array_values(array_unique(
                array_merge($plan['vendor_contacts'], $contacts['names'])
            ));
        }

        foreach ($sheets['stores'] as $sheet) {
            $stores = $this->applyStoreList($sheet, commit: false);
            $plan['stores_matched'] = array_values(array_unique(
                array_merge($plan['stores_matched'], $stores['matched'])
            ));
            $plan['stores_unmatched'] = array_values(array_unique(
                array_merge($plan['stores_unmatched'], $stores['unmatched'])
            ));
        }

        $plan['path'] = $path;

        return $plan;
    }

    /**
     * Apply a plan. One transaction, so a failure halfway through leaves the
     * database exactly as it was.
     *
     * @param  array<string, mixed>  $plan
     * @return array<string, int>
     */
    public function commit(array $plan, ClientInventoryImporterOptions $options): array
    {
        $store = $plan['store'];
        $result = [
            'vendors_created' => 0, 'categories_created' => 0,
            'items_created' => 0, 'items_updated' => 0,
            'mappings_written' => 0, 'prices_written' => 0, 'rows_skipped' => 0,
            'vendor_contacts_updated' => 0, 'stores_updated' => 0,
        ];

        DB::transaction(function () use ($plan, $store, $options, &$result) {
            // Vendor contacts first: an item row can then map straight onto a
            // vendor the contact tab just created.
            if (! empty($plan['path'])) {
                $sheets = $this->classifySheets($plan['path']);

                foreach ($sheets['vendors'] as $sheet) {
                    $contacts = $this->applyVendorContacts($sheet, commit: true);
                    $result['vendors_created'] += $contacts['created'];
                    $result['vendor_contacts_updated'] += $contacts['updated'];
                }

                foreach ($sheets['stores'] as $sheet) {
                    $result['stores_updated'] += $this->applyStoreList($sheet, commit: true)['updated'];
                }
            }

            $vendors = Vendor::withTrashed()->get()->keyBy(fn ($v) => mb_strtolower($v->vendor_name));
            $categories = InventoryCategory::all()->keyBy(fn ($c) => mb_strtolower($c->name));

            foreach ($plan['rows'] as $row) {
                $rowVendors = [];

                foreach ($row['vendors'] as $vendorName) {
                    $vendorKey = mb_strtolower($vendorName);
                    $vendor = $vendors->get($vendorKey);

                    if (! $vendor) {
                        if (! $options->createVendors) {
                            continue;
                        }

                        $vendor = Vendor::create([
                            'vendor_name' => $vendorName,
                            'vendor_type' => 'Food',
                            'is_active' => true,
                        ]);
                        $vendors->put($vendorKey, $vendor);
                        $result['vendors_created']++;
                    }

                    $rowVendors[] = $vendor;
                }

                $categoryKey = mb_strtolower($row['category']);
                $category = $categories->get($categoryKey);

                if (! $category) {
                    if ($row['category'] === '' || ! $options->createCategories) {
                        $result['rows_skipped']++;

                        continue;
                    }

                    $category = InventoryCategory::create([
                        'name' => $row['category'],
                        'display_order' => InventoryCategory::nextDisplayOrder(),
                    ]);
                    $categories->put($categoryKey, $category);
                    $result['categories_created']++;
                }

                $item = InventoryItem::withTrashed()
                    ->where('store_id', $store->id)
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($row['name'])])
                    ->first();

                $attributes = [
                    'inventory_category_id' => $category->id,
                    'category' => $category->name,
                    'purchase_unit' => $row['unit'],
                    // When the sheet gives a portion unit, that is what the item
                    // is counted in; otherwise fall back to counting pieces.
                    'base_unit' => $row['portion_unit'] ?? 'each',
                    'units_per_purchase' => $row['pack_size'],
                    'portion_size' => $row['portion_size'],
                    'portion_unit' => $row['portion_unit'],
                    'is_active' => true,
                ];

                if ($item) {
                    $item->update($attributes);
                    $result['items_updated']++;
                } else {
                    $item = InventoryItem::create($attributes + [
                        'store_id' => $store->id,
                        'name' => $row['name'],
                    ]);
                    $result['items_created']++;
                }

                if (empty($rowVendors)) {
                    continue;
                }

                // Route through the mapper so the one-preferred-vendor rule and
                // the vendor_prices mirror are applied the same way the UI does
                // it. First vendor named on the row wins the preference.
                $mapping = [];

                foreach ($rowVendors as $index => $vendor) {
                    $mapping[$vendor->id] = [
                        'enabled' => true,
                        'vendor_sku' => $row['vendor_sku'],
                        'current_price' => $row['cost'],
                        'is_preferred' => $index === 0,
                    ];
                    $result['mappings_written']++;

                    if ($row['cost'] !== null && $row['cost'] > 0) {
                        $result['prices_written']++;
                    }
                }

                $this->mapper->sync($item->fresh(), $mapping);
            }
        });

        return $result;
    }

    // ---- reading -----------------------------------------------------------

    /**
     * Work out what each sheet in the workbook is, from its headings.
     *
     * Order matters: a vendor-contact tab has a "Vendor Name" column that also
     * looks like an item "name", so the more specific shapes are tested first.
     *
     * @return array{items: ?Collection, vendors: list<Collection>, stores: list<Collection>}
     */
    private function classifySheets(string $path): array
    {
        $result = ['items' => null, 'vendors' => [], 'stores' => []];

        foreach ($this->readAllSheets($path) as $sheet) {
            if ($sheet->isEmpty()) {
                continue;
            }

            if ($this->sheetLooksLike($sheet, $this->vendorSheetAliases, 'name',
                ['contact_name', 'contact_email', 'contact_phone', 'website'])) {
                $result['vendors'][] = $sheet;

                continue;
            }

            if ($this->sheetLooksLike($sheet, $this->storeSheetAliases, 'name', ['address', 'manager_name'])) {
                $result['stores'][] = $sheet;

                continue;
            }

            if ($result['items'] === null
                && $this->sheetLooksLike($sheet, $this->aliases, 'name', ['category', 'pack_size', 'vendor'])) {
                $result['items'] = $sheet;
            }
        }

        return $result;
    }

    /**
     * True when some row in the sheet maps the required key plus at least one of
     * the supporting keys.
     *
     * @param  Collection<int, list<string>>  $sheet
     * @param  array<string, list<string>>  $aliases
     * @param  list<string>  $anyOf
     */
    private function sheetLooksLike(Collection $sheet, array $aliases, string $requiredKey, array $anyOf): bool
    {
        foreach ($sheet as $cells) {
            $map = $this->mapHeaderWith($cells, $aliases);

            if (! isset($map[$requiredKey])) {
                continue;
            }

            foreach ($anyOf as $key) {
                if (isset($map[$key])) {
                    return true;
                }
            }
        }

        return false;
    }

    /** @return Collection<int, array<string, mixed>> */
    private function readRows(string $path): Collection
    {
        $itemSheet = $this->classifySheets($path)['items'];

        return $itemSheet === null ? collect() : $this->mapToCanonical($itemSheet);
    }

    /**
     * Every sheet in the workbook, as raw rows.
     *
     * A single-sheet file (or the client's HTML export) yields one entry, so the
     * item import behaves exactly as before.
     *
     * @return Collection<int, Collection<int, list<string>>>
     */
    private function readAllSheets(string $path): Collection
    {
        if (! is_readable($path)) {
            throw new RuntimeException("Cannot read {$path}.");
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        // The client's export is an Excel "save as web page" HTML table, so that
        // is supported alongside real spreadsheets.
        if (in_array($extension, ['html', 'htm'], true)) {
            return collect([$this->readHtml($path)]);
        }

        return collect(Excel::toArray(new \stdClass, $path))
            ->map(fn ($sheet) => collect($sheet)
                ->map(fn ($row) => array_map(fn ($cell) => trim((string) $cell), (array) $row))
                ->filter(fn (array $cells) => count(array_filter($cells, fn ($c) => $c !== '')) > 0)
                ->values());
    }

    /** @return Collection<int, list<string>> */
    private function readHtml(string $path): Collection
    {
        $contents = file_get_contents($path);
        preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $contents, $matches);

        return collect($matches[1])->map(function (string $row) {
            preg_match_all('/<t[dh][^>]*>(.*?)<\/t[dh]>/is', $row, $cells);

            return array_map(
                fn ($cell) => trim(html_entity_decode(strip_tags($cell), ENT_QUOTES | ENT_HTML5)),
                $cells[1]
            );
        })->filter(fn (array $cells) => count(array_filter($cells, fn ($c) => $c !== '')) > 0)->values();
    }

    /** @return Collection<int, list<string>> */
    private function readSpreadsheet(string $path): Collection
    {
        $sheets = Excel::toArray(new \stdClass, $path);
        $first = $sheets[0] ?? [];

        return collect($first)
            ->map(fn ($row) => array_map(fn ($cell) => trim((string) $cell), (array) $row))
            ->filter(fn (array $cells) => count(array_filter($cells, fn ($c) => $c !== '')) > 0)
            ->values();
    }

    /**
     * Find the heading row wherever it is. The client's export has a title row
     * and a spreadsheet-letter row above the real headings.
     *
     * @param  Collection<int, list<string>>  $raw
     * @return Collection<int, array<string, mixed>>
     */
    private function mapToCanonical(Collection $raw): Collection
    {
        $headingIndex = null;
        $map = [];

        foreach ($raw as $index => $cells) {
            $candidate = $this->mapHeader($cells);

            if (isset($candidate['name'])) {
                $headingIndex = $index;
                $map = $candidate;
                break;
            }
        }

        if ($headingIndex === null) {
            throw new RuntimeException(
                'Could not find a heading row. The file needs a column named Name or Item, '
                .'alongside Supplier, Category, Pack Size and Cost.'
            );
        }

        return $raw
            ->slice($headingIndex + 1)
            ->values()
            ->map(function (array $cells, int $index) use ($map) {
                $row = ['_line' => $index + 1];

                foreach ($map as $key => $column) {
                    $row[$key] = $cells[$column] ?? null;
                }

                return $row;
            });
    }

    /** @return array<string, int> */
    private function mapHeader(array $cells): array
    {
        $map = [];

        foreach ($cells as $i => $cell) {
            $key = strtolower(trim(str_replace("\u{FEFF}", '', (string) $cell)));

            foreach ($this->aliases as $canonical => $names) {
                if (in_array($key, $names, true) && ! isset($map[$canonical])) {
                    $map[$canonical] = $i;
                }
            }
        }

        return $map;
    }

    /**
     * "Lisanti, Sam's, Restaurant Depot" -> three vendors.
     *
     * Split on commas, semicolons, slashes and the word "and". Apostrophes are
     * left alone so "Sam's Club" survives intact.
     *
     * @return list<string>
     */
    private function splitVendors(string $raw): array
    {
        $raw = trim($raw);

        if ($raw === '') {
            return [];
        }

        $parts = preg_split('/\s*(?:,|;|\/|\band\b|&(?!\s*M))\s*/i', $raw) ?: [];

        return array_values(array_filter(array_map('trim', $parts), fn ($p) => $p !== ''));
    }

    /**
     * Vendor-contact tab: create or top up vendors with the details the client
     * supplied. Existing values are never overwritten with blanks.
     *
     * @param  Collection<int, list<string>>  $raw
     * @return array{created: int, updated: int, names: list<string>}
     */
    private function applyVendorContacts(Collection $raw, bool $commit): array
    {
        $result = ['created' => 0, 'updated' => 0, 'names' => []];
        $rows = $this->mapSheet($raw, $this->vendorSheetAliases, 'name');

        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));

            if ($name === '' || mb_strtolower($name) === 'vendor name') {
                continue;
            }

            $canonical = self::VENDOR_ALIASES[mb_strtolower($name)] ?? $name;
            $result['names'][] = $canonical;

            if (! $commit) {
                continue;
            }

            $vendor = Vendor::withTrashed()
                ->whereRaw('LOWER(vendor_name) = ?', [mb_strtolower($canonical)])
                ->first();

            $details = array_filter([
                'contact_name' => trim((string) ($row['contact_name'] ?? '')) ?: null,
                'contact_phone' => trim((string) ($row['contact_phone'] ?? '')) ?: null,
                'contact_email' => trim((string) ($row['contact_email'] ?? '')) ?: null,
                'website' => trim((string) ($row['website'] ?? '')) ?: null,
            ], fn ($v) => $v !== null);

            if ($vendor) {
                if (! empty($details)) {
                    $vendor->update($details);
                    $result['updated']++;
                }

                continue;
            }

            Vendor::create($details + [
                'vendor_name' => $canonical,
                'vendor_type' => 'Food',
                'is_active' => true,
            ]);
            $result['created']++;
        }

        return $result;
    }

    /**
     * Store-list tab: match by name and top up the address details.
     *
     * Deliberately does NOT create stores. A store carries a creator, tax rates
     * and access grants that a spreadsheet cannot supply, so an unmatched row is
     * reported for a human to handle instead of half-created here.
     *
     * @param  Collection<int, list<string>>  $raw
     * @return array{matched: list<string>, unmatched: list<string>, updated: int}
     */
    private function applyStoreList(Collection $raw, bool $commit): array
    {
        $result = ['matched' => [], 'unmatched' => [], 'updated' => 0];
        $rows = $this->mapSheet($raw, $this->storeSheetAliases, 'name');
        $stores = Store::all();

        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));

            if ($name === '' || mb_strtolower($name) === 'store name') {
                continue;
            }

            $store = $stores->first(
                fn ($s) => mb_strtolower(trim((string) $s->store_info)) === mb_strtolower($name)
            );

            if (! $store) {
                $result['unmatched'][] = $name;

                continue;
            }

            $result['matched'][] = $name;

            if (! $commit) {
                continue;
            }

            $details = array_filter([
                'address' => trim((string) ($row['address'] ?? '')) ?: null,
                'phone' => trim((string) ($row['phone'] ?? '')) ?: null,
            ], fn ($v) => $v !== null);

            if (! empty($details)) {
                $store->update($details);
                $result['updated']++;
            }
        }

        return $result;
    }

    /**
     * Map a sheet's rows onto canonical keys using the given alias table.
     *
     * @param  Collection<int, list<string>>  $raw
     * @param  array<string, list<string>>  $aliases
     * @return list<array<string, mixed>>
     */
    private function mapSheet(Collection $raw, array $aliases, string $requiredKey): array
    {
        $headingIndex = null;
        $map = [];

        foreach ($raw as $index => $cells) {
            $candidate = $this->mapHeaderWith($cells, $aliases);

            if (isset($candidate[$requiredKey])) {
                $headingIndex = $index;
                $map = $candidate;
                break;
            }
        }

        if ($headingIndex === null) {
            return [];
        }

        return $raw->slice($headingIndex + 1)->values()
            ->map(function (array $cells) use ($map) {
                $row = [];

                foreach ($map as $key => $column) {
                    $row[$key] = $cells[$column] ?? null;
                }

                return $row;
            })
            ->all();
    }

    /** @return array<string, int> */
    private function mapHeaderWith(array $cells, array $aliases): array
    {
        $map = [];

        foreach ($cells as $i => $cell) {
            $key = strtolower(trim(str_replace("\u{FEFF}", '', (string) $cell)));

            foreach ($aliases as $canonical => $names) {
                if (in_array($key, $names, true) && ! isset($map[$canonical])) {
                    $map[$canonical] = $i;
                }
            }
        }

        return $map;
    }

    private function numeric(mixed $raw): ?float
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $clean = preg_replace('/[^0-9.\-]/', '', str_replace(',', '', (string) $raw));

        return ($clean === '' || ! is_numeric($clean)) ? null : (float) $clean;
    }
}
