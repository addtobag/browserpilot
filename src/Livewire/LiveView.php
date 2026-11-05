<?php

namespace BrowserPilot\Livewire;

use BrowserPilot\Facades\Pilot;
use BrowserPilot\Models\PilotSession;
use BrowserPilot\Session;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * <livewire:pilot-live-view :session="$session" />
 *
 * Shows the browser live. While the agent is working the view is watch-only;
 * when it asks for help (or someone presses "Take over") the person can use
 * the browser, then hand it back.
 */
class LiveView extends Component
{
    #[Locked]
    public string $sessionId;

    public bool $controls = true;

    public string $height = '560px';

    public function mount(Session|PilotSession|string $session, bool $controls = true, string $height = '560px'): void
    {
        $this->sessionId = match (true) {
            $session instanceof Session => $session->id(),
            $session instanceof PilotSession => (string) $session->getKey(),
            default => $session,
        };

        $this->controls = $controls;
        $this->height = $height;

        $this->authorizeSession();
    }

    public function takeOver(): void
    {
        $this->authorizeSession();
        $this->pilotSession()->takeOver();
    }

    public function handBack(): void
    {
        $this->authorizeSession();
        $this->pilotSession()->handBack();
    }

    public function end(): void
    {
        $this->authorizeSession();
        $this->pilotSession()->release();
    }

    public function pilotSession(): Session
    {
        return Pilot::findOrFail($this->sessionId);
    }

    public function render()
    {
        return view('pilot::livewire.live-view', [
            'session' => $this->pilotSession(),
            'poll' => config('pilot.live_view.poll', '3s'),
        ]);
    }

    protected function authorizeSession(): void
    {
        if ($ability = config('pilot.live_view.gate')) {
            Gate::authorize($ability, $this->pilotSession());
        }
    }
}
