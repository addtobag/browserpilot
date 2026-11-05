<?php

namespace BrowserPilot\Stores;

use BrowserPilot\Contracts\SessionStore;
use BrowserPilot\Models\PilotSession;

class DatabaseSessionStore implements SessionStore
{
    public function create(array $attributes): array
    {
        return PilotSession::query()->create($attributes)->refresh()->toArray();
    }

    public function find(string $id): ?array
    {
        return PilotSession::query()->find($id)?->toArray();
    }

    public function update(string $id, array $attributes): array
    {
        $session = PilotSession::query()->findOrFail($id);
        $session->update($attributes);

        return $session->refresh()->toArray();
    }
}
