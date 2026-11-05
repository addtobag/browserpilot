<?php

namespace BrowserPilot\Contracts;

use Closure;

interface ProfileStore
{
    /**
     * Find the provider's id for a saved login, creating it with the callback if needed.
     *
     * @param  Closure(string): string  $create
     */
    public function resolve(string $driver, string $name, Closure $create): string;
}
