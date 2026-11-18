@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; margin-top: 1.25rem; margin-bottom: 0.75rem;">
        @if ($paginator->onFirstPage())
            <span style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; border: 1px solid #e2e8f0; background: #f8fafc; color: #94a3b8; cursor: not-allowed; font-size: 0.9rem;">‹</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; color: #334155; text-decoration: none; font-size: 0.9rem; font-weight: 700;">‹</a>
        @endif

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; color: #334155; text-decoration: none; font-size: 0.9rem; font-weight: 700;">›</a>
        @else
            <span style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; border: 1px solid #e2e8f0; background: #f8fafc; color: #94a3b8; cursor: not-allowed; font-size: 0.9rem;">›</span>
        @endif
    </nav>
@endif
