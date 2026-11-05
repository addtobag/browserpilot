<?php

namespace BrowserPilot\Ai\Tools;

use BrowserPilot\Contracts\Page;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

class Click extends BrowserTool
{
    public function description(): Stringable|string
    {
        return 'Click a link, button or other element on the current page, using its number from the latest page read. Returns the updated page.';
    }

    public function handle(Request $request): Stringable|string
    {
        return $this->withPage(function (Page $page) use ($request) {
            $page->click($this->selectorFor($request['ref'] ?? null, $request['selector'] ?? null));

            return null;
        });
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'ref' => $schema->integer()->description('The element number shown in [brackets].')->required(),
            'selector' => $schema->string()->description('Optional CSS selector to use instead of ref.'),
        ];
    }
}
