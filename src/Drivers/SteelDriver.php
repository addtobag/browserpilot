<?php

namespace BrowserPilot\Drivers;

use BrowserPilot\Exceptions\PilotException;
use BrowserPilot\Session;
use BrowserPilot\SessionOptions;
use BrowserPilot\Support\RemoteSession;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Steel (https://steel.dev), hosted or self-hosted from the open-source steel-browser.
 *
 * Note: written against Steel's public session API; check field names against
 * the version you run before relying on it in production.
 */
class SteelDriver extends CdpDriver
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(protected array $config) {}

    public function start(SessionOptions $options): RemoteSession
    {
        if ($options->profile !== null) {
            throw PilotException::unsupported('steel', 'saved logins (profiles)');
        }

        $data = $this->http()->post('/v1/sessions', array_filter([
            'timeout' => $options->timeout ? $options->timeout * 1000 : null,
            'dimensions' => $options->viewport ? ['width' => $options->viewport[0], 'height' => $options->viewport[1]] : null,
            'region' => $options->region,
        ]))->throw()->json();

        return new RemoteSession(
            id: $data['id'],
            connectUrl: $this->connectUrl($data),
            liveViewUrl: $data['debugUrl'] ?? $data['sessionViewerUrl'] ?? null,
            region: $data['region'] ?? null,
            raw: $data,
        );
    }

    public function release(Session $session): void
    {
        $this->http()->post('/v1/sessions/'.$session->remoteId().'/release')->throw();
    }

    public function liveViewUrl(Session $session): ?string
    {
        $data = $this->http()->get('/v1/sessions/'.$session->remoteId())->throw()->json();

        return $data['debugUrl'] ?? $data['sessionViewerUrl'] ?? null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function connectUrl(array $data): string
    {
        $base = $data['websocketUrl'] ?? 'wss://connect.steel.dev';

        if (str_contains($base, 'apiKey=')) {
            return $base;
        }

        return $base.(str_contains($base, '?') ? '&' : '?').http_build_query([
            'apiKey' => $this->config['key'],
            'sessionId' => $data['id'],
        ]);
    }

    protected function http(): PendingRequest
    {
        $key = $this->config['key'] ?? null;

        if (! $key) {
            throw PilotException::missingKey('steel', 'STEEL_API_KEY');
        }

        return Http::baseUrl(rtrim($this->config['url'] ?? 'https://api.steel.dev', '/'))
            ->withHeaders(['steel-api-key' => $key])
            ->acceptJson()
            ->asJson()
            ->timeout(30);
    }
}
