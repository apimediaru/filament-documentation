<?php

namespace ApiMedia\Documentation;

use Collator;
use Composer\InstalledVersions;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class DocumentationCatalog
{
    /** @param array<string, string>|null $packagePaths */
    public function __construct(
        private ?string $rootPath = null,
        private ?array $packagePaths = null,
    ) {}

    /** @return array<string, DocumentationSource> */
    public function sources(string $locale, array $excluded = []): array
    {
        $root = realpath($this->rootPath ?? base_path());
        $paths = $this->packagePaths ?? $this->installedPaths();
        $paths = ['@root' => $root, ...$paths];
        $sources = [];
        $seen = [];

        foreach ($paths as $package => $path) {
            $base = realpath($path);
            if ($base === false || isset($seen[$base])) {
                continue;
            }
            $seen[$base] = true;
            $manifest = $base.'/composer.json';
            if (! is_file($manifest)) {
                continue;
            }
            $metadata = json_decode(file_get_contents($manifest), true, flags: JSON_THROW_ON_ERROR);
            $definition = $metadata['extra']['apimediaru']['documentation'] ?? null;
            if ($definition === null) {
                continue;
            }
            if (! is_array($definition)
                || ! is_string($definition['id'] ?? null)
                || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $definition['id'])
                || ! is_array($definition['title'] ?? null)
                || ! is_string($definition['title']['ru'] ?? null)
                || ! is_string($definition['path'] ?? null)) {
                throw new InvalidArgumentException("Invalid documentation declaration in {$package}.");
            }
            $id = $definition['id'];
            if (isset($sources[$id])) {
                throw new InvalidArgumentException("Duplicate documentation source {$id} in {$package}.");
            }
            $directory = realpath($base.'/'.$definition['path']);
            if ($directory === false || ! is_dir($directory) || ! str_starts_with($directory, $base.DIRECTORY_SEPARATOR)) {
                throw new InvalidArgumentException("Invalid documentation directory in {$package}.");
            }
            $title = $definition['title'][$locale] ?? $definition['title']['ru'];
            if (! is_string($title) || trim($title) === '') {
                throw new InvalidArgumentException("Invalid documentation title in {$package}.");
            }
            $sources[$id] = new DocumentationSource(
                $id, $title, $base === $root,
                $this->chapters($directory, $locale),
            );
        }

        $sources = array_diff_key($sources, array_fill_keys($excluded, true));
        $collator = new Collator($locale);
        uasort($sources, static function (DocumentationSource $a, DocumentationSource $b) use ($collator): int {
            return ($b->site <=> $a->site)
                ?: ($collator->compare($a->title, $b->title) ?: strcmp($a->id, $b->id));
        });

        return $sources;
    }

    public function chapter(string $source, string $chapter, string $locale, array $excluded = []): ?DocumentationChapter
    {
        return $this->sources($locale, $excluded)[$source]->chapters[$chapter] ?? null;
    }

    /** @return array<string, string> */
    private function installedPaths(): array
    {
        $paths = [];
        foreach (InstalledVersions::getInstalledPackages() as $package) {
            $path = InstalledVersions::getInstallPath($package);
            if ($path !== null) {
                $paths[$package] = $path;
            }
        }

        return $paths;
    }

    /** @return array<string, DocumentationChapter> */
    private function chapters(string $directory, string $locale): array
    {
        $translations = [];
        foreach (['ru', 'en'] as $language) {
            foreach (glob($directory.'/'.$language.'/*.md') ?: [] as $path) {
                $resolved = realpath($path);
                if ($resolved === false || ! str_starts_with($resolved, $directory.DIRECTORY_SEPARATOR)) {
                    throw new InvalidArgumentException("Documentation file escapes its source: {$path}");
                }
                $filename = basename($path, '.md');
                $ordered = preg_match('/^(\d+)[-_](.+)$/D', $filename, $matches);
                $slug = Str::slug($ordered ? $matches[2] : $filename);
                if ($slug === '') {
                    throw new InvalidArgumentException("Invalid documentation filename: {$path}");
                }
                if (isset($translations[$slug][$language])) {
                    throw new InvalidArgumentException("Duplicate documentation chapter: {$path}");
                }
                if (! preg_match('/^#[ \t]+(.+)$/m', file_get_contents($resolved), $heading) || trim($heading[1]) === '') {
                    throw new InvalidArgumentException("Missing chapter heading: {$path}");
                }
                $translations[$slug][$language] = [
                    'path' => $resolved, 'filename' => $filename,
                    'title' => trim($heading[1]), 'order' => $ordered ? (int) $matches[1] : 0,
                ];
            }
        }
        $chapters = [];
        foreach ($translations as $slug => $languages) {
            if (isset($languages['ru'], $languages['en']) && $languages['ru']['filename'] !== $languages['en']['filename']) {
                throw new InvalidArgumentException("Mismatched documentation translations: {$directory}/{$slug}");
            }
            $language = $locale === 'en' && isset($languages['en']) ? 'en' : 'ru';
            $file = $languages[$language] ?? null;
            if ($file === null) {
                continue;
            }
            $chapters[$slug] = new DocumentationChapter(
                $slug, $file['title'], $file['path'], $file['order'], $locale === 'en' && $language === 'ru',
            );
        }
        uasort($chapters, static fn (DocumentationChapter $a, DocumentationChapter $b): int => ($a->order <=> $b->order) ?: strcmp($a->slug, $b->slug));

        return $chapters;
    }
}
