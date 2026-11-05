<?php

namespace BrowserPilot\Contracts;

use BrowserPilot\Session;

/**
 * Drivers whose provider also tracks take-over state (Pilot Cloud), so a person
 * can hand control back from the provider's own dashboard.
 */
interface SyncsHandoff
{
    public function handoffRequested(Session $session, string $reason): void;

    public function handedBack(Session $session): void;

    /**
     * The provider's view of the session status: running, needs_human or ended.
     */
    public function remoteStatus(Session $session): ?string;
}
