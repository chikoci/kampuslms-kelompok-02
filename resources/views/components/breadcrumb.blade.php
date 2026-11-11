@props(['items' => []])

@if (!empty($items))
    <nav class="breadcrumb-nav" aria-label="Breadcrumb">
        <ol class="breadcrumb-list">
            @foreach ($items as $index => $item)
                @php
                    $isLast = $index === count($items) - 1;
                    $label = is_array($item) ? ($item['label'] ?? '') : $item;
                    $url = is_array($item) ? ($item['url'] ?? null) : null;
                @endphp

                <li class="breadcrumb-item {{ $isLast ? 'active' : '' }}" {{ $isLast ? 'aria-current=page' : '' }}>
                    @if (!$isLast && !empty($url))
                        <a href="{{ $url }}" class="breadcrumb-link">
                            @if ($index === 0)
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="breadcrumb-home-icon">
                                    <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                    <polyline points="9 22 9 12 15 12 15 22"/>
                                </svg>
                            @endif
                            <span>{{ $label }}</span>
                        </a>
                        <span class="breadcrumb-separator" aria-hidden="true">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9 18 15 12 9 6"/>
                            </svg>
                        </span>
                    @else
                        @if ($index === 0)
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="breadcrumb-home-icon">
                                <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                <polyline points="9 22 9 12 15 12 15 22"/>
                            </svg>
                        @endif
                        <span class="breadcrumb-current">{{ $label }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif

