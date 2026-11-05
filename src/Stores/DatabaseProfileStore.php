<?php

namespace BrowserPilot\Stores;

use BrowserPilot\Contracts\ProfileStore;
use BrowserPilot\Models\PilotProfile;
use Closure;

class DatabaseProfileStore implements ProfileStore
{
    public function resolve(string $driver, string $name, Closure $create): string
    {
        $profile = PilotProfile::query()->where('driver', $driver)->where('name', $name)->first();

        if ($profile !== null) {
            return $profile->remote_id;
        }

        return PilotProfile::query()->create([
            'driver' => $driver,
            'name' => $name,
            'remote_id' => $create($name),
        ])->remote_id;
    }
}
