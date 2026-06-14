<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Cashier\Subscription;

/**
 * Phase 4 — Admin billing dashboard: active subscriptions, MRR and churn.
 *
 * Metrics are computed from the local Cashier `subscriptions` table (kept in
 * sync by webhooks) using the configured monthly price, so the dashboard never
 * has to call Stripe.
 */
class SubscriptionAdminController extends Controller
{
    public function index(Request $request)
    {
        $monthlyAmount = config('subscription.monthly_amount') / 100; // dollars

        $activeQuery = Subscription::query()->active();
        $activeCount = (clone $activeQuery)->count();

        // Cancellations in the last 30 days (for a simple churn rate).
        $cancelledLast30 = Subscription::query()
            ->where('stripe_status', 'canceled')
            ->where('updated_at', '>=', now()->subDays(30))
            ->count();

        $denominator = $activeCount + $cancelledLast30;
        $churnRate = $denominator > 0 ? round(($cancelledLast30 / $denominator) * 100, 1) : 0.0;

        $mrr = $activeCount * $monthlyAmount;

        $subscriptions = (clone $activeQuery)
            ->with('user')
            ->latest()
            ->paginate(25);

        return view('admin.subscriptions.index', [
            'activeCount' => $activeCount,
            'mrr' => $mrr,
            'arr' => $mrr * 12,
            'cancelledLast30' => $cancelledLast30,
            'churnRate' => $churnRate,
            'monthlyAmount' => $monthlyAmount,
            'planName' => config('subscription.plan_name'),
            'subscriptions' => $subscriptions,
        ]);
    }
}
