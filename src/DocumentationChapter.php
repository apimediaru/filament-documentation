<?php

namespace ApiMedia\Documentation;

use Illuminate\Support\Str;

final readonly class DocumentationChapter
{
    public function __construct(
        public string $slug,
        public string $title,
        public string $path,
        public int $order,
        public bool $fallback,
    ) {}

    public function html(): string
    {
        return Str::of(file_get_contents($this->path))->markdown()->sanitizeHtml()->value();
    }
}
