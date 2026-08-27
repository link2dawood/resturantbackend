<?php

namespace App\Services\Inventory;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Store;
use App\Models\Vendor;
use App\Models\VendorPrice;
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
        'vendor' => ['supplier', 'vendor', 'vendor name'],
        'category' => ['category', 'group', 'section'],
        'pack_size' => ['pack size', 'pack', 'portions per unit', 'portions per box', 'units per purchase', 'size'],
        'vendor_sku' => ['vendorid #', 'vendorid#', 'vendor id', 'vendor sku', 'sku', 'item #', 'item number'],
        'cost' => ['cost', 'price', 'unit cost', 'case cost'],
        'unit' => ['unit', 'purchase unit', 'order unit', 'uom'],
    ];

    /**
     * Read the file and describe exactly what a commit would do. Writes nothing.
     *
     * @return array<string, mixed>
     */
    public function plan(string $path, Store $store): array
    {
        $rows = $this->readRows($path);

        if ($rows->isEmpty()) {
            throw new RuntimeException('No data rows found in that file.');
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
            'prices' => 0,
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

            // ---- vendor
            $rawVendor = trim((string) ($row['vendor'] ?? ''));
            $vendorName = null;
            $retiredFrom = null;

            if ($rawVendor !== '') {
                $lower = mb_strtolower($rawVendor);

                if (isset(self::RETIRED_VENDORS[$lower])) {
                    $retiredFrom = $rawVendor;
                    $vendorName = self::RETIRED_VENDORS[$lower];
                    $label = $rawVendor.' -> '.$vendorName;
                    if (! in_array($label, $plan['vendors_retired'], true)) {
                        $plan['vendors_retired'][] = $label;
                    }
                } else {
                    $vendorName = self::VENDOR_ALIASES[$lower] ?? $rawVendor;
                }

                $vendorKey = mb_strtolower($vendorName);

                if ($existingVendors->has($vendorKey)) {
                    if (! in_array($vendorName, $plan['vendors_matched'], true)) {
                        $plan['vendors_matched'][] = $vendorName;
                    }
                } elseif (! in_array($vendorName, $plan['vendors_to_create'], true)) {
                    $plan['vendors_to_create'][] = $vendorName;
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

            $isUpdate = $existingItems->has($key);
            $plan[$isUpdate ? 'items_to_update' : 'items_to_create'][] = $name;

            if ($vendorName !== null) {
                $plan['mappings']++;

                if ($cost !== null && $cost > 0) {
                    $plan['prices']++;
                }
            }

            $plan['rows'][] = [
                'line' => $line,
                'name' => $name,
                'vendor' => $vendorName,
                'retired_from' => $retiredFrom,
                'category' => $categoryName,
                'pack_size' => $packSize,
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
        ];

        DB::transaction(function () use ($plan, $store, $options, &$result) {
            $vendors = Vendor::withTrashed()->get()->keyBy(fn ($v) => mb_strtolower($v->vendor_name));
            $categories = InventoryCategory::all()->keyBy(fn ($c) => mb_strtolower($c->name));

            foreach ($plan['rows'] as $row) {
                $vendor = null;

                if ($row['vendor'] !== null) {
                    $vendorKey = mb_strtolower($row['vendor']);
                    $vendor = $vendors->get($vendorKey);

                    if (! $vendor) {
                        if (! $options->createVendors) {
                            $result['rows_skipped']++;

                            continue;
                        }

                        $vendor = Vendor::create([
                            'vendor_name' => $row['vendor'],
                            'vendor_type' => 'Food',
                            'is_active' => true,
                        ]);
                        $vendors->put($vendorKey, $vendor);
                        $result['vendors_created']++;
                    }
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
                    'base_unit' => 'each',
                    'units_per_purchase' => $row['pack_size'],
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

                if (! $vendor) {
                    continue;
                }

                // The sheet lists one supplier per item, so that supplier is the
                // preferred vendor. Both readings of "preferred" are written.
                $item->vendors()->syncWithoutDetaching([
                    $vendor->id => [
                        'vendor_sku' => $row['vendor_sku'],
                        'current_price' => $row['cost'],
                        'price_updated_at' => $row['cost'] !== null ? now() : null,
                        'is_preferred_vendor' => true,
                    ],
                ]);
                $item->forceFill(['preferred_vendor_id' => $vendor->id])->save();
                $result['mappings_written']++;

                if ($row['cost'] !== null && $row['cost'] > 0) {
                    VendorPrice::create([
                        'vendor_id' => $vendor->id,
                        'inventory_item_id' => $item->id,
                        'price' => $row['cost'],
                        'price_unit' => $row['unit'],
                        'effective_date' => now()->toDateString(),
                        'entered_by' => auth()->id(),
                    ]);
                    $result['prices_written']++;
                }
            }
        });

        return $result;
    }

    // ---- reading -----------------------------------------------------------

    /** @return Collection<int, array<string, mixed>> */
    private function readRows(string $path): Collection
    {
        if (! is_readable($path)) {
            throw new RuntimeException("Cannot read {$path}.");
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        // The client's export is an Excel "save as web page" HTML table, so that
        // is supported alongside real spreadsheets.
        $raw = in_array($extension, ['html', 'htm'], true)
            ? $this->readHtml($path)
            : $this->readSpreadsheet($path);

        return $raw->isEmpty() ? collect() : $this->mapToCanonical($raw);
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

    private function numeric(mixed $raw): ?float
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $clean = preg_replace('/[^0-9.\-]/', '', str_replace(',', '', (string) $raw));

        return ($clean === '' || ! is_numeric($clean)) ? null : (float) $clean;
    }
}
