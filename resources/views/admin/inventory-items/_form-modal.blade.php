{{-- Create/edit form for an inventory item. Included by index.blade.php. --}}
<div class="modal fade" id="itemModal" tabindex="-1" aria-labelledby="itemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="itemModalLabel">Add Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="itemForm">
                <div class="modal-body">
                    <input type="hidden" id="itemId">

                    <div class="row">
                        <div class="col-md-7 mb-3">
                            <label for="name" class="form-label">Item Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required maxlength="150" placeholder="e.g. Ribeye Steak">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-5 mb-3">
                            <label for="inventoryCategoryId" class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select" id="inventoryCategoryId" name="inventory_category_id" required>
                                <option value="">Select category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <div class="card mb-3" style="background-color: var(--google-grey-50, #f8f9fa);">
                        <div class="card-body">
                            <h6 class="mb-1">Pack size</h6>
                            <p class="text-muted small mb-3">
                                How the item is ordered, and how much is in one of them. Every order quantity and
                                variance figure is derived from these values, so keep them current when a supplier
                                changes the pack.
                            </p>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="purchaseUnit" class="form-label">Ordered by (unit) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="purchaseUnit" name="purchase_unit" required maxlength="20" placeholder="box, case, bag">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="unitsPerPurchase" class="form-label">
                                        Portions per Box/Unit <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" step="0.0001" min="0.0001" class="form-control" id="unitsPerPurchase" name="units_per_purchase" required placeholder="53">
                                    <small class="text-muted">e.g. 53 portions of steak in one box</small>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="baseUnit" class="form-label">Counted in (base unit) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="baseUnit" name="base_unit" required maxlength="20" placeholder="portion, oz, lb, each">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="portionSize" class="form-label">Portion size</label>
                                    <input type="number" step="0.01" min="0" class="form-control" id="portionSize" name="portion_size" placeholder="3.00">
                                    <small class="text-muted">Optional, e.g. a 3 oz steak portion</small>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="portionUnit" class="form-label">Portion unit</label>
                                    <input type="text" class="form-control" id="portionUnit" name="portion_unit" maxlength="20" placeholder="oz">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="minStockLevel" class="form-label">Minimum stock</label>
                                    <input type="number" step="0.0001" min="0" class="form-control" id="minStockLevel" name="min_stock_level" placeholder="0">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>

                            <div class="alert alert-light border mb-0 py-2">
                                <small class="text-muted" id="portionHint"></small>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Vendors</label>
                        <p class="text-muted small mb-2">
                            Tick every vendor that sells this item, then set one as preferred. The preferred
                            vendor is the default when orders are generated. Prices are quoted per purchase
                            unit and also land on the price comparison screen.
                        </p>

                        <div id="noVendorWarning" class="alert alert-warning py-2 d-none">
                            <small>No vendor is ticked yet. You can save the item now and map vendors later,
                            but it will not appear on any generated order until one is set.</small>
                        </div>

                        <div class="table-responsive border rounded" style="max-height: 280px; overflow-y: auto;">
                            <table class="table table-sm mb-0" id="vendorMappingTable">
                                <thead style="background-color: var(--google-grey-50, #f8f9fa); position: sticky; top: 0;">
                                    <tr>
                                        <th style="width: 34%;">Vendor</th>
                                        <th style="width: 12%;" class="text-center">Enabled</th>
                                        <th style="width: 24%;">Vendor SKU</th>
                                        <th style="width: 18%;">Current Price</th>
                                        <th style="width: 12%;" class="text-center">Preferred</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($vendors as $vendor)
                                    <tr data-vendor-row="{{ $vendor->id }}">
                                        <td style="vertical-align: middle;">
                                            {{ $vendor->vendor_name }}
                                            <div><small class="text-muted vendor-price-stamp" data-stamp-for="{{ $vendor->id }}"></small></div>
                                        </td>
                                        <td class="text-center" style="vertical-align: middle;">
                                            <input type="checkbox" class="form-check-input vendor-enabled"
                                                   data-vendor="{{ $vendor->id }}" id="vendorEnabled{{ $vendor->id }}">
                                        </td>
                                        <td style="vertical-align: middle;">
                                            <input type="text" class="form-control form-control-sm vendor-sku"
                                                   data-vendor="{{ $vendor->id }}" maxlength="100" placeholder="optional" disabled>
                                        </td>
                                        <td style="vertical-align: middle;">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">$</span>
                                                <input type="number" step="0.01" min="0" class="form-control vendor-price"
                                                       data-vendor="{{ $vendor->id }}" placeholder="0.00" disabled>
                                            </div>
                                        </td>
                                        <td class="text-center" style="vertical-align: middle;">
                                            <input type="radio" name="preferred_vendor_radio" class="form-check-input vendor-preferred"
                                                   data-vendor="{{ $vendor->id }}" value="{{ $vendor->id }}" disabled>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="5" class="text-muted text-center py-3">
                                        No active supply vendors. Add one under Vendors first.
                                    </td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="text-danger small mt-1 d-none" id="vendorMappingError"></div>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="isActive" name="is_active" checked>
                        <label class="form-check-label" for="isActive">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Item</button>
                </div>
            </form>
        </div>
    </div>
</div>
