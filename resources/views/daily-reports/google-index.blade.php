@extends('layouts.google-dashboard')

@section('title', 'Daily Reports')
@section('page-title', 'Daily Reports')
@section('page-subtitle', 'Manage and track your restaurant\'s daily performance')

@section('page-actions')
<div class="gd-flex gd-gap-sm">
    @if(auth()->user()->hasPermission('create_reports'))
    <a href="{{ route('daily-reports.create') }}" class="gd-button gd-button-primary">
        <span class="material-symbols-outlined">add</span>
        Create Report
    </a>
    @endif
</div>
@endsection

@section('content')

<!-- Summary Statistics - Immediate Emotional Impact -->
@if($reports->count() > 0)
@php
    $totalGross = $reports->sum('gross_sales');
    $totalNet = $reports->sum('net_sales');
    $avgGross = $reports->avg('gross_sales');
    $totalCustomers = $reports->sum('total_customers');
@endphp

<section aria-labelledby="summary-heading" class="gd-mb-xl">
    <h2 id="summary-heading" class="gd-sr-only">Report Summary</h2>

    <div class="gd-grid gd-grid-cols-1 md:gd-grid-cols-2 lg:gd-grid-cols-4 gd-gap-lg gd-mb-xl">
        <!-- Total Gross Sales - Success Emotion -->
        <div class="gd-stat-card success" role="img" aria-label="Total gross sales: ${{ number_format($totalGross, 0) }}">
            <div class="gd-stat-icon success">
                <span class="material-symbols-outlined">trending_up</span>
            </div>
            <div class="gd-stat-value">${{ number_format($totalGross, 0) }}</div>
            <div class="gd-stat-label">Total Gross Sales</div>
        </div>

        <!-- Total Net Sales - Trust Building -->
        <div class="gd-stat-card trust" role="img" aria-label="Total net sales: ${{ number_format($totalNet, 0) }}">
            <div class="gd-stat-icon trust">
                <span class="material-symbols-outlined">account_balance_wallet</span>
            </div>
            <div class="gd-stat-value">${{ number_format($totalNet, 0) }}</div>
            <div class="gd-stat-label">Total Net Sales</div>
        </div>

        <!-- Average Daily Sales -->
        <div class="gd-stat-card warning" role="img" aria-label="Average daily sales: ${{ number_format($avgGross, 0) }}">
            <div class="gd-stat-icon warning">
                <span class="material-symbols-outlined">analytics</span>
            </div>
            <div class="gd-stat-value">${{ number_format($avgGross, 0) }}</div>
            <div class="gd-stat-label">Average Daily Sales</div>
        </div>

        <!-- Total Customers Served -->
        <div class="gd-stat-card trust" role="img" aria-label="Total customers: {{ number_format($totalCustomers) }}">
            <div class="gd-stat-icon trust">
                <span class="material-symbols-outlined">group</span>
            </div>
            <div class="gd-stat-value">{{ number_format($totalCustomers) }}</div>
            <div class="gd-stat-label">Total Customers</div>
        </div>
    </div>
</section>
@endif

<!-- Search & Filter Section - Progressive Disclosure -->
<div class="gd-card gd-mb-xl">
    <div class="gd-card-header">
        <h3 class="gd-card-title">Search & Filter</h3>
        <p class="gd-card-subtitle">Find specific reports using filters</p>
    </div>
    <div class="gd-card-body">
        <form method="GET" action="{{ route('daily-reports.index') }}" class="gd-grid gd-grid-cols-1 md:gd-grid-cols-2 lg:gd-grid-cols-5 gd-gap-md">
            <!-- Search Input -->
            <div class="lg:gd-col-span-2">
                <label for="search" class="gd-label-medium gd-block gd-mb-xs">Search</label>
                <div class="gd-relative">
                    <span class="gd-absolute gd-left-md gd-top-1/2 -translate-y-1/2 material-symbols-outlined gd-text-secondary">search</span>
                    <input type="text"
                           id="search"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Search reports, stores, creators..."
                           class="gd-w-full gd-pl-xl gd-pr-md gd-py-sm gd-border gd-border-gray-300 gd-rounded-md gd-text-sm gd-bg-surface-0"
                           style="font-family: 'Google Sans', sans-serif;">
                </div>
            </div>

            <!-- Date From -->
            <div>
                <label for="date_from" class="gd-label-medium gd-block gd-mb-xs">From Date</label>
                <input type="date"
                       id="date_from"
                       name="date_from"
                       value="{{ request('date_from') }}"
                       class="gd-w-full gd-px-md gd-py-sm gd-border gd-border-gray-300 gd-rounded-md gd-text-sm gd-bg-surface-0"
                       style="font-family: 'Google Sans', sans-serif;">
            </div>

            <!-- Date To -->
            <div>
                <label for="date_to" class="gd-label-medium gd-block gd-mb-xs">To Date</label>
                <input type="date"
                       id="date_to"
                       name="date_to"
                       value="{{ request('date_to') }}"
                       class="gd-w-full gd-px-md gd-py-sm gd-border gd-border-gray-300 gd-rounded-md gd-text-sm gd-bg-surface-0"
                       style="font-family: 'Google Sans', sans-serif;">
            </div>

            <!-- Store Filter -->
            <div>
                <label for="store_id" class="gd-label-medium gd-block gd-mb-xs">Store</label>
                <select name="store_id"
                        id="store_id"
                        class="gd-w-full gd-px-md gd-py-sm gd-border gd-border-gray-300 gd-rounded-md gd-text-sm gd-bg-surface-0"
                        style="font-family: 'Google Sans', sans-serif;">
                    <option value="">All Stores</option>
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>
                            {{ $store->store_info }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Search Button -->
            <div class="lg:gd-col-span-5 gd-flex gd-justify-end gd-gap-sm">
                <a href="{{ route('daily-reports.index') }}" class="gd-button gd-button-outlined">
                    <span class="material-symbols-outlined">clear</span>
                    Clear
                </a>
                <button type="submit" class="gd-button gd-button-primary">
                    <span class="material-symbols-outlined">search</span>
                    Search
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Reports Table or Empty State -->
@if($reports->count() > 0)
<div class="gd-card">
    <div class="gd-card-header gd-flex gd-justify-between gd-items-center">
        <div>
            <h3 class="gd-card-title">Reports</h3>
            <p class="gd-card-subtitle">{{ $reports->total() }} reports found</p>
        </div>
        @if($reports->count() > 0)
        <div class="gd-flex gd-gap-sm">
            <a href="{{ route('daily-reports.export-csv') }}" class="gd-button gd-button-outlined gd-button-small">
                <span class="material-symbols-outlined">file_download</span>
                Export CSV
            </a>
        </div>
        @endif
    </div>

    <!-- Responsive Table -->
    <div class="gd-overflow-auto">
        <table class="gd-w-full gd-table-auto" style="font-family: 'Google Sans', sans-serif;">
            <thead class="gd-bg-surface-1 gd-border-b gd-border-surface-3">
                <tr>
                    <th class="gd-px-lg gd-py-md gd-text-left gd-label-medium gd-text-secondary">Date</th>
                    <th class="gd-px-lg gd-py-md gd-text-left gd-label-medium gd-text-secondary">Store</th>
                    <th class="gd-px-lg gd-py-md gd-text-left gd-label-medium gd-text-secondary">Status</th>
                    <th class="gd-px-lg gd-py-md gd-text-left gd-label-medium gd-text-secondary">Gross Sales</th>
                    <th class="gd-px-lg gd-py-md gd-text-left gd-label-medium gd-text-secondary">Net Sales</th>
                    <th class="gd-px-lg gd-py-md gd-text-left gd-label-medium gd-text-secondary">Created By</th>
                    <th class="gd-px-lg gd-py-md gd-text-center gd-label-medium gd-text-secondary">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reports as $report)
                <tr class="gd-border-b gd-border-surface-2 hover:gd-bg-surface-1 gd-transition-colors gd-duration-200">
                    <!-- Date -->
                    <td class="gd-px-lg gd-py-md">
                        <div class="gd-label-medium">{{ $report->report_date->format('M j, Y') }}</div>
                        <div class="gd-body-small gd-text-secondary gd-mt-xs">{{ $report->report_date->format('l') }}</div>
                    </td>

                    <!-- Store -->
                    <td class="gd-px-lg gd-py-md">
                        <div class="gd-label-medium">{{ $report->store->store_info ?? 'N/A' }}</div>
                        @if($report->page_number)
                        <div class="gd-body-small gd-text-secondary gd-mt-xs">Page #{{ $report->page_number }}</div>
                        @endif
                    </td>

                    <!-- Status Badge -->
                    <td class="gd-px-lg gd-py-md">
                        @php
                            $statusConfig = [
                                'draft' => ['class' => 'gd-bg-gray-100 gd-text-gray-700', 'icon' => 'draft'],
                                'submitted' => ['class' => 'gd-bg-warning gd-text-warning-600', 'icon' => 'schedule'],
                                'approved' => ['class' => 'gd-bg-success gd-text-success-600', 'icon' => 'check_circle'],
                                'rejected' => ['class' => 'gd-bg-error gd-text-error-600', 'icon' => 'cancel']
                            ];
                            $config = $statusConfig[$report->status] ?? $statusConfig['draft'];
                        @endphp
                        <span class="gd-inline-flex gd-items-center gd-gap-xs gd-px-sm gd-py-xs gd-rounded-full gd-label-small {{ $config['class'] }}">
                            <span class="material-symbols-outlined" style="font-size: 14px;">{{ $config['icon'] }}</span>
                            {{ ucfirst($report->status) }}
                        </span>
                    </td>

                    <!-- Gross Sales -->
                    <td class="gd-px-lg gd-py-md">
                        <div class="gd-label-large gd-text-success">${{ number_format($report->gross_sales, 0) }}</div>
                        @if($report->projected_sales)
                        <div class="gd-body-small gd-text-secondary gd-mt-xs">Projected: ${{ number_format($report->projected_sales, 0) }}</div>
                        @endif
                    </td>

                    <!-- Net Sales -->
                    <td class="gd-px-lg gd-py-md">
                        <div class="gd-label-large">${{ number_format($report->net_sales, 0) }}</div>
                        @if($report->total_customers)
                        <div class="gd-body-small gd-text-secondary gd-mt-xs">{{ $report->total_customers }} customers</div>
                        @endif
                    </td>

                    <!-- Created By -->
                    <td class="gd-px-lg gd-py-md">
                        <div class="gd-label-medium">{{ $report->creator->name ?? 'N/A' }}</div>
                        <div class="gd-body-small gd-text-secondary gd-mt-xs">{{ $report->created_at->format('M j, g:i A') }}</div>
                    </td>

                    <!-- Actions -->
                    <td class="gd-px-lg gd-py-md">
                        <div class="gd-flex gd-justify-center gd-gap-xs">
                            <!-- View -->
                            <a href="{{ route('daily-reports.show', $report) }}"
                               class="gd-inline-flex gd-items-center gd-justify-center w-8 h-8 gd-bg-trust gd-text-trust-600 gd-rounded-full hover:gd-bg-trust-100 gd-transition-colors"
                               title="View Report"
                               aria-label="View report for {{ $report->report_date->format('M j, Y') }}">
                                <span class="material-symbols-outlined" style="font-size: 16px;">visibility</span>
                            </a>

                            <!-- Edit (if allowed) -->
                            @if(auth()->user()->hasPermission('manage_reports') && in_array($report->status, ['draft', 'rejected']))
                            <a href="{{ route('daily-reports.edit', $report) }}"
                               class="gd-inline-flex gd-items-center gd-justify-center w-8 h-8 gd-bg-warning gd-text-warning-600 gd-rounded-full hover:gd-bg-warning-100 gd-transition-colors"
                               title="Edit Report"
                               aria-label="Edit report for {{ $report->report_date->format('M j, Y') }}">
                                <span class="material-symbols-outlined" style="font-size: 16px;">edit</span>
                            </a>
                            @endif

                            <!-- Export PDF -->
                            <a href="{{ route('daily-reports.export-pdf', $report) }}"
                               class="gd-inline-flex gd-items-center gd-justify-center w-8 h-8 gd-bg-gray-100 gd-text-gray-600 gd-rounded-full hover:gd-bg-gray-200 gd-transition-colors"
                               title="Export PDF"
                               aria-label="Export PDF for {{ $report->report_date->format('M j, Y') }}">
                                <span class="material-symbols-outlined" style="font-size: 16px;">picture_as_pdf</span>
                            </a>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($reports->hasPages())
    <div class="gd-flex gd-items-center gd-justify-between gd-px-lg gd-py-md gd-bg-surface-1 gd-border-t gd-border-surface-3">
        <div class="gd-body-medium gd-text-secondary">
            Showing {{ $reports->firstItem() }} to {{ $reports->lastItem() }} of {{ $reports->total() }} reports
        </div>
        <div class="gd-flex gd-gap-xs">
            {{ $reports->links('pagination::simple-bootstrap-4') }}
        </div>
    </div>
    @endif
</div>

@else
<!-- Empty State - Emotional Design for Motivation -->
<div class="gd-card">
    <div class="gd-card-body gd-text-center gd-py-3xl">
        <!-- Empty State Icon -->
        <div class="gd-inline-flex gd-items-center gd-justify-center w-24 h-24 gd-bg-trust gd-rounded-full gd-mb-lg">
            <span class="material-symbols-outlined gd-text-trust" style="font-size: 48px;">assessment</span>
        </div>

        <!-- Empty State Content -->
        <h3 class="gd-title-large gd-mb-xs">
            @if(request()->filled('search') || request()->filled('date_from') || request()->filled('store_id'))
                No Reports Match Your Search
            @else
                No Reports Created Yet
            @endif
        </h3>

        <p class="gd-body-large gd-text-secondary gd-mb-xl gd-max-w-md gd-mx-auto">
            @if(request()->filled('search') || request()->filled('date_from') || request()->filled('store_id'))
                Try adjusting your search criteria or create a new report to get started.
            @else
                Get started by creating your first daily report to track your restaurant's performance and gain valuable insights.
            @endif
        </p>

        <!-- Empty State Actions -->
        @if(auth()->user()->hasPermission('create_reports'))
        <div class="gd-flex gd-justify-center gd-gap-md gd-flex-wrap">
            <a href="{{ route('daily-reports.create') }}" class="gd-button gd-button-primary">
                <span class="material-symbols-outlined">add</span>
                Create Your First Report
            </a>
        </div>
        @endif

        @if(request()->filled('search') || request()->filled('date_from') || request()->filled('store_id'))
        <div class="gd-mt-lg">
            <a href="{{ route('daily-reports.index') }}" class="gd-button gd-button-text">
                <span class="material-symbols-outlined">clear_all</span>
                Clear All Filters
            </a>
        </div>
        @endif
    </div>
</div>
@endif

<!-- Floating Action Button for Mobile -->
@if(auth()->user()->hasPermission('create_reports'))
<a href="{{ route('daily-reports.create') }}" class="gd-fab lg:gd-hidden" aria-label="Create new report">
    <span class="material-symbols-outlined">add</span>
</a>
@endif

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Enhanced form interactions with immediate feedback
    const form = document.querySelector('form');
    const inputs = form.querySelectorAll('input, select');

    // Add focus/blur animations for form inputs
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.closest('.gd-relative')?.classList.add('focused');
            this.style.transform = 'translateY(-1px)';
            this.style.boxShadow = '0 0 0 2px rgba(66, 133, 244, 0.2)';
        });

        input.addEventListener('blur', function() {
            this.closest('.gd-relative')?.classList.remove('focused');
            this.style.transform = '';
            this.style.boxShadow = '';
        });
    });

    // Form submission with loading feedback
    form.addEventListener('submit', function() {
        const submitButton = this.querySelector('button[type="submit"]');
        const originalContent = submitButton.innerHTML;

        // Show loading state
        submitButton.innerHTML = '<span class="material-symbols-outlined">search</span> Searching...';
        submitButton.disabled = true;
        submitButton.classList.add('gd-loading');

        // Show immediate feedback
        showToast('Searching reports...', 'info', 2000);
    });

    // Export link feedback
    document.querySelectorAll('a[href*="export"]').forEach(link => {
        link.addEventListener('click', function() {
            const type = this.href.includes('pdf') ? 'PDF' : 'CSV';
            showToast(`Preparing ${type} export...`, 'info', 3000);

            // Add visual feedback
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
    });

    // Table row hover effects with micro-animations
    document.querySelectorAll('tbody tr').forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(2px)';
            this.style.boxShadow = '0 2px 4px rgba(0, 0, 0, 0.05)';
        });

        row.addEventListener('mouseleave', function() {
            this.style.transform = '';
            this.style.boxShadow = '';
        });
    });

    // Action button hover effects
    document.querySelectorAll('[title]').forEach(button => {
        let tooltip;

        button.addEventListener('mouseenter', function() {
            // Create tooltip
            tooltip = document.createElement('div');
            tooltip.className = 'gd-absolute gd-bg-gray-800 gd-text-white gd-px-sm gd-py-xs gd-rounded gd-text-xs gd-z-50 gd-pointer-events-none';
            tooltip.textContent = this.title;
            tooltip.style.bottom = 'calc(100% + 4px)';
            tooltip.style.left = '50%';
            tooltip.style.transform = 'translateX(-50%)';
            tooltip.style.whiteSpace = 'nowrap';

            this.style.position = 'relative';
            this.appendChild(tooltip);

            // Remove native title to prevent double tooltips
            this.setAttribute('data-title', this.title);
            this.removeAttribute('title');

            // Animate in
            setTimeout(() => {
                tooltip.style.opacity = '1';
            }, 100);
        });

        button.addEventListener('mouseleave', function() {
            if (tooltip) {
                tooltip.remove();
            }
            // Restore title
            if (this.hasAttribute('data-title')) {
                this.title = this.getAttribute('data-title');
                this.removeAttribute('data-title');
            }
        });
    });

    // Keyboard navigation enhancement
    document.addEventListener('keydown', function(event) {
        // Quick keyboard shortcuts
        if (event.ctrlKey || event.metaKey) {
            switch(event.key) {
                case 'k':
                case 'f':
                    event.preventDefault();
                    document.getElementById('search')?.focus();
                    break;
                case 'n':
                    event.preventDefault();
                    if (document.querySelector('a[href*="create"]')) {
                        window.location.href = document.querySelector('a[href*="create"]').href;
                    }
                    break;
            }
        }
    });

    // Progressive enhancement: Add search suggestions (future enhancement)
    const searchInput = document.getElementById('search');
    if (searchInput) {
        let debounceTimer;

        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                // Here you could add live search suggestions
                console.log('Search query:', this.value);
            }, 300);
        });
    }

    // Smooth scroll for better UX
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});
</script>
@endpush