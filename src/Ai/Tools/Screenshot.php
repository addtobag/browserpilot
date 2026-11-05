<?php

namespace BrowserPilot\Ai\Tools;

use BrowserPilot\Contracts\Page;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

class Screenshot extends BrowserTool
{
    public function description(): Stringable|string
    {
        return 'Save a screenshot of the current page, for example as proof of a booking or submission. Returns where it was saved.';
    }

    public function handle(Request $request): Stringable|string
    {
        return $this->withPage(function (Page $page) {
            $path = $page->screenshot();

            return "Saved a screenshot of {$page->url()} to {$path}.";
        });
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
