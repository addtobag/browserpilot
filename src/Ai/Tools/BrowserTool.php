<?php

namespace BrowserPilot\Ai\Tools;

use BrowserPilot\Contracts\Page;
use BrowserPilot\Session;
use Closure;
use Laravel\Ai\Contracts\Tool;
use Throwable;

/**
 * Base for Browser Pilot's Laravel AI SDK tools.
 */
abstract class BrowserTool implements Tool
{
    /**
     * @param  Closure(): Session  $session
     */
    public function __construct(protected Closure $session) {}

    public function session(): Session
    {
        return ($this->session)();
    }

    /**
     * Run an action against the page, refusing while a person is in control so
     * the agent never sees what they type.
     *
     * @param  Closure(Page): (string|null)  $action
     */
    protected function withPage(Closure $action): string
    {
        $session = $this->session()->refresh();

        if ($session->hasEnded()) {
            return 'The browser session has ended. Tell the user you could not finish.';
        }

        if ($session->needsHuman()) {
            return 'A person is in control of the browser right now. Do not act; call AskHuman to wait for them to hand back.';
        }

        try {
            $page = $session->page();
            $result = $action($page);

            return $result ?? $page->snapshot()->forAgent();
        } catch (Throwable $e) {
            report($e);

            return 'That did not work: '.$e->getMessage().'. Read the page again and try something else.';
        }
    }

    protected function selectorFor(mixed $ref, mixed $selector = null): string
    {
        if (is_string($selector) && $selector !== '') {
            return $selector;
        }

        return '[data-pilot-ref="'.(int) $ref.'"]';
    }
}
