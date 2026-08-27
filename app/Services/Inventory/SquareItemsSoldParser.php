<?php

namespace App\Services\Inventory;

use RuntimeException;

/**
 * Phase 5.4 — parse a Square "Items Sold" CSV export into normalized rows
 * (item, variation, quantity). Columns are matched by header alias so minor
 * differences between Square exports don't break it; a trailing "Totals" row
 * and blank lines are skipped. This is a SAMPLE-format parser — when the client
 * provides their real export, add its exact header names to $aliases.
 */
class SquareItemsSoldParser
{
    /** @var array<string, list<string>> canonical => accepted header names (lowercased) */
    private array $aliases = [
        'item' => ['item', 'item name', 'name'],
        'variation' => ['variation', 'item variation', 'variation name', 'size'],
        'quantity' => ['qty', 'quantity', 'qty sold', 'items sold', 'count', 'quantity sold', 'units sold'],
    ];

    /**
     * @return list<array{item: string, variation: string, quantity: float}>
     */
    public function parse(string $path): array
    {
        $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (! $lines) {
            return [];
        }

        $map = $this->mapHeader(str_getcsv(array_shift($lines)));
        if (! isset($map['item'], $map['quantity'])) {
            throw new RuntimeException('The Square CSV needs an item-name column and a quantity column.');
        }

        $rows = [];
        foreach ($lines as $line) {
            $cells = str_getcsv($line);
            $item = trim((string) ($cells[$map['item']] ?? ''));

            if ($item === '' || preg_match('/^totals?$/i', $item)) {
                continue; // skip blank lines and Square's totals row
            }

            $variation = isset($map['variation']) ? trim((string) ($cells[$map['variation']] ?? '')) : '';
            $qty = (float) preg_replace('/[^0-9.\-]/', '', (string) ($cells[$map['quantity']] ?? '0'));

            $rows[] = ['item' => $item, 'variation' => $variation, 'quantity' => $qty];
        }

        return $rows;
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
