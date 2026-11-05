<?php

namespace BrowserPilot\Drivers;

use BrowserPilot\Contracts\SyncsHandoff;
use BrowserPilot\Exceptions\PilotException;
use BrowserPilot\Session;
use BrowserPilot\SessionOptions;
use BrowserPilot\Support\RemoteSession;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Pilot Cloud: hosted browsers with saved logins, take-over and usage tracking.
 */
class PilotDriver extends CdpDriver implements SyncsHandoff
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(protected array $config) {}

    public function start(SessionOptions $options): RemoteSession
    {
        $data = $this->http()->post('/sessions', array_filter([
            'profile' => $options->profile,
            'region' => $options->region ?? ($this->config['region'] ?? null),
            'timeout' => $options->timeout,
            'keep_alive' => $options->keepAlive ?: null,
            'metadata' => $options->metadata ?: null,
            'viewport' => $options->viewport ? ['width' => $options->viewport[0], 'height' => $options->viewport[1]] : null,
        ], fn ($value) => $value !== null))->throw()->json('data');

        return new RemoteSession(
            id: $data['id'],
            connectUrl: $data['connect_url'],
            liveViewUrl: $data['live_view_url'] ?? null,
            region: $data['region'] ?? null,
            raw: $data,
        );
    }

    public function release(Session $session): void
    {
        $this->http()->delete('/sessions/'.$session->remoteId())->throw();
    }

    public function liveViewUrl(Session $session): ?string
    {
        return $this->http()->get('/sessions/'.$session->remoteId().'/live')->throw()->json('data.live_view_url');
    }

    public function handoffRequested(Session $session, string $reason): void
    {
        $this->http()->post('/sessions/'.$session->remoteId().'/handoff', ['reason' => $reason])->throw();
    }

    public function handedBack(Session $session): void
    {
        $this->http()->post('/sessions/'.$session->remoteId().'/handback')->throw();
    }

    public function remoteStatus(Session $session): ?string
    {
        return $this->http()->get('/sessions/'.$session->remoteId())->throw()->json('data.status');
    }

    protected function http(): PendingRequest
    {
        $key = $this->config['key'] ?? null;

        if (! $key) {
            throw PilotException::missingKey('pilot', 'PILOT_API_KEY');
        }

        return Http::baseUrl(rtrim($this->config['url'] ?? 'https://browserpilot.dev', '/').'/api/v1')
            ->withToken($key)
            ->acceptJson()
            ->asJson()
            ->timeout(30);
    }
}
