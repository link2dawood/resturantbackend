<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Phase 5 Part 2 — restate saved variance snapshots.
 *
 * The client set the convention on 2026-09-22: variance is counted minus
 * assumed on hand, so a shortage reads negative, and the percentage is measured
 * against assumed on hand rather than everything available that week.
 *
 * Lines written by the Tuesday job before that date carry the old sign and the
 * old denominator. Left alone they would sit in the same report as new lines
 * meaning the opposite thing, so they are restated here.
 */
return new class extends Migration
{
    public function up(): void
    {
        $lines = DB::table('variance_report_lines')
            ->whereNotNull('variance')
            ->select('id', 'variance', 'theoretical_ending')
            ->get();

        foreach ($lines as $line) {
            $variance = -1 * (float) $line->variance;
            $assumed = (float) $line->theoretical_ending;

            DB::table('variance_report_lines')->where('id', $line->id)->update([
                'variance' => round($variance, 4),
                'variance_pct' => $assumed == 0.0 ? 0 : round($variance / abs($assumed) * 100, 4),
            ]);
        }
    }

    public function down(): void
    {
        $lines = DB::table('variance_report_lines')
            ->whereNotNull('variance')
            ->select('id', 'variance', 'total_available')
            ->get();

        foreach ($lines as $line) {
            $variance = -1 * (float) $line->variance;
            $available = (float) $line->total_available;

            DB::table('variance_report_lines')->where('id', $line->id)->update([
                'variance' => round($variance, 4),
                'variance_pct' => $available == 0.0 ? 0 : round($variance / $available * 100, 4),
            ]);
        }
    }
};
