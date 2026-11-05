<?php

namespace BrowserPilot\Ai\Tools;

use BrowserPilot\Contracts\Page;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

class Browse extends BrowserTool
{
    public function description(): Stringable|string
    {
        return 'Open a web page in the browser. Returns the page title, a numbered list of links, buttons and fields you can use with Click and TypeText, and the page text.';
    }

    public function handle(Request $request): Stringable|string
    {
        return $this->withPage(function (Page $page) use ($request) {
            $page->goto((string) $request['url']);

            return null;
        });
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'url' => $schema->string()->description('The full URL to open, e.g. https://example.com/login')->required(),
        ];
    }
}
