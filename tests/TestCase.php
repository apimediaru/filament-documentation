<?php

namespace ApiMedia\Documentation\Tests;

use ApiMedia\Documentation\DocumentationServiceProvider;
use Composer\InstalledVersions;
use Orchestra\Testbench\TestCase as Orchestra;
use ReflectionClass;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        $installed = json_decode(file_get_contents(dirname((new ReflectionClass(InstalledVersions::class))->getFileName()).'/installed.json'), true);
        $providers = [];
        foreach ($installed['packages'] as $package) {
            foreach ($package['extra']['laravel']['providers'] ?? [] as $provider) {
                $providers[] = $provider;
            }
        }

        return array_values(array_unique([...$providers, DocumentationServiceProvider::class, PanelProvider::class]));
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('d', 32)));
        $app['config']->set('session.driver', 'array');
        $app['config']->set('cache.default', 'array');
        $app['config']->set('app.locale', 'ru');
    }
}
