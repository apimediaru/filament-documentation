<x-filament-panels::page>
    <style>
        .documentation-layout { display: grid; gap: 1.5rem; align-items: start; }
        @media (min-width: 1024px) { .documentation-layout { grid-template-columns: 16rem minmax(0, 1fr); } }
        @media (min-width: 1024px) { .documentation-nav { position: sticky; top: 6rem; } }

        .documentation-nav > .fi-section + .fi-section { margin-top: 1rem; }
        .documentation-nav ul { list-style: none; margin: 0; padding: 0; }
        .documentation-nav a {
            display: block; width: 100%; text-align: start;
            padding: .375rem .5rem; border-radius: .5rem;
            font-size: .875rem; line-height: 1.35;
            background: none; border: 0; cursor: pointer; text-decoration: none;
            color: var(--gray-600);
        }
        .documentation-nav a:hover { background: rgba(127, 127, 127, .12); }
        .documentation-nav a[aria-current="page"] {
            background: rgba(127, 127, 127, .14);
            color: var(--primary-600);
            font-weight: 600;
        }
        .dark .documentation-nav a { color: var(--gray-400); }
        .dark .documentation-nav a[aria-current="page"] { color: var(--primary-400); }

        .documentation { font-size: .875rem; line-height: 1.65; color: var(--gray-700); }
        .dark .documentation { color: var(--gray-300); }
        .documentation :where(h1) { font-size: 1.5rem; font-weight: 700; margin: 0 0 1rem; line-height: 1.3; }
        .documentation :where(h2) { font-size: 1.2rem; font-weight: 600; margin: 2rem 0 .75rem; line-height: 1.35; }
        .documentation :where(h3) { font-size: 1.05rem; font-weight: 600; margin: 1.5rem 0 .5rem; }
        .documentation :where(h1, h2, h3) { color: var(--gray-950); }
        .dark .documentation :where(h1, h2, h3) { color: var(--gray-50); }
        .documentation :where(p, ul, ol, blockquote) { margin: 0 0 1rem; }
        .documentation :where(ul) { list-style: disc; padding-inline-start: 1.5rem; }
        .documentation :where(ol) { list-style: decimal; padding-inline-start: 1.5rem; }
        .documentation :where(li) { margin: .25rem 0; }
        .documentation :where(strong) { font-weight: 600; color: var(--gray-950); }
        .dark .documentation :where(strong) { color: var(--gray-50); }
        .documentation :where(a) { color: var(--primary-600); text-decoration: underline; }
        .documentation :where(code) { font-size: .875em; padding: .1em .35em; border-radius: .25rem; background: rgba(127, 127, 127, .18); }
        .documentation :where(pre) { overflow-x: auto; padding: .875rem 1rem; border-radius: .5rem; background: rgba(127, 127, 127, .14); margin: 0 0 1rem; }
        .documentation :where(pre code) { background: none; padding: 0; }
        .documentation :where(blockquote) { border-inline-start: 3px solid rgba(127, 127, 127, .4); padding-inline-start: .875rem; }
        .documentation :where(hr) { border: 0; border-top: 1px solid rgba(127, 127, 127, .3); margin: 2rem 0; }
        /* Таблицы уезжают за край на узком экране, а не ломают страницу. */
        .documentation-table-scroll { overflow-x: auto; margin: 0 0 1rem; }
        .documentation :where(table) { width: 100%; border-collapse: collapse; }
        .documentation :where(th, td) { border: 1px solid rgba(127, 127, 127, .3); padding: .4rem .6rem; text-align: start; vertical-align: top; }
        .documentation :where(th) { font-weight: 600; }
    </style>

    <div class="documentation-layout">
        <nav class="documentation-nav" aria-label="{{ __('documentation::documentation.title') }}">
            @foreach ($sources as $guide)
                <x-filament::section :heading="$guide->title" compact>
                    <ul>
                        @foreach ($guide->chapters as $entry)
                            <li>
                                <a href="{{ static::getUrl(['source' => $guide->id, 'chapter' => $entry->slug]) }}"
                                   @if ($source === $guide->id && $chapter === $entry->slug) aria-current="page" @endif>
                                    {{ $entry->title }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </x-filament::section>
            @endforeach
        </nav>
        <x-filament::section>
            @if ($current === null)
                <p>{{ __($source !== null || $chapter !== null ? 'documentation::documentation.missing' : 'documentation::documentation.empty') }}</p>
            @else
                @if ($current->fallback)
                    <p role="status">{{ __('documentation::documentation.fallback') }}</p>
                @endif
                <div class="documentation" lang="{{ $current->fallback ? 'ru' : app()->getLocale() }}"
                    x-data x-init="$el.querySelectorAll('table').forEach(table => {
                        const wrap = document.createElement('div');
                        wrap.className = 'documentation-table-scroll';
                        table.replaceWith(wrap);
                        wrap.appendChild(table);
                    })">{!! $html !!}</div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
