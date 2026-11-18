# Filament Documentation

Руководства заказчика в Markdown для Laravel 13 и Filament 5.
Namespace `ApiMedia\Documentation`. PHP 8.3+ и intl.

## Установка

```bash
composer require apimediaru/filament-documentation:^0.1
```

Добавьте plugin в выбранную панель.

```php
use ApiMedia\Documentation\DocumentationPlugin;

return $panel->plugin(DocumentationPlugin::make());
```

Раздел доступен пользователям, допущенным в эту панель. Пакет использует
её auth middleware и остаётся независимым от ролей приложения.

## Объявление руководства

Добавьте в composer.json приложения или пакета следующие метаданные.
Остальное содержимое composer.json сохраняется.

```json
{
    "extra": {
        "apimediaru": {
            "documentation": {
                "id": "site",
                "title": {
                    "ru": "Работа с сайтом",
                    "en": "Working with the site"
                },
                "path": "docs"
            }
        }
    }
}
```

Путь относителен корню владельца composer.json. Источники автоматически
обнаруживаются у установленных Composer-пакетов и корневого приложения.
Используйте уникальный постоянный id. Руководство приложения отображается первым,
остальные сортируются по локализованным названиям.

## Главы и переводы

```text
docs/
├── ru/
│   ├── 01-create-page.md
│   └── 02-publish-news.md
└── en/
    ├── 01-create-page.md
    └── 02-publish-news.md
```

Название главы берётся из первого заголовка `#`. Имя после числового префикса
задаёт адрес главы. Переименование `01-create-page.md` в `03-create-page.md`
сохраняет адрес `create-page`. Имена переводов, включая порядок, должны совпадать.
Для адреса имя нормализуется через Laravel `Str::slug`, как в исходном просмотрщике.
Например, `03-Работа_с_блоками.md` получает адрес `rabota-s-blokami`.

Язык выбирается по активной локали приложения. Для en без английской главы
показывается ru с пометкой. Для прочих локалей используется русский текст.
Пакет следует механизму смены локали панели. При смене языка сохраняйте
параметры source/chapter в адресе страницы. Livewire сохраняет локаль
компонента в последующих запросах, поэтому смена языка панели должна
открыть страницу заново в новой локали.

## Ссылки и отключение

```php
$url = DocumentationPlugin::url('site', 'create-page');
$otherPanelUrl = DocumentationPlugin::url('site', 'create-page', panelId: 'admin');
```

Resolver возвращает URL либо null при недоступности главы, пользователя
или plugin в выбранной панели. Используйте null для скрытия ссылки помощи.
Открывайте инструкции из формы в новой вкладке.

```php
return $panel->plugin(
    DocumentationPlugin::make()->withoutSources(['tgs'])
);
```

После отключения руководство исчезает из меню и resolver возвращает null.
Старая ссылка на удалённую или отключённую главу показывает «Глава не найдена».
При входе без параметров открывается первая доступная глава.

## Поставка

Тексты читаются из установленной версии кода. Включите каталог руководства
в Composer-архив и Docker-образ. Проверьте `.gitattributes` и `.dockerignore`.
После обновления зависимости и деплоя удалённые главы исчезают, изменённые
показываются в новой редакции. Выполняйте штатный reload долгоживущих воркеров.

Стили документации поставляются в Blade и используют цвета Filament.
Дополнительный Tailwind source scanning для просмотрщика не требуется.
Markdown санитизируется перед выводом HTML.

## Проверки

```bash
composer install
composer test
composer analyse
composer lint
```

При работе через Docker стартера доступны Makefile-цели documentation-test,
documentation-analyse и documentation-lint.
