<?php

namespace BrowserPilot\Support;

final class RemoteSession
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly string $id,
        public readonly string $connectUrl,
        public readonly ?string $liveViewUrl = null,
        public readonly ?string $region = null,
        public readonly array $raw = [],
    ) {}
}
