@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.5rem; margin-top: 1.25rem; margin-bottom: 0.75rem; width: 100%;">
        <div style="display: flex; align-items: center; justify-content: center; gap: 0.35rem; flex-wrap: wrap;">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; border: 1px solid #e2e8f0; background: #f8fafc; color: #94a3b8; cursor: not-allowed; font-size: 0.9rem;" aria-disabled="true">
                    ‹
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; color: #334155; text-decoration: none; font-size: 0.9rem; font-weight: 700; transition: all 0.15s ease;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#ffffff'">
                    ‹
                </a>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; color: #94a3b8; font-size: 0.85rem;">{{ $element }}</span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span style="display: inline-flex; align-items: center; justify-content: center; min-width: 32px; height: 32px; padding: 0 0.5rem; border-radius: 8px; background: #2563eb; color: #ffffff; font-size: 0.85rem; font-weight: 700; border: 1px solid #2563eb;" aria-current="page">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" style="display: inline-flex; align-items: center; justify-content: center; min-width: 32px; height: 32px; padding: 0 0.5rem; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: #334155; text-decoration: none; font-size: 0.85rem; font-weight: 500; transition: all 0.15s ease;" onmouseover="this.style.background='#f1f5f9'; this.style.borderColor='#cbd5e1'" onmouseout="this.style.background='#ffffff'; this.style.borderColor='#e2e8f0'">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; color: #334155; text-decoration: none; font-size: 0.9rem; font-weight: 700; transition: all 0.15s ease;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#ffffff'">
                    ›
                </a>
            @else
                <span style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; border: 1px solid #e2e8f0; background: #f8fafc; color: #94a3b8; cursor: not-allowed; font-size: 0.9rem;" aria-disabled="true">
                    ›
                </span>
            @endif
        </div>

        {{-- Compact Results Summary --}}
        <div style="font-size: 0.78rem; color: #64748b;">
            Menampilkan <span style="font-weight: 600; color: #1e293b;">{{ $paginator->firstItem() ?? 0 }}</span> - <span style="font-weight: 600; color: #1e293b;">{{ $paginator->lastItem() ?? 0 }}</span> dari <span style="font-weight: 600; color: #1e293b;">{{ $paginator->total() }}</span> data
        </div>
    </nav>
@endif
