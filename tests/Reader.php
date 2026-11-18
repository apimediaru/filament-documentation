<?php

namespace ApiMedia\Documentation\Tests;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Foundation\Auth\User;

class Reader extends User implements FilamentUser, HasAvatar, HasName
{
    protected $guarded = [];

    public function getFilamentAvatarUrl(): ?string
    {
        return null;
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->allowed;
    }
}
