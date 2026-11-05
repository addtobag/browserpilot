<?php

namespace BrowserPilot\Cdp;

use BrowserPilot\Contracts\Page;
use BrowserPilot\Exceptions\PilotException;
use BrowserPilot\Support\Snapshot;
use HeadlessChromium\Browser;
use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Page as ChromePage;
use Illuminate\Support\Str;
use Throwable;

/**
 * A page driven over the Chrome DevTools Protocol with chrome-php/chrome.
 * Works with any provider that gives you a CDP WebSocket URL.
 */
class CdpPage implements Page
{
    public function __construct(protected Browser $browser, protected ChromePage $page) {}

    public static function connect(string $webSocketUrl): self
    {
        $browser = BrowserFactory::connectToBrowser($webSocketUrl);

        $page = null;

        try {
            $pages = $browser->getPages();
            $page = $pages[0] ?? null;
        } catch (Throwable) {
            // Fall through and open a new tab.
        }

        return new self($browser, $page ?? $browser->createPage());
    }

    public function chrome(): ChromePage
    {
        return $this->page;
    }

    public function goto(string $url): static
    {
        if (! Str::startsWith($url, ['http://', 'https://', 'about:', 'data:'])) {
            $url = 'https://'.$url;
        }

        $this->page->navigate($url)->waitForNavigation(ChromePage::LOAD, 30000);

        return $this;
    }

    public function url(): string
    {
        return (string) $this->evaluate('location.href');
    }

    public function title(): string
    {
        return (string) $this->evaluate('document.title');
    }

    public function text(): string
    {
        return (string) $this->evaluate('document.body ? document.body.innerText : ""');
    }

    public function html(): string
    {
        return $this->page->getHtml();
    }

    public function click(string $selector): static
    {
        $this->assertExists($selector);

        $this->evaluate(sprintf(
            '(() => { const el = document.querySelector(%s); el.scrollIntoView({block: "center"}); el.click(); return true; })()',
            json_encode($selector)
        ));

        $this->settle();

        return $this;
    }

    public function type(string $selector, string $text): static
    {
        $this->assertExists($selector);

        $this->evaluate(sprintf(
            '(() => { const el = document.querySelector(%s); el.scrollIntoView({block: "center"}); el.focus(); if ("value" in el) { el.value = ""; el.dispatchEvent(new Event("input", {bubbles: true})); } return true; })()',
            json_encode($selector)
        ));

        $this->page->keyboard()->typeText($text);

        return $this;
    }

    public function press(string $key): static
    {
        $this->page->keyboard()->typeRawKey($key);
        $this->settle();

        return $this;
    }

    public function select(string $selector, string $value): static
    {
        $this->assertExists($selector);

        $this->evaluate(sprintf(
            '(() => { const el = document.querySelector(%s); el.value = %s; el.dispatchEvent(new Event("change", {bubbles: true})); return true; })()',
            json_encode($selector),
            json_encode($value)
        ));

        return $this;
    }

    public function evaluate(string $script): mixed
    {
        return $this->page->evaluate($script)->getReturnValue(30000);
    }

    public function waitFor(string $selector, int $timeoutMs = 10000): static
    {
        $deadline = microtime(true) + $timeoutMs / 1000;

        while (! $this->exists($selector)) {
            if (microtime(true) > $deadline) {
                throw new PilotException("Timed out waiting for [{$selector}].");
            }

            usleep(250_000);
        }

        return $this;
    }

    public function extract(array $selectors): array
    {
        $result = $this->evaluate(sprintf(
            '(() => { const map = %s; const out = {}; for (const [k, s] of Object.entries(map)) { const el = document.querySelector(s); out[k] = el ? (el.innerText ?? el.textContent ?? "").trim() : null; } return JSON.stringify(out); })()',
            json_encode((object) $selectors)
        ));

        return json_decode((string) $result, true) ?: [];
    }

    public function snapshot(): Snapshot
    {
        $max = (int) config('pilot.tools.max_elements', 120);

        return Snapshot::fromJson((string) $this->evaluate(str_replace('__MAX__', (string) $max, self::SNAPSHOT_JS)));
    }

    public function screenshot(?string $path = null): string
    {
        $path ??= storage_path('app/pilot/screenshots/'.Str::ulid().'.png');

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $this->page->screenshot()->saveToFile($path);

        return $path;
    }

    public function disconnect(): void
    {
        try {
            $connection = $this->browser->getConnection();

            if (method_exists($connection, 'disconnect')) {
                $connection->disconnect();
            }
        } catch (Throwable) {
            // Already gone.
        }
    }

    protected function exists(string $selector): bool
    {
        return (bool) $this->evaluate(sprintf('!!document.querySelector(%s)', json_encode($selector)));
    }

    protected function assertExists(string $selector): void
    {
        if (! $this->exists($selector)) {
            throw new PilotException("No element matches [{$selector}] on ".$this->url());
        }
    }

    /**
     * Give the page a moment to react (navigation, rendering) after an action.
     */
    protected function settle(): void
    {
        usleep(400_000);

        $deadline = microtime(true) + 8;

        while (microtime(true) < $deadline) {
            try {
                if ($this->evaluate('document.readyState') === 'complete') {
                    return;
                }
            } catch (Throwable) {
                // The page is navigating; try again.
            }

            usleep(250_000);
        }
    }

    /**
     * Numbers each visible interactive element with data-pilot-ref so agents can
     * refer to it, and never includes the values typed into fields.
     */
    protected const SNAPSHOT_JS = <<<'JS'
(() => {
  const max = __MAX__;
  document.querySelectorAll('[data-pilot-ref]').forEach(el => el.removeAttribute('data-pilot-ref'));
  const nodes = document.querySelectorAll('a[href], button, input:not([type=hidden]), select, textarea, summary, [role=button], [role=link], [role=checkbox], [role=tab], [role=menuitem], [contenteditable=true]');
  const clean = s => (s || '').replace(/\s+/g, ' ').trim().slice(0, 80);
  const elements = [];
  let n = 0;
  for (const el of nodes) {
    const r = el.getBoundingClientRect();
    const st = window.getComputedStyle(el);
    if (r.width === 0 || r.height === 0 || st.visibility === 'hidden' || st.display === 'none' || el.disabled) continue;
    n++;
    el.setAttribute('data-pilot-ref', String(n));
    const tag = el.tagName.toLowerCase();
    const type = el.getAttribute('type');
    const isButtonInput = tag === 'input' && ['submit', 'button', 'reset'].includes(type);
    let label = el.getAttribute('aria-label') || '';
    if (!label && el.id) { const l = document.querySelector('label[for="' + CSS.escape(el.id) + '"]'); if (l) label = l.innerText; }
    if (!label && (tag === 'a' || tag === 'button' || tag === 'summary' || el.getAttribute('role'))) label = el.innerText;
    if (!label && isButtonInput) label = el.value;
    if (!label) label = el.getAttribute('placeholder') || el.getAttribute('title') || el.getAttribute('name') || '';
    let line = '[' + n + '] ' + tag + (type ? '[' + type + ']' : '') + ' "' + clean(label) + '"';
    if (tag === 'a') line += ' -> ' + clean(el.getAttribute('href'));
    if (tag === 'select') line += ' options: ' + Array.from(el.options).slice(0, 15).map(o => clean(o.text)).join(' | ');
    if (type === 'checkbox' || type === 'radio') line += el.checked ? ' (checked)' : ' (not checked)';
    elements.push(line);
    if (elements.length >= max) break;
  }
  return JSON.stringify({ url: location.href, title: document.title, elements, text: document.body ? document.body.innerText : '' });
})()
JS;
}
