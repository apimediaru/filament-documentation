<?php

namespace ApiMedia\Documentation\Tests;

use ApiMedia\Documentation\DocumentationCatalog;
use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;

class CatalogTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir().'/documentation-'.bin2hex(random_bytes(8));
        mkdir($this->root.'/docs/ru', 0777, true);
        mkdir($this->root.'/docs/en', 0777, true);
        file_put_contents($this->root.'/composer.json', json_encode(['extra' => ['apimediaru' => ['documentation' => [
            'id' => 'site', 'title' => ['ru' => 'Сайт', 'en' => 'Site'], 'path' => 'docs',
        ]]]], JSON_THROW_ON_ERROR));
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->root);
        parent::tearDown();
    }

    private function catalog(): DocumentationCatalog
    {
        return new DocumentationCatalog($this->root, []);
    }

    #[Test]
    public function reads_translations_and_fallback_with_stable_addresses(): void
    {
        file_put_contents($this->root.'/docs/ru/02-library.md', "# Библиотека\n\nТекст");
        $chapter = $this->catalog()->chapter('site', 'library', 'en');
        $this->assertSame('Библиотека', $chapter->title);
        $this->assertTrue($chapter->fallback);
        rename($this->root.'/docs/ru/02-library.md', $this->root.'/docs/ru/12-library.md');
        file_put_contents($this->root.'/docs/en/12-library.md', "# Library\n\nEnglish");
        $chapter = $this->catalog()->chapter('site', 'library', 'en');
        $this->assertSame('Library', $chapter->title);
        $this->assertFalse($chapter->fallback);
    }

    #[Test]
    public function removal_and_exclusion_use_the_same_catalog(): void
    {
        $path = $this->root.'/docs/ru/01-library.md';
        file_put_contents($path, '# Библиотека');
        $this->assertNotNull($this->catalog()->chapter('site', 'library', 'ru'));
        $this->assertNull($this->catalog()->chapter('site', 'library', 'ru', ['site']));
        unlink($path);
        $this->assertNull($this->catalog()->chapter('site', 'library', 'ru'));
        $this->assertNull($this->catalog()->chapter('site', '../../composer', 'ru'));
    }

    #[Test]
    public function sorts_numeric_prefixes_and_sanitizes_markdown(): void
    {
        file_put_contents($this->root.'/docs/ru/10-last.md', '# Last');
        file_put_contents($this->root.'/docs/ru/2-first.md', "# First\n\n<img src=x onerror=alert(1)>\n\n<script>alert(2)</script>");
        $source = $this->catalog()->sources('ru')['site'];
        $this->assertSame(['first', 'last'], array_keys($source->chapters));
        $html = $source->chapters['first']->html();
        $this->assertStringNotContainsString('onerror', $html);
        $this->assertStringNotContainsString('<script>alert', $html);
    }

    #[Test]
    public function rejects_duplicate_chapters_in_a_language(): void
    {
        file_put_contents($this->root.'/docs/ru/01-library.md', '# One');
        file_put_contents($this->root.'/docs/ru/02-library.md', '# Two');
        $this->expectException(InvalidArgumentException::class);
        $this->catalog()->sources('ru');
    }

    #[Test]
    public function supports_cyrillic_filenames_like_the_original_viewer(): void
    {
        file_put_contents($this->root.'/docs/ru/03-Работа_с_блоками.md', '# Работа с блоками');
        $chapter = $this->catalog()->chapter('site', 'rabota-s-blokami', 'ru');
        $this->assertSame('Работа с блоками', $chapter->title);
    }

    #[Test]
    public function rejects_chapter_symlinks_outside_the_guide(): void
    {
        file_put_contents($this->root.'/secret.md', '# Secret');
        symlink($this->root.'/secret.md', $this->root.'/docs/ru/01-secret.md');
        $this->expectException(InvalidArgumentException::class);
        $this->catalog()->sources('ru');
    }

    #[Test]
    public function discovers_two_packages_and_keeps_site_first(): void
    {
        $packages = [];
        foreach (['second' => 'Бета', 'first' => 'Альфа'] as $id => $title) {
            $path = $this->root.'/'.$id;
            mkdir($path.'/docs/ru', 0777, true);
            file_put_contents($path.'/composer.json', json_encode(['extra' => ['apimediaru' => ['documentation' => [
                'id' => $id, 'title' => ['ru' => $title], 'path' => 'docs',
            ]]]], JSON_THROW_ON_ERROR));
            file_put_contents($path.'/docs/ru/01-library.md', '# '.$title);
            $packages[$id] = $path;
        }
        $catalog = new DocumentationCatalog($this->root, $packages);
        $this->assertSame(['site', 'first', 'second'], array_keys($catalog->sources('ru')));
        $this->assertSame('Альфа', $catalog->chapter('first', 'library', 'ru')->title);
        $this->assertSame('Бета', $catalog->chapter('second', 'library', 'ru')->title);
    }
}
