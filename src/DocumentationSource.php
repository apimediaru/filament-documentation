<?php

namespace ApiMedia\Documentation;

final readonly class DocumentationSource
{
    /** @param array<string, DocumentationChapter> $chapters */
    public function __construct(
        public string $id,
        public string $title,
        public bool $site,
        public array $chapters,
    ) {}
}
