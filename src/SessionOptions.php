<?php

namespace BrowserPilot;

use Illuminate\Database\Eloquent\Model;

final class SessionOptions
{
    /**
     * @param  array<string, mixed>  $metadata
     * @param  array{0: int, 1: int}|null  $viewport
     */
    public function __construct(
        public ?string $profile = null,
        public ?string $region = null,
        public ?int $timeout = null,
        public bool $keepAlive = false,
        public array $metadata = [],
        public ?array $viewport = null,
        public ?Model $owner = null,
    ) {}
}
