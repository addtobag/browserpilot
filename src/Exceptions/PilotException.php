<?php

namespace BrowserPilot\Exceptions;

use RuntimeException;

class PilotException extends RuntimeException
{
    public static function missingKey(string $driver, string $env): self
    {
        return new self("The [{$driver}] driver needs an API key. Set {$env} in your .env file.");
    }

    public static function sessionEnded(string $id): self
    {
        return new self("Browser session [{$id}] has ended.");
    }

    public static function unsupported(string $driver, string $feature): self
    {
        return new self("The [{$driver}] driver doesn't support {$feature} yet.");
    }
}
