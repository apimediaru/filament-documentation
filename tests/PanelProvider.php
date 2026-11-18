<?php

namespace ApiMedia\Documentation\Tests;

use ApiMedia\Documentation\DocumentationPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Panel;
use Filament\PanelProvider as BaseProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class PanelProvider extends BaseProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel->default()->id('admin')->path('admin')->login()
            ->plugin(DocumentationPlugin::make())
            ->middleware([EncryptCookies::class, AddQueuedCookiesToResponse::class, StartSession::class,
                ShareErrorsFromSession::class, SubstituteBindings::class])
            ->authMiddleware([Authenticate::class]);
    }
}
