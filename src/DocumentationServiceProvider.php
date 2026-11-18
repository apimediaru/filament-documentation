<?php

namespace ApiMedia\Documentation;

use Illuminate\Support\ServiceProvider;

class DocumentationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'documentation');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'documentation');
    }
}
