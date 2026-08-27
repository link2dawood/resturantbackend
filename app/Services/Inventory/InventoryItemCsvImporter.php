<?php

namespace App\Services\Inventory;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Phase 5 — bulk import of the client's order-guide spreadsheet.
 *
 * parse() never writes anything: it returns one row per CSV line, annotated with
 * its line number, whether the category exists, whether an item of that name is
 * already on file for the store, and any per-field errors. The controller shows
 * that as the preview; commit() then applies the decisions the user made there.
 *
 * "Portions per Unit" maps to inventory_items.units_per_purchase, the number of
 * base units in one purchase unit, which is what UnitConverter uses.
 */
class InventoryItemCsvImporter
{
    /** @var array<string, list<string>> canonical => accepted header names (lowercased) */
    private array $aliases = [
        'name' => ['name', 'item', 'item name', 'product', 'description'],
        'category' => ['category', 'group', 'section'],
        'unit' => ['unit', 'purchase unit', 'order unit', 'pack', 'buy unit'],
        'portions_per_unit' => [
            'portions per unit', 'portions per box', 'portions per case', 'portions',
            'portions/unit', 'units per purchase', 'qty per unit', 'per box', 'per case',
        ],
        'portion_size' => ['portion size', 'size', 'serving size', 'portion'],
        'portion_unit' => ['portion unit', 'size unit', 'serving unit', 'base unit'],
    ];

    public const ACTION_CREATE = 'create';
    public const ACTION_UPDATE = 'update';
    public const ACTION_SKIP = 'skip';

    /**
     * Read the CSV and describe what an import would do. Writes nothing.
     *
     * @return array{rows: list<array<string, mixed>>, missing_categories: list<string>}
     */
    public function parse(string $path, int $storeId): array
    {
        $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (! $lines) {
            throw new RuntimeException('That file is empty.');
        }

        $map = $this->mapHeader(str_getcsv((string) array_shift($lines)));
        if (! isset($map['name'])) {
            throw new RuntimeException('The CSV needs a "Name" column. Found: '.implode(', ', array_keys($map)).'.');
        }

        $categories = InventoryCategory::all()->keyBy(fn ($c) => mb_strtolower($c->name));
        $existing = InventoryItem::withTrashed()->where('store_id', $storeId)->get()
            ->keyBy(fn ($i) => mb_strtolower($i->name));

        $rows = [];
        $missingCategories = [];
        $seenNames = [];

        foreach ($lines as $offset => $line) {
            // +2: one for the header row, one because humans count from 1.
            $lineNumber = $offset + 2;
            $cells = str_getcsv((string) $line);
            $row = $this->buildRow($cells, $map, $lineNumber);

            if ($row['name'] === '') {
                continue; // trailing blank line, not an error worth reporting
            }

            $key = mb_strtolower($row['name']);

            if (isset($seenNames[$key])) {
                $row['errors'][] = 'Duplicated inside this file (also on line '.$seenNames[$key].').';
            }
            $seenNames[$key] = $lineNumber;

            // Category: matched case-insensitively, otherwise offered for creation.
            $category = $row['category'] !== '' ? $categories->get(mb_strtolower($row['category'])) : null;
            $row['inventory_category_id'] = $category?->id;
            $row['category_missing'] = $row['category'] !== '' && $category === null;

            if ($row['category'] === '') {
                $row['errors'][] = 'Category is required.';
            } elseif ($row['category_missing'] && ! in_array($row['category'], $missingCategories, true)) {
                $missingCategories[] = $row['category'];
            }

            // Duplicate by name within the store: the user picks update or skip.
            $match = $existing->get($key);
            $row['existing_item_id'] = $match?->id;
            $row['is_duplicate'] = $match !== null;
            $row['suggested_action'] = $match !== null ? self::ACTION_UPDATE : self::ACTION_CREATE;

            $rows[] = $row;
        }

        if (empty($rows)) {
            throw new RuntimeException('No item rows found in that file.');
        }

        return ['rows' => $rows, 'missing_categories' => $missingCategories];
    }

    /**
     * Apply the previewed rows. Rows carrying errors, and rows the user marked
     * skip, are counted and left alone.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return array{created: int, updated: int, skipped: int}
     */
    public function commit(array $rows, int $storeId, bool $createMissingCategories = false): array
    {
        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0];

        DB::transaction(function () use ($rows, $storeId, $createMissingCategories, &$result) {
            $categories = InventoryCategory::all()->keyBy(fn ($c) => mb_strtolower($c->name));

            foreach ($rows as $row) {
                if (($row['action'] ?? self::ACTION_SKIP) === self::ACTION_SKIP || ! empty($row['errors'])) {
                    $result['skipped']++;

                    continue;
                }

                $categoryId = $row['inventory_category_id'] ?? null;

                if (! $categoryId && $createMissingCategories && filled($row['category'] ?? null)) {
                    $key = mb_strtolower($row['category']);
                    $category = $categories->get($key) ?? tap(
                        InventoryCategory::create([
                            'name' => $row['category'],
                            'display_order' => InventoryCategory::nextDisplayOrder(),
                        ]),
                        fn ($created) => $categories->put($key, $created)
                    );
                    $categoryId = $category->id;
                }

                if (! $categoryId) {
                    $result['skipped']++;

                    continue;
                }

                $attributes = [
                    'inventory_category_id' => $categoryId,
                    'category' => $row['category'],
                    'purchase_unit' => $row['unit'] !== '' ? $row['unit'] : 'case',
                    'base_unit' => $row['portion_unit'] !== '' ? $row['portion_unit'] : 'each',
                    'units_per_purchase' => $row['portions_per_unit'],
                    'portion_size' => $row['portion_size'],
                    'portion_unit' => $row['portion_unit'] !== '' ? $row['portion_unit'] : null,
                ];

                if (($row['action'] ?? null) === self::ACTION_UPDATE && ! empty($row['existing_item_id'])) {
                    $item = InventoryItem::withTrashed()->find($row['existing_item_id']);

                    if (! $item || $item->store_id !== $storeId) {
                        $result['skipped']++;

                        continue;
                    }

                    $item->update($attributes);
                    $result['updated']++;

                    continue;
                }

                InventoryItem::create($attributes + [
                    'store_id' => $storeId,
                    'name' => $row['name'],
                    'is_active' => true,
                ]);
                $result['created']++;
            }
        });

        return $result;
    }

    /**
     * @param  list<string>  $cells
     * @param  array<string, int>  $map
     * @return array<string, mixed>
     */
    private function buildRow(array $cells, array $map, int $lineNumber): array
    {
        $value = fn (string $key): string => isset($map[$key])
            ? trim((string) ($cells[$map[$key]] ?? ''))
            : '';

        $row = [
            'line' => $lineNumber,
            'name' => $value('name'),
            'category' => $value('category'),
            'unit' => $value('unit'),
            'portion_unit' => $value('portion_unit'),
            'errors' => [],
        ];

        $row['portions_per_unit'] = $this->numeric($value('portions_per_unit'));
        $row['portion_size'] = $this->numeric($value('portion_size'));

        if ($row['name'] !== '' && mb_strlen($row['name']) > 150) {
            $row['errors'][] = 'Name is longer than 150 characters.';
        }

        if ($row['portions_per_unit'] === null) {
            $row['errors'][] = 'Portions per unit is missing or not a number.';
        } elseif ($row['portions_per_unit'] <= 0) {
            $row['errors'][] = 'Portions per unit must be greater than zero.';
        }

        if ($row['portion_size'] !== null && $row['portion_size'] <= 0) {
            $row['errors'][] = 'Portion size must be greater than zero.';
        }

        if ($row['portion_size'] !== null && $row['portion_unit'] === '') {
            $row['errors'][] = 'Portion size needs a portion unit, for example oz.';
        }

        return $row;
    }

    /** Tolerates "53", "53.0", "1,200" and "10 lb"; returns null when there is no number. */
    private function numeric(string $raw): ?float
    {
        if ($raw === '') {
            return null;
        }

        $clean = preg_replace('/[^0-9.\-]/', '', str_replace(',', '', $raw));

        return ($clean === '' || ! is_numeric($clean)) ? null : (float) $clean;
    }

    /** @return array<string, int> canonical => column index */
    private function mapHeader(array $header): array
    {
        $map = [];
        foreach ($header as $i => $raw) {
            $key = strtolower(trim(str_replace("\u{FEFF}", '', (string) $raw)));
            foreach ($this->aliases as $canonical => $names) {
                if (in_array($key, $names, true) && ! isset($map[$canonical])) {
                    $map[$canonical] = $i;
                }
            }
        }

        return $map;
    }
}
