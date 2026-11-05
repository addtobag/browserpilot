<?php

namespace BrowserPilot\Testing;

use BrowserPilot\Contracts\SessionStore;
use BrowserPilot\Events\HandoffRequested;
use BrowserPilot\PilotManager;
use BrowserPilot\SessionOptions;
use BrowserPilot\Stores\ArraySessionStore;
use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use PHPUnit\Framework\Assert as PHPUnit;

/**
 * Pilot::fake() — never opens a real browser.
 */
class PilotFake extends PilotManager
{
    /**
     * @var array<string, list<mixed>>
     */
    protected array $recorded = [];

    protected ArraySessionStore $sessions;

    protected FakeDriver $fakeDriver;

    protected bool $autoHandBack = true;

    /**
     * @param  array<string, string>  $pages  URL pattern => text the fake page returns
     */
    public function __construct(Container $container, protected array $pages = [])
    {
        parent::__construct($container);

        $this->sessions = new ArraySessionStore;
        $this->fakeDriver = new FakeDriver($this);

        Event::listen(HandoffRequested::class, function (HandoffRequested $event) {
            $this->record('handoff', $event->reason);

            if ($this->autoHandBack) {
                $event->session->handBack();
            }
        });
    }

    /**
     * Keep handoffs waiting (the agent's AskHuman tool will time out) instead of
     * handing back straight away.
     */
    public function holdHandoffs(): static
    {
        $this->autoHandBack = false;

        return $this;
    }

    /**
     * @param  array<string, string>  $pages
     */
    public function withPages(array $pages): static
    {
        $this->pages = array_merge($this->pages, $pages);

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function pages(): array
    {
        return $this->pages;
    }

    public function driver($driver = null)
    {
        return $this->fakeDriver;
    }

    public function store(): SessionStore
    {
        return $this->sessions;
    }

    public function record(string $type, mixed $value): void
    {
        $this->recorded[$type][] = $value;
    }

    /**
     * @return list<mixed>
     */
    public function recorded(string $type): array
    {
        return $this->recorded[$type] ?? [];
    }

    /**
     * @param  (Closure(SessionOptions): bool)|null  $callback
     */
    public function assertSessionStarted(?Closure $callback = null): void
    {
        $matching = array_filter($this->recorded('started'), fn (SessionOptions $o) => $callback === null || $callback($o));

        PHPUnit::assertNotEmpty($matching, 'No matching browser session was started.');
    }

    public function assertSessionsStarted(int $count): void
    {
        PHPUnit::assertCount($count, $this->recorded('started'), "Expected {$count} browser sessions to start.");
    }

    public function assertNothingStarted(): void
    {
        PHPUnit::assertEmpty($this->recorded('started'), 'A browser session was started unexpectedly.');
    }

    public function assertVisited(string $url): void
    {
        PHPUnit::assertTrue(
            collect($this->recorded('visited'))->contains(fn ($visited) => $visited === $url || Str::is($url, $visited)),
            "The browser did not visit [{$url}]. Visited: ".implode(', ', $this->recorded('visited'))
        );
    }

    public function assertNotVisited(string $url): void
    {
        PHPUnit::assertFalse(
            collect($this->recorded('visited'))->contains(fn ($visited) => $visited === $url || Str::is($url, $visited)),
            "The browser visited [{$url}] unexpectedly."
        );
    }

    public function assertClicked(string $selector): void
    {
        PHPUnit::assertContains($selector, $this->recorded('clicked'), "[{$selector}] was not clicked.");
    }

    public function assertTyped(string $text, ?string $selector = null): void
    {
        $match = collect($this->recorded('typed'))->contains(
            fn ($typed) => $typed['text'] === $text && ($selector === null || $typed['selector'] === $selector)
        );

        PHPUnit::assertTrue($match, "[{$text}] was not typed.");
    }

    public function assertHandoffRequested(?string $reasonContains = null): void
    {
        $match = collect($this->recorded('handoff'))->contains(
            fn ($reason) => $reasonContains === null || Str::contains($reason, $reasonContains, ignoreCase: true)
        );

        PHPUnit::assertTrue($match, 'No matching handoff was requested.');
    }

    public function assertNoHandoffRequested(): void
    {
        PHPUnit::assertEmpty($this->recorded('handoff'), 'A handoff was requested unexpectedly.');
    }

    public function assertReleased(): void
    {
        $started = count($this->recorded('started'));
        $released = count(array_unique(Arr::flatten($this->recorded('released'))));

        PHPUnit::assertSame($started, $released, "{$started} sessions started but {$released} were released.");
    }
}
