<?php

namespace BrowserPilot\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;
use Throwable;

class AskHuman extends BrowserTool
{
    public function description(): Stringable|string
    {
        return 'Ask a person to take over the browser and wait until they hand it back. Use this for sign-ins, passwords, two-factor codes, CAPTCHAs, payments, and before sending, buying or deleting anything.';
    }

    public function handle(Request $request): Stringable|string
    {
        $session = $this->session()->refresh();

        if ($session->hasEnded()) {
            return 'The browser session has ended.';
        }

        if (! $session->needsHuman()) {
            $session->requestHandoff((string) ($request['reason'] ?? 'The agent needs a hand.'));
        }

        if (! $session->waitForHandback()) {
            return 'Nobody took over in time. Stop and tell the user what you were waiting for.';
        }

        try {
            return "The person handed control back. Here is the page now:\n\n".$session->page()->snapshot()->forAgent();
        } catch (Throwable $e) {
            report($e);

            return 'The person handed control back. Read the page to continue.';
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'reason' => $schema->string()->description('A short, friendly message for the person, e.g. "Can you sign in to your Trainline account for me?"')->required(),
        ];
    }
}
