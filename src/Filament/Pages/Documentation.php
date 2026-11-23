<?php

namespace ApiMedia\Documentation\Filament\Pages;

use ApiMedia\Documentation\DocumentationPlugin;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Locked;

class Documentation extends Page
{
    protected string $view = 'documentation::documentation';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    #[Locked]
    public ?string $source = null;

    #[Locked]
    public ?string $chapter = null;

    public static function getNavigationLabel(): string
    {
        return __('documentation::documentation.title');
    }

    public function getTitle(): string
    {
        return static::getNavigationLabel();
    }

    public function mount(): void
    {
        // Chapter links load the page, so read identifiers without Livewire's JSON decoding.
        $query = request()->query();
        if (array_key_exists('source', $query) || array_key_exists('chapter', $query)) {
            $source = $query['source'] ?? null;
            $chapter = $query['chapter'] ?? null;
            $this->source = is_string($source) ? $source : '';
            $this->chapter = is_string($chapter) ? $chapter : '';

            return;
        }
        foreach ($this->plugin()->sources() as $source) {
            if ($source->chapters !== []) {
                $this->source = $source->id;
                $this->chapter = array_key_first($source->chapters);
                break;
            }
        }
    }

    protected function getViewData(): array
    {
        $sources = $this->plugin()->sources();
        $chapter = $sources[$this->source]->chapters[$this->chapter] ?? null;

        return ['sources' => $sources, 'current' => $chapter, 'html' => $chapter?->html()];
    }

    private function plugin(): DocumentationPlugin
    {
        $plugin = Filament::getCurrentPanel()->getPlugin('documentation');
        assert($plugin instanceof DocumentationPlugin);

        return $plugin;
    }
}
