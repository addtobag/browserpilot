<?php

namespace BrowserPilot\Drivers;

use BrowserPilot\Contracts\ProfileStore;
use BrowserPilot\Exceptions\PilotException;
use BrowserPilot\Session;
use BrowserPilot\SessionOptions;
use BrowserPilot\Support\RemoteSession;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Browserbase (https://docs.browserbase.com/reference/api).
 * Saved logins use Browserbase contexts with persist: true.
 */
class BrowserbaseDriver extends CdpDriver
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(protected array $config, protected ProfileStore $profiles) {}

    public function start(SessionOptions $options): RemoteSession
    {
        $browserSettings = array_filter([
            'viewport' => $options->viewport ? ['width' => $options->viewport[0], 'height' => $options->viewport[1]] : null,
        ]);

        if ($options->profile !== null) {
            $browserSettings['context'] = [
                'id' => $this->profiles->resolve('browserbase', $options->profile, fn (string $name) => $this->createContext($name)),
                'persist' => true,
            ];
        }

        $body = array_filter([
            'projectId' => $this->config['project'] ?? null,
            'region' => $options->region ?? ($this->config['region'] ?? null),
            'timeout' => $options->timeout ? max(60, min(21600, $options->timeout)) : null,
            'keepAlive' => $options->keepAlive ?: null,
            'userMetadata' => $options->metadata ?: null,
            'browserSettings' => $browserSettings ?: null,
        ], fn ($value) => $value !== null);

        $data = $this->http()->post('/v1/sessions', $body)->throw()->json();

        return new RemoteSession(
            id: $data['id'],
            connectUrl: $data['connectUrl'],
            liveViewUrl: $this->debugUrl($data['id']),
            region: $data['region'] ?? null,
            raw: $data,
        );
    }

    public function release(Session $session): void
    {
        $this->http()->post('/v1/sessions/'.$session->remoteId(), array_filter([
            'projectId' => $this->config['project'] ?? null,
            'status' => 'REQUEST_RELEASE',
        ]))->throw();
    }

    public function liveViewUrl(Session $session): ?string
    {
        return $this->debugUrl($session->remoteId());
    }

    /**
     * Create a Browserbase context to hold cookies and storage for a saved login.
     */
    public function createContext(string $name): string
    {
        return (string) $this->http()->post('/v1/contexts', array_filter([
            'projectId' => $this->config['project'] ?? null,
            'name' => $name,
        ]))->throw()->json('id');
    }

    protected function debugUrl(string $id): ?string
    {
        return $this->http()->get("/v1/sessions/{$id}/debug")->throw()->json('debuggerFullscreenUrl');
    }

    protected function http(): PendingRequest
    {
        $key = $this->config['key'] ?? null;

        if (! $key) {
            throw PilotException::missingKey('browserbase', 'BROWSERBASE_API_KEY');
        }

        return Http::baseUrl(rtrim($this->config['url'] ?? 'https://api.browserbase.com', '/'))
            ->withHeaders(['X-BB-API-Key' => $key])
            ->acceptJson()
            ->asJson()
            ->timeout(30);
    }
}
