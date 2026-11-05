<?php

namespace BrowserPilot;

use BrowserPilot\Contracts\Driver;
use BrowserPilot\Contracts\Page;
use BrowserPilot\Contracts\SyncsHandoff;
use BrowserPilot\Events\HandedBack;
use BrowserPilot\Events\HandoffRequested;
use BrowserPilot\Events\SessionReleased;
use BrowserPilot\Exceptions\PilotException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Sleep;
use Throwable;

/**
 * @implements Arrayable<string, mixed>
 */
class Session implements Arrayable
{
    public const RUNNING = 'running';

    public const NEEDS_HUMAN = 'needs_human';

    public const ENDED = 'ended';

    protected ?Page $page = null;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(protected PilotManager $manager, protected array $attributes) {}

    public function id(): string
    {
        return (string) $this->attributes['id'];
    }

    public function remoteId(): string
    {
        return (string) $this->attributes['remote_id'];
    }

    public function driverName(): string
    {
        return (string) $this->attributes['driver'];
    }

    public function connectUrl(): string
    {
        return (string) ($this->attributes['connect_url'] ?? '');
    }

    public function liveViewUrl(): ?string
    {
        return $this->attributes['live_view_url'] ?? null;
    }

    public function status(): string
    {
        return (string) ($this->attributes['status'] ?? self::RUNNING);
    }

    public function profile(): ?string
    {
        return $this->attributes['profile'] ?? null;
    }

    public function region(): ?string
    {
        return $this->attributes['region'] ?? null;
    }

    public function handoffReason(): ?string
    {
        return $this->attributes['handoff_reason'] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        $metadata = $this->attributes['metadata'] ?? [];

        return is_string($metadata) ? (json_decode($metadata, true) ?: []) : (array) $metadata;
    }

    public function isRunning(): bool
    {
        return $this->status() === self::RUNNING;
    }

    public function needsHuman(): bool
    {
        return $this->status() === self::NEEDS_HUMAN;
    }

    public function hasEnded(): bool
    {
        return $this->status() === self::ENDED;
    }

    public function driver(): Driver
    {
        return $this->manager->driver($this->driverName());
    }

    /**
     * The browser's active page, connected on first use.
     */
    public function page(): Page
    {
        if ($this->hasEnded()) {
            throw PilotException::sessionEnded($this->id());
        }

        return $this->page ??= $this->driver()->page($this);
    }

    /**
     * Ask a person to take over. Fires HandoffRequested so you can notify them.
     */
    public function requestHandoff(string $reason): static
    {
        $this->update([
            'status' => self::NEEDS_HUMAN,
            'handoff_reason' => $reason,
            'handoff_requested_at' => now(),
            'handed_back_at' => null,
        ]);

        $driver = $this->driver();

        if ($driver instanceof SyncsHandoff) {
            rescue(fn () => $driver->handoffRequested($this, $reason), report: true);
        }

        event(new HandoffRequested($this, $reason));

        return $this;
    }

    /**
     * A person stepped in without being asked. Agent tools pause until they hand back.
     */
    public function takeOver(): static
    {
        return $this->requestHandoff('A person took over the browser.');
    }

    /**
     * Give control back to the agent. Fires HandedBack.
     */
    public function handBack(): static
    {
        if (! $this->needsHuman()) {
            return $this;
        }

        $this->update([
            'status' => self::RUNNING,
            'handed_back_at' => now(),
        ]);

        $driver = $this->driver();

        if ($driver instanceof SyncsHandoff) {
            rescue(fn () => $driver->handedBack($this), report: true);
        }

        event(new HandedBack($this));

        return $this;
    }

    /**
     * Block until a person hands control back. Returns false on timeout.
     * Run this on a queue worker, not inside a web request.
     */
    public function waitForHandback(?int $timeoutSeconds = null): bool
    {
        $timeoutSeconds ??= (int) config('pilot.handoff.timeout', 600);
        $poll = max(1, (int) config('pilot.handoff.poll', 2));
        $deadline = time() + $timeoutSeconds;
        $driver = $this->driver();

        do {
            $this->refresh();

            if (! $this->needsHuman()) {
                return ! $this->hasEnded();
            }

            if ($driver instanceof SyncsHandoff && rescue(fn () => $driver->remoteStatus($this), report: false) === self::RUNNING) {
                $this->handBack();

                return true;
            }

            Sleep::for($poll)->seconds();
        } while (time() < $deadline);

        return false;
    }

    /**
     * Stop the browser. Safe to call more than once.
     */
    public function release(): void
    {
        if ($this->hasEnded()) {
            return;
        }

        try {
            $this->page?->disconnect();
            $this->driver()->release($this);
        } catch (Throwable $e) {
            report($e);
        } finally {
            $this->page = null;
            $this->update(['status' => self::ENDED, 'ended_at' => now()]);
            event(new SessionReleased($this));
        }
    }

    /**
     * Ask the driver for a fresh live view URL (they can expire).
     */
    public function refreshLiveViewUrl(): ?string
    {
        $url = $this->driver()->liveViewUrl($this);

        if ($url !== null) {
            $this->update(['live_view_url' => $url]);
        }

        return $url;
    }

    public function refresh(): static
    {
        $this->attributes = $this->manager->store()->find($this->id()) ?? $this->attributes;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(array $attributes): static
    {
        $this->attributes = $this->manager->store()->update($this->id(), $attributes);

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $attributes = $this->attributes;
        unset($attributes['connect_url']);

        return $attributes;
    }
}
