<?php

namespace BrowserPilot\Contracts;

use BrowserPilot\Support\Snapshot;

interface Page
{
    public function goto(string $url): static;

    public function url(): string;

    public function title(): string;

    /**
     * The visible text of the page.
     */
    public function text(): string;

    public function html(): string;

    public function click(string $selector): static;

    /**
     * Focus an element, clear it and type into it like a person would.
     */
    public function type(string $selector, string $text): static;

    /**
     * Press a key such as "Enter" or "Tab".
     */
    public function press(string $key): static;

    public function select(string $selector, string $value): static;

    /**
     * Run JavaScript in the page and return its (JSON-serialisable) result.
     */
    public function evaluate(string $script): mixed;

    /**
     * Wait until an element matching the selector exists.
     */
    public function waitFor(string $selector, int $timeoutMs = 10000): static;

    /**
     * Pull text out of the page: ['title' => 'h1', 'price' => '.price'].
     *
     * @param  array<string, string>  $selectors
     * @return array<string, string|null>
     */
    public function extract(array $selectors): array;

    /**
     * A compact view of the page for an agent: numbered interactive elements plus text.
     */
    public function snapshot(): Snapshot;

    /**
     * Save a PNG screenshot and return its path.
     */
    public function screenshot(?string $path = null): string;

    /**
     * Drop the connection without closing the remote browser.
     */
    public function disconnect(): void;
}
