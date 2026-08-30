<?php

namespace App\Support;

use App\Models\Order;

/**
 * Phase 5 Part 1.5 — one place that turns an order into a PDF.
 *
 * The order screen streams it to the browser and the manager's "order is ready"
 * email attaches it. Both must be the same document, so neither builds its own.
 */
class OrderPdf
{
    /** Raw PDF bytes for the order. */
    public static function render(Order $order): string
    {
        $order->loadMissing(['vendor', 'store', 'items.inventoryItem']);

        $dompdf = new \Dompdf\Dompdf;
        $dompdf->loadHtml(view('admin.orders.report-pdf', ['order' => $order])->render());
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    /** A filename a manager can recognise in a crowded downloads folder. */
    public static function filename(Order $order): string
    {
        $order->loadMissing('vendor');

        return sprintf(
            'order-%s-%s-%d.pdf',
            str($order->vendor->vendor_name ?? 'vendor')->slug(),
            $order->week_start_date->toDateString(),
            $order->order_sequence
        );
    }
}
