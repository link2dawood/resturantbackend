<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImportBatch;
use App\Models\OwnerCcStatementImport;
use App\Models\ThirdPartyStatement;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ImportLogController extends Controller
{
    /**
     * Download/Upload log: unified history for credit cards, banks, and online platforms.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $accessibleStoreIds = $user->getAccessibleStoreIds();

        $items = new Collection();

        // Owner CC statement imports
        $ccImports = OwnerCcStatementImport::with(['store', 'importer'])
            ->where(function ($q) use ($accessibleStoreIds) {
                $q->whereIn('store_id', $accessibleStoreIds)->orWhereNull('store_id');
            })
            ->get()
            ->map(function ($imp) {
                return (object)[
                    'type' => 'Credit Card',
                    'date' => $imp->created_at,
                    'file_name' => $imp->file_name,
                    'store' => $imp->store?->store_info,
                    'user' => $imp->importer?->name,
                    'rows' => $imp->rows_imported,
                    'detail' => $imp->cardPlatformLabel(),
                ];
            });
        $items = $items->merge($ccImports);

        // Third-party (Uber, DoorDash, Grubhub) statements
        $thirdParty = ThirdPartyStatement::with(['store', 'importer'])
            ->whereIn('store_id', $accessibleStoreIds)
            ->get()
            ->map(function ($st) {
                return (object)[
                    'type' => 'Online Platform',
                    'date' => $st->created_at,
                    'file_name' => $st->file_name ?? ($st->platform . ' statement'),
                    'store' => $st->store?->store_info,
                    'user' => $st->importer?->name,
                    'rows' => null,
                    'detail' => null,
                ];
            });
        $items = $items->merge($thirdParty);

        // Bank import batches
        $bankBatches = ImportBatch::with(['store', 'importer'])
            ->where('import_type', 'bank_statement')
            ->where(function ($q) use ($accessibleStoreIds) {
                $q->whereIn('store_id', $accessibleStoreIds)->orWhereNull('store_id');
            })
            ->get()
            ->map(function ($batch) {
                return (object)[
                    'type' => 'Bank',
                    'date' => $batch->imported_at ?? $batch->created_at,
                    'file_name' => $batch->file_name,
                    'store' => $batch->store?->store_info,
                    'user' => $batch->importer?->name,
                    'rows' => $batch->imported_count,
                    'detail' => null,
                ];
            });
        $items = $items->merge($bankBatches);

        $items = $items->sortByDesc('date')->values();
        $perPage = 25;
        $page = $request->get('page', 1);
        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $items->forPage($page, $perPage),
            $items->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.import-log.index', ['imports' => $paginated]);
    }
}
