@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-between">
        <div>
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span class="relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-neutral-400 bg-neutral-900 border border-neutral-800 cursor-default leading-5 rounded-md">
                    {!! __('pagination.previous') !!}
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-neutral-300 bg-neutral-800 border border-neutral-700 leading-5 rounded-md hover:bg-neutral-750 hover:text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-neutral-900 focus:ring-indigo-500 active:bg-neutral-750 transition ease-in-out duration-150">
                    {!! __('pagination.previous') !!}
                </a>
            @endif
        </div>

        {{-- Page Number Information (Optional but good for UX) --}}
        <div class="hidden sm:flex sm:items-center">
            <p class="text-sm text-neutral-400">
                {!! __('Ukazujem') !!}
                @if ($paginator->firstItem())
                    <span class="font-medium text-neutral-200">{{ $paginator->firstItem() }}</span>
                    {!! __('do') !!}
                    <span class="font-medium text-neutral-200">{{ $paginator->lastItem() }}</span>
                @else
                    {{ $paginator->count() }}
                @endif
                {!! __('z') !!}
                <span class="font-medium text-neutral-200">{{ $paginator->total() }}</span>
                {!! __('výsledkov') !!}
            </p>
        </div>

        <div>
            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-neutral-300 bg-neutral-800 border border-neutral-700 leading-5 rounded-md hover:bg-neutral-750 hover:text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-neutral-900 focus:ring-indigo-500 active:bg-neutral-750 transition ease-in-out duration-150">
                    {!! __('pagination.next') !!}
                </a>
            @else
                <span class="relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-neutral-400 bg-neutral-900 border border-neutral-800 cursor-default leading-5 rounded-md">
                    {!! __('pagination.next') !!}
                </span>
            @endif
        </div>
    </nav>
@endif
