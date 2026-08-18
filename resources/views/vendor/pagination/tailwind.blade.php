@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('pagination.navigation') }}" class="flex justify-center">
        <ul class="flex flex-wrap items-center justify-center gap-xs text-label-md">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li>
                    <span class="inline-flex min-h-10 items-center rounded-lg border border-outline-variant/60
                                 px-sm text-on-surface-variant/50" aria-disabled="true">
                        {{ __('pagination.previous') }}
                    </span>
                </li>
            @else
                <li>
                    <a href="{{ $paginator->previousPageUrl() }}"
                       class="inline-flex min-h-10 items-center rounded-lg border border-secondary-fixed-dim
                              bg-secondary-fixed px-sm text-on-secondary-fixed transition-all duration-200
                              hover:bg-primary hover:text-on-primary hover:border-primary">
                        {{ __('pagination.previous') }}
                    </a>
                </li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="px-xs text-on-surface-variant">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span class="inline-flex size-10 items-center justify-center rounded-lg border
                                             border-secondary bg-secondary text-on-secondary" aria-current="page">
                                    {{ $page }}
                                </span>
                            @else
                                <a href="{{ $url }}"
                                   class="inline-flex size-10 items-center justify-center rounded-lg border
                                          border-secondary-fixed-dim bg-secondary-fixed text-on-secondary-fixed
                                          transition-all duration-200 hover:border-primary hover:bg-primary
                                          hover:text-on-primary">
                                    {{ $page }}
                                </a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li>
                    <a href="{{ $paginator->nextPageUrl() }}"
                       class="inline-flex min-h-10 items-center rounded-lg border border-secondary-fixed-dim
                              bg-secondary-fixed px-sm text-on-secondary-fixed transition-all duration-200
                              hover:bg-primary hover:text-on-primary hover:border-primary">
                        {{ __('pagination.next') }}
                    </a>
                </li>
            @else
                <li>
                    <span class="inline-flex min-h-10 items-center rounded-lg border border-outline-variant/60
                                 px-sm text-on-surface-variant/50" aria-disabled="true">
                        {{ __('pagination.next') }}
                    </span>
                </li>
            @endif
        </ul>
    </nav>
@endif
