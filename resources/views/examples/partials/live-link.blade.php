{{-- Demo only: a live link in the step page's header (resources/js/page-tabs.js keeps its pill current) --}}
<a href="#{{ $link['kind'] === 'endpoint' ? 'requests' : 'stored' }}" data-live="{{ $link['kind'] }}" data-label="{{ $link['label'] }}"
   class="flex h-8 shrink-0 items-center gap-1.5 whitespace-nowrap rounded-md px-2 font-mono text-xs text-gray-700 no-underline hover:bg-gray-100 hover:text-gray-900">
    @if ($link['kind'] === 'stored')
        <svg aria-hidden="true" class="size-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v14c0 1.7 3.6 3 8 3s8-1.3 8-3V5"/><path d="M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3"/></svg>
    @endif
    <span>{{ $link['label'] }}</span>
    <span data-pill data-kind="none" class="rounded-full px-2 font-sans text-[11.5px] font-semibold data-[kind=error]:bg-red-100 data-[kind=error]:text-red-800 data-[kind=none]:bg-gray-100 data-[kind=none]:text-gray-500 data-[kind=ok]:bg-emerald-50 data-[kind=ok]:text-emerald-800">{{ $link['kind'] === 'endpoint' ? '–' : 'unchanged' }}</span>
</a>
