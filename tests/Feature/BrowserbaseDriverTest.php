<?php

use BrowserPilot\Facades\Pilot;
use BrowserPilot\Models\PilotProfile;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake([
        'api.browserbase.com/v1/contexts' => Http::response(['id' => 'ctx_1', 'publicKey' => 'k', 'cipherAlgorithm' => 'AES-256-CBC', 'initializationVectorSize' => 16], 201),
        'api.browserbase.com/v1/sessions/sess_1/debug' => Http::response(['debuggerFullscreenUrl' => 'https://www.browserbase.com/devtools-fullscreen/sess_1', 'debuggerUrl' => 'x', 'pages' => [], 'wsUrl' => 'wss://x']),
        'api.browserbase.com/v1/sessions/sess_1' => Http::response(['id' => 'sess_1', 'status' => 'COMPLETED']),
        'api.browserbase.com/v1/sessions' => Http::response(['id' => 'sess_1', 'connectUrl' => 'wss://connect.browserbase.com?signingKey=abc', 'region' => 'eu-central-1', 'status' => 'RUNNING'], 201),
    ]);
});

it('starts a Browserbase session with a saved login', function () {
    $session = Pilot::session('browserbase')->profile('acme-portal')->region('eu-central-1')->start();

    expect($session->remoteId())->toBe('sess_1')
        ->and($session->connectUrl())->toBe('wss://connect.browserbase.com?signingKey=abc')
        ->and($session->liveViewUrl())->toBe('https://www.browserbase.com/devtools-fullscreen/sess_1')
        ->and($session->region())->toBe('eu-central-1');

    Http::assertSent(fn (Request $r) => $r->url() === 'https://api.browserbase.com/v1/sessions'
        && $r->hasHeader('X-BB-API-Key', 'bb_test')
        && $r['projectId'] === 'proj_123'
        && $r['browserSettings']['context'] === ['id' => 'ctx_1', 'persist' => true]);

    expect(PilotProfile::where('name', 'acme-portal')->value('remote_id'))->toBe('ctx_1');
});

it('reuses a saved login context', function () {
    PilotProfile::create(['driver' => 'browserbase', 'name' => 'acme-portal', 'remote_id' => 'ctx_existing']);

    Pilot::session('browserbase')->profile('acme-portal')->start();

    Http::assertNotSent(fn (Request $r) => str_ends_with($r->url(), '/v1/contexts'));
    Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/v1/sessions')
        && $r['browserSettings']['context']['id'] === 'ctx_existing');
});

it('releases the session', function () {
    $session = Pilot::start('browserbase');
    $session->release();

    Http::assertSent(fn (Request $r) => $r->url() === 'https://api.browserbase.com/v1/sessions/sess_1'
        && $r['status'] === 'REQUEST_RELEASE');

    expect($session->hasEnded())->toBeTrue();
});
