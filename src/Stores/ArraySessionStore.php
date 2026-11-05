<?php

namespace BrowserPilot\Stores;

use BrowserPilot\Contracts\SessionStore;
use Illuminate\Support\Str;

class ArraySessionStore implements SessionStore
{
    /**
     * @var array<string, array<string, mixed>>
     */
    protected array $sessions = [];

    public function create(array $attributes): array
    {
        $id = (string) Str::ulid();

        return $this->sessions[$id] = $this->normalise(['id' => $id, 'created_at' => now()->toIso8601String()] + $attributes);
    }

    public function find(string $id): ?array
    {
        return $this->sessions[$id] ?? null;
    }

    public function update(string $id, array $attributes): array
    {
        return $this->sessions[$id] = $this->normalise(array_merge($this->sessions[$id] ?? ['id' => $id], $attributes));
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->sessions;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function normalise(array $attributes): array
    {
        return array_map(
            fn ($value) => $value instanceof \DateTimeInterface ? $value->format(DATE_ATOM) : $value,
            $attributes
        );
    }
}
