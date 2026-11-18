<?php

namespace ApiMedia\Documentation\Filament\Pages;

use ApiMedia\Documentation\DocumentationPlugin;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Url;

class Documentation extends Page
{
    protected string $view = 'documentation::documentation';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    #[Url]
    public ?string $source = null;

    #[Url]
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
        if ($this->source !== null || $this->chapter !== null) {
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
