<?php

namespace ApiMedia\Documentation\Tests;

use ApiMedia\Documentation\DocumentationCatalog;
use ApiMedia\Documentation\DocumentationPlugin;
use ApiMedia\Documentation\Filament\Pages\Documentation;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Auth\User;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class PageTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir().'/documentation-page-'.bin2hex(random_bytes(8));
        mkdir($this->root.'/docs/ru', 0777, true);
        mkdir($this->root.'/docs/en', 0777, true);
        $this->declareSource('site');
        file_put_contents($this->root.'/docs/ru/01-intro.md', "# Введение\n\nТекст инструкции");
        $this->app->instance(DocumentationCatalog::class, new DocumentationCatalog($this->root, []));
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
        $this->actingAs(new Reader(['id' => 1, 'name' => 'Reader', 'email' => 'reader@example.test', 'remember_token' => null, 'allowed' => true]));
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->root);
        parent::tearDown();
    }

    private function declareSource(string $id): void
    {
        file_put_contents($this->root.'/composer.json', json_encode(['extra' => ['apimediaru' => ['documentation' => [
            'id' => $id, 'title' => ['ru' => 'Сайт', 'en' => 'Site'], 'path' => 'docs',
        ]]]], JSON_THROW_ON_ERROR));
    }

    #[Test]
    public function opens_first_chapter_and_generates_shareable_links(): void
    {
        Livewire::test(Documentation::class)->assertSuccessful()
            ->assertSet('source', 'site')->assertSet('chapter', 'intro')->assertSee('Текст инструкции');
        $url = DocumentationPlugin::url('site', 'intro');
        $this->assertStringContainsString('source=site', $url);
        $this->assertStringContainsString('chapter=intro', $url);
    }

    public static function scalar_like_slugs(): iterable
    {
        yield ['true'];
        yield ['false'];
        yield ['1e3'];
    }

    #[Test]
    #[DataProvider('scalar_like_slugs')]
    public function generated_links_preserve_scalar_like_source_and_chapter_names(string $slug): void
    {
        $this->declareSource($slug);
        file_put_contents($this->root.'/docs/ru/02-'.$slug.'.md', "# Глава\n\nТекст выбранной главы");

        $url = DocumentationPlugin::url($slug, $slug);
        $this->assertNotNull($url);
        $this->get($url)->assertOk()->assertSee('Текст выбранной главы');
    }

    #[Test]
    public function malformed_chapter_parameter_shows_the_missing_state(): void
    {
        $this->get(Documentation::getUrl(['chapter' => ['intro']]))
            ->assertOk()->assertSee('Глава не найдена')->assertDontSee('Текст инструкции');
    }

    #[Test]
    public function missing_explicit_chapter_does_not_redirect_to_first(): void
    {
        Livewire::withQueryParams(['source' => 'site', 'chapter' => 'removed'])
            ->test(Documentation::class)->assertSee('Глава не найдена')->assertDontSee('Текст инструкции');
        $this->assertNull(DocumentationPlugin::url('site', 'removed'));
    }

    #[Test]
    public function switching_language_keeps_the_chapter_and_uses_fallback(): void
    {
        Livewire::test(Documentation::class)->assertSet('chapter', 'intro');
        $this->app->setLocale('en');
        Livewire::withQueryParams(['source' => 'site', 'chapter' => 'intro'])->test(Documentation::class)->assertSet('chapter', 'intro')
            ->assertSee('An English translation is not available yet.')->assertSee('Текст инструкции');
        file_put_contents($this->root.'/docs/en/01-intro.md', "# Introduction\n\nEnglish instructions");
        Livewire::withQueryParams(['source' => 'site', 'chapter' => 'intro'])->test(Documentation::class)
            ->assertSee('English instructions')->assertDontSee('Текст инструкции');
        $this->app->setLocale('ru');
        Livewire::withQueryParams(['source' => 'site', 'chapter' => 'intro'])->test(Documentation::class)
            ->assertSee('Текст инструкции')->assertDontSee('English instructions');
    }

    #[Test]
    public function excluded_guide_disappears_from_menu_and_links(): void
    {
        Filament::getCurrentPanel()->getPlugin('documentation')->withoutSources(['site']);
        Livewire::test(Documentation::class)->assertSee('Руководства пока не добавлены.');
        $this->assertNull(DocumentationPlugin::url('site', 'intro'));
    }

    #[Test]
    public function package_update_is_visible_without_a_persistent_copy(): void
    {
        $component = Livewire::test(Documentation::class);
        file_put_contents($this->root.'/docs/ru/01-intro.md', "# Введение\n\nОбновлённая инструкция");
        $component->call('$refresh')->assertSee('Обновлённая инструкция')->assertDontSee('Текст инструкции');
        unlink($this->root.'/docs/ru/01-intro.md');
        $component->call('$refresh')->assertSee('Глава не найдена');
        $this->assertNull(DocumentationPlugin::url('site', 'intro'));
    }

    #[Test]
    public function links_are_hidden_when_panel_has_no_plugin_or_user(): void
    {
        Filament::setCurrentPanel(null);
        $this->assertNull(DocumentationPlugin::url('site', 'intro'));
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::auth()->logout();
        $this->assertNull(DocumentationPlugin::url('site', 'intro'));
        $this->get(Documentation::getUrl())->assertRedirect();
    }

    #[Test]
    public function panel_authorization_applies_to_routes_and_links(): void
    {
        $this->get(Documentation::getUrl())->assertOk()->assertSee('Текст инструкции');
        $this->actingAs(new Reader(['id' => 2, 'name' => 'Denied', 'allowed' => false]));
        $this->assertNull(DocumentationPlugin::url('site', 'intro'));
        $this->get(Documentation::getUrl())->assertForbidden();
    }

    #[Test]
    public function links_follow_filaments_local_exception_for_users_without_the_panel_contract(): void
    {
        $this->actingAs((new User)->forceFill(['id' => 3, 'name' => 'Reader']));
        $url = Documentation::getUrl(['source' => 'site', 'chapter' => 'intro']);

        config(['app.env' => 'local']);
        $this->assertSame($url, DocumentationPlugin::url('site', 'intro', 'admin'));
        $this->get($url)->assertOk();

        config(['app.env' => 'production']);
        $this->get($url)->assertForbidden();
        $this->assertNull(DocumentationPlugin::url('site', 'intro', 'admin'));
    }

    #[Test]
    public function another_panel_does_not_inherit_the_documentation_plugin(): void
    {
        Filament::registerPanel(Panel::make()->id('other')->path('other'));
        $this->assertNull(DocumentationPlugin::url('site', 'intro', 'other'));
        $this->assertNull(DocumentationPlugin::url('site', 'intro', 'unknown'));
        $this->assertNotNull(DocumentationPlugin::url('site', 'intro', 'admin'));
    }
}
