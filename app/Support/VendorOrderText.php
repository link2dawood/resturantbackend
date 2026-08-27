<?php

namespace App\Support;

use App\Models\Order;

/**
 * Phase 5 Part 1 Task 11 — the plain-text rendering of a vendor order.
 *
 * Built in one place because it is used three ways that must stay identical:
 * the copy-to-clipboard button, the mailto body, and the fallback text shown on
 * the report page. Deliberately plain ASCII-ish: it gets pasted into WhatsApp
 * and vendor email, where anything clever turns into mojibake.
 */
class VendorOrderText
{
    public static function build(Order $order): string
    {
        $store = $order->store;
        $vendor = $order->vendor;

        $lines = [];
        $lines[] = 'ORDER — '.($store->store_info ?? 'Store');
        $lines[] = str_repeat('=', 40);
        $lines[] = 'Vendor:   '.($vendor->vendor_name ?? 'Vendor');
        $lines[] = 'Week of:  '.$order->week_start_date->format('M j, Y');
        $lines[] = 'Order:    #'.$order->order_sequence;
        $lines[] = 'Date:     '.($order->placed_at ?? $order->created_at)->format('M j, Y');
        $lines[] = '';

        if (filled($storeAddress = self::storeAddress($store))) {
            $lines[] = 'Deliver to:';
            foreach ($storeAddress as $addressLine) {
                $lines[] = '  '.$addressLine;
            }
            $lines[] = '';
        }

        $lines[] = 'ITEMS';
        $lines[] = str_repeat('-', 40);

        foreach ($order->items as $item) {
            $name = $item->inventoryItem->name ?? 'Item';
            $quantity = self::number($item->quantity);
            $unit = $item->unit;

            $line = sprintf('%-24s %8s %s', self::truncate($name, 24), $quantity, $unit);

            if ($item->unit_price !== null) {
                $line .= sprintf('  @ $%s = $%s',
                    number_format((float) $item->unit_price, 2),
                    number_format((float) $item->line_total, 2)
                );
            }

            $lines[] = $line;

            if (filled($item->notes)) {
                $lines[] = '    note: '.$item->notes;
            }
        }

        $lines[] = str_repeat('-', 40);

        if ($order->total > 0) {
            $lines[] = sprintf('%-24s %s', 'TOTAL', '$'.number_format($order->total, 2));
            $lines[] = '';
        }

        if (filled($order->notes)) {
            $lines[] = 'Notes:';
            $lines[] = '  '.$order->notes;
            $lines[] = '';
        }

        if (filled($store->phone ?? null)) {
            $lines[] = 'Questions: '.$store->phone;
        }

        return implode("\n", $lines);
    }

    /** A short subject line for the mailto link. */
    public static function subject(Order $order): string
    {
        return sprintf(
            'Order for %s — week of %s (Order %d)',
            $order->store->store_info ?? 'our store',
            $order->week_start_date->format('M j, Y'),
            $order->order_sequence
        );
    }

    /** @return list<string> */
    private static function storeAddress($store): array
    {
        $lines = [];

        if (filled($store->address ?? null)) {
            $lines[] = $store->address;
        }

        $cityLine = trim(implode(' ', array_filter([
            filled($store->city ?? null) ? $store->city.',' : null,
            $store->state ?? null,
            $store->zip ?? null,
        ])));

        if ($cityLine !== '') {
            $lines[] = $cityLine;
        }

        return $lines;
    }

    /** Trim trailing zeros: 5.0000 reads as 5, 2.5000 as 2.5. */
    private static function number($value): string
    {
        return rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.');
    }

    private static function truncate(string $value, int $length): string
    {
        return mb_strlen($value) > $length ? mb_substr($value, 0, $length - 1).'…' : $value;
    }
}
