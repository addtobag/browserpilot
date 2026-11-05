<?php

namespace BrowserPilot\Ai\Tools;

use BrowserPilot\Contracts\Page;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

class TypeText extends BrowserTool
{
    public function description(): Stringable|string
    {
        return 'Type into a field on the current page, replacing what is there. Set submit to true to press Enter afterwards. Never use this for passwords, card numbers or one-time codes: call AskHuman instead.';
    }

    public function handle(Request $request): Stringable|string
    {
        return $this->withPage(function (Page $page) use ($request) {
            $page->type($this->selectorFor($request['ref'] ?? null, $request['selector'] ?? null), (string) $request['text']);

            if ($request['submit'] ?? false) {
                $page->press('Enter');
            }

            return null;
        });
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'ref' => $schema->integer()->description('The field number shown in [brackets].')->required(),
            'text' => $schema->string()->description('The text to type.')->required(),
            'submit' => $schema->boolean()->description('Press Enter after typing.'),
            'selector' => $schema->string()->description('Optional CSS selector to use instead of ref.'),
        ];
    }
}
