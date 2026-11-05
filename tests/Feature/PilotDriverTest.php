<?php

use BrowserPilot\Facades\Pilot;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

beforeEach(function () {
    Sleep::fake();
});

it('starts a Pilot Cloud session', function () {
    Http::fake([
        'cloud.test/api/v1/sessions' => Http::response(['data' => [
            'id' => 'ps_1', 'status' => 'running', 'connect_url' => 'wss://bb/abc', 'live_view_url' => 'https://live/ps_1', 'region' => 'eu-central-1',
        ]], 201),
    ]);

    $session = Pilot::session('pilot')->profile('trainline')->start();

    expect($session->remoteId())->toBe('ps_1')
        ->and($session->liveViewUrl())->toBe('https://live/ps_1');

    Http::assertSent(fn (Request $r) => $r->hasHeader('Authorization', 'Bearer bp_test') && $r['profile'] === 'trainline');
});

it('picks up a hand-back made in the Pilot Cloud dashboard', function () {
    Http::fake([
        'cloud.test/api/v1/sessions' => Http::response(['data' => ['id' => 'ps_1', 'status' => 'running', 'connect_url' => 'wss://bb/abc']], 201),
        'cloud.test/api/v1/sessions/ps_1/handoff' => Http::response(['data' => ['status' => 'needs_human']]),
        'cloud.test/api/v1/sessions/ps_1/handback' => Http::response(['data' => ['status' => 'running']]),
        'cloud.test/api/v1/sessions/ps_1' => Http::sequence()
            ->push(['data' => ['status' => 'needs_human']])
            ->push(['data' => ['status' => 'running']]),
    ]);

    $session = Pilot::start('pilot');
    $session->requestHandoff('Please sign in');

    expect($session->waitForHandback(30))->toBeTrue()
        ->and($session->refresh()->isRunning())->toBeTrue();
});
