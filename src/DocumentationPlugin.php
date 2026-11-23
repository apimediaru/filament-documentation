<?php

namespace ApiMedia\Documentation;

use ApiMedia\Documentation\Filament\Pages\Documentation;
use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

final class DocumentationPlugin implements Plugin
{
    /** @var list<string> */
    private array $excluded = [];

    public static function make(): self
    {
        return new self;
    }

    public function getId(): string
    {
        return 'documentation';
    }

    /** @param list<string> $ids */
    public function withoutSources(array $ids): self
    {
        $this->excluded = $ids;

        return $this;
    }

    /** @return array<string, DocumentationSource> */
    public function sources(): array
    {
        return app(DocumentationCatalog::class)->sources(app()->getLocale(), $this->excluded);
    }

    public function chapter(string $source, string $chapter): ?DocumentationChapter
    {
        return $this->sources()[$source]->chapters[$chapter] ?? null;
    }

    public static function url(string $source, string $chapter, ?string $panelId = null): ?string
    {
        $panel = $panelId === null ? Filament::getCurrentPanel() : (Filament::getPanels()[$panelId] ?? null);
        if ($panel === null || ! $panel->hasPlugin('documentation')) {
            return null;
        }
        $user = $panel->auth()->user();
        if ($user === null) {
            return null;
        }

        $canAccessPanel = $user instanceof FilamentUser
            ? $user->canAccessPanel($panel)
            : config('app.env') === 'local';
        if (! $canAccessPanel) {
            return null;
        }
        $plugin = $panel->getPlugin('documentation');
        if (! $plugin instanceof self || $plugin->chapter($source, $chapter) === null) {
            return null;
        }

        return Documentation::getUrl(['source' => $source, 'chapter' => $chapter], panel: $panel->getId());
    }

    public function register(Panel $panel): void
    {
        $panel->pages([Documentation::class]);
    }

    public function boot(Panel $panel): void {}
}
