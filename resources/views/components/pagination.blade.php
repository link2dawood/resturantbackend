@props(['paginator'])

@if($paginator->hasPages())
    <div class="pagination-wrapper" style="padding: 1.5rem; background: #ffffff; border-top: 1px solid #e0e0e0;">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
            <!-- Results Info -->
            <div class="pagination-info" style="font-family: 'Google Sans', sans-serif; font-size: 0.875rem; color: #5f6368;">
                <span style="font-weight: 500;">Showing</span>
                <span style="font-weight: 600; color: #202124;">{{ $paginator->firstItem() ?? 0 }}</span>
                <span style="font-weight: 500;">to</span>
                <span style="font-weight: 600; color: #202124;">{{ $paginator->lastItem() ?? 0 }}</span>
                <span style="font-weight: 500;">of</span>
                <span style="font-weight: 600; color: #202124;">{{ number_format($paginator->total()) }}</span>
                <span style="font-weight: 500;">results</span>
            </div>

            <!-- Pagination Controls -->
            <nav aria-label="Pagination Navigation" class="pagination-nav">
                <ul class="pagination mb-0" style="gap: 0.25rem;">
                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                            <span class="page-link" style="
                                border: 1px solid #dadce0;
                                border-radius: 8px;
                                padding: 8px 12px;
                                color: #9aa0a6;
                                background: #f8f9fa;
                                font-family: 'Google Sans', sans-serif;
                                font-size: 0.875rem;
                                min-width: 40px;
                                text-align: center;
                            ">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="15 18 9 12 15 6"/>
                                </svg>
                            </span>
                        </li>
                    @else
                        <li class="page-item">
                            <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')" style="
                                border: 1px solid #dadce0;
                                border-radius: 8px;
                                padding: 8px 12px;
                                color: #1a73e8;
                                background: #ffffff;
                                font-family: 'Google Sans', sans-serif;
                                font-size: 0.875rem;
                                min-width: 40px;
                                text-align: center;
                                text-decoration: none;
                                transition: all 0.2s ease;
                            " onmouseover="this.style.background='#e8f0fe'; this.style.borderColor='#1a73e8';" onmouseout="this.style.background='#ffffff'; this.style.borderColor='#dadce0';">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="15 18 9 12 15 6"/>
                                </svg>
                            </a>
                        </li>
                    @endif

                    {{-- Pagination Elements --}}
                    @php
                        // Build page range manually (similar to Laravel's default pagination)
                        $currentPage = $paginator->currentPage();
                        $lastPage = $paginator->lastPage();
                        $onEachSide = 1;
                        $elements = [];
                        
                        if ($lastPage <= ($onEachSide * 2 + 5)) {
                            // Show all pages if we can fit them
                            for ($i = 1; $i <= $lastPage; $i++) {
                                $elements[] = [$i => $paginator->url($i)];
                            }
                        } else {
                            // Show first page
                            $elements[] = [1 => $paginator->url(1)];
                            
                            // Calculate window around current page
                            $windowStart = max(2, $currentPage - $onEachSide);
                            $windowEnd = min($lastPage - 1, $currentPage + $onEachSide);
                            
                            // Add ellipsis if needed before window
                            if ($windowStart > 2) {
                                $elements[] = '...';
                            }
                            
                            // Add pages in window
                            for ($i = $windowStart; $i <= $windowEnd; $i++) {
                                $elements[] = [$i => $paginator->url($i)];
                            }
                            
                            // Add ellipsis if needed after window
                            if ($windowEnd < $lastPage - 1) {
                                $elements[] = '...';
                            }
                            
                            // Show last page
                            $elements[] = [$lastPage => $paginator->url($lastPage)];
                        }
                    @endphp
                    @foreach ($elements as $element)
                            {{-- "Three Dots" Separator --}}
                            @if (is_string($element))
                                <li class="page-item disabled" aria-disabled="true">
                                    <span class="page-link" style="
                                        border: 1px solid #dadce0;
                                        border-radius: 8px;
                                        padding: 8px 12px;
                                        color: #9aa0a6;
                                        background: #f8f9fa;
                                        font-family: 'Google Sans', sans-serif;
                                        font-size: 0.875rem;
                                        min-width: 40px;
                                        text-align: center;
                                    ">{{ $element }}</span>
                                </li>
                            @endif

                            {{-- Array Of Links --}}
                            @if (is_array($element))
                                @foreach ($element as $page => $url)
                                    @if ($page == $paginator->currentPage())
                                        <li class="page-item active" aria-current="page">
                                            <span class="page-link" style="
                                                border: 1px solid #1a73e8;
                                                border-radius: 8px;
                                                padding: 8px 12px;
                                                color: #ffffff;
                                                background: #1a73e8;
                                                font-family: 'Google Sans', sans-serif;
                                                font-size: 0.875rem;
                                                font-weight: 500;
                                                min-width: 40px;
                                                text-align: center;
                                                box-shadow: 0 1px 2px 0 rgba(60, 64, 67, 0.3), 0 1px 3px 1px rgba(60, 64, 67, 0.15);
                                            ">{{ $page }}</span>
                                        </li>
                                    @else
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $url }}" style="
                                                border: 1px solid #dadce0;
                                                border-radius: 8px;
                                                padding: 8px 12px;
                                                color: #1a73e8;
                                                background: #ffffff;
                                                font-family: 'Google Sans', sans-serif;
                                                font-size: 0.875rem;
                                                min-width: 40px;
                                                text-align: center;
                                                text-decoration: none;
                                                transition: all 0.2s ease;
                                            " onmouseover="this.style.background='#e8f0fe'; this.style.borderColor='#1a73e8';" onmouseout="this.style.background='#ffffff'; this.style.borderColor='#dadce0';">{{ $page }}</a>
                                        </li>
                                    @endif
                                @endforeach
                            @endif
                        @endforeach

                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <li class="page-item">
                            <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')" style="
                                border: 1px solid #dadce0;
                                border-radius: 8px;
                                padding: 8px 12px;
                                color: #1a73e8;
                                background: #ffffff;
                                font-family: 'Google Sans', sans-serif;
                                font-size: 0.875rem;
                                min-width: 40px;
                                text-align: center;
                                text-decoration: none;
                                transition: all 0.2s ease;
                            " onmouseover="this.style.background='#e8f0fe'; this.style.borderColor='#1a73e8';" onmouseout="this.style.background='#ffffff'; this.style.borderColor='#dadce0';">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="9 18 15 12 9 6"/>
                                </svg>
                            </a>
                        </li>
                    @else
                        <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                            <span class="page-link" style="
                                border: 1px solid #dadce0;
                                border-radius: 8px;
                                padding: 8px 12px;
                                color: #9aa0a6;
                                background: #f8f9fa;
                                font-family: 'Google Sans', sans-serif;
                                font-size: 0.875rem;
                                min-width: 40px;
                                text-align: center;
                            ">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="9 18 15 12 9 6"/>
                                </svg>
                            </span>
                        </li>
                    @endif
                </ul>
            </nav>
        </div>
    </div>
@endif

