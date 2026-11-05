<?php

namespace BrowserPilot\Ai\Tools;

use BrowserPilot\Contracts\Page;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

class ReadPage extends BrowserTool
{
    public function description(): Stringable|string
    {
        return 'Read the current page again: its URL, title, numbered links, buttons and fields, and text.';
    }

    public function handle(Request $request): Stringable|string
    {
        return $this->withPage(fn (Page $page) => null);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
