<?php

namespace BrowserPilot\Testing;

use BrowserPilot\Contracts\Page;
use BrowserPilot\Support\Snapshot;
use Illuminate\Support\Str;

class FakePage implements Page
{
    protected string $url = 'about:blank';

    /**
     * @param  array<string, string>  $pages  URL pattern => page text
     */
    public function __construct(protected PilotFake $fake, protected array $pages = []) {}

    public function goto(string $url): static
    {
        $this->url = Str::startsWith($url, ['http://', 'https://', 'about:']) ? $url : 'https://'.$url;
        $this->fake->record('visited', $this->url);

        return $this;
    }

    public function url(): string
    {
        return $this->url;
    }

    public function title(): string
    {
        return 'Fake page';
    }

    public function text(): string
    {
        foreach ($this->pages as $pattern => $text) {
            if (Str::is($pattern, $this->url)) {
                return $text;
            }
        }

        return '';
    }

    public function html(): string
    {
        return '<html><body>'.e($this->text()).'</body></html>';
    }

    public function click(string $selector): static
    {
        $this->fake->record('clicked', $selector);

        return $this;
    }

    public function type(string $selector, string $text): static
    {
        $this->fake->record('typed', ['selector' => $selector, 'text' => $text]);

        return $this;
    }

    public function press(string $key): static
    {
        $this->fake->record('pressed', $key);

        return $this;
    }

    public function select(string $selector, string $value): static
    {
        $this->fake->record('selected', ['selector' => $selector, 'value' => $value]);

        return $this;
    }

    public function evaluate(string $script): mixed
    {
        return null;
    }

    public function waitFor(string $selector, int $timeoutMs = 10000): static
    {
        return $this;
    }

    public function extract(array $selectors): array
    {
        return array_map(fn () => null, $selectors);
    }

    public function snapshot(): Snapshot
    {
        return new Snapshot($this->url, $this->title(), [], $this->text());
    }

    public function screenshot(?string $path = null): string
    {
        $path ??= 'fake/screenshot.png';
        $this->fake->record('screenshot', $path);

        return $path;
    }

    public function disconnect(): void {}
}
