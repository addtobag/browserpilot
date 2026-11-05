<?php

use BrowserPilot\Events\HandedBack;
use BrowserPilot\Events\HandoffRequested;
use BrowserPilot\Facades\Pilot;
use BrowserPilot\SessionOptions;
use Illuminate\Support\Facades\Event;

it('records what the browser did', function () {
    $fake = Pilot::fake(['https://example.com/*' => 'Welcome back, Ross']);

    $session = Pilot::session()->profile('acme')->start();
    $page = $session->page()->goto('https://example.com/account');
    $page->type('#search', 'invoices');
    $page->click('[data-pilot-ref="3"]');

    expect($page->text())->toBe('Welcome back, Ross');

    $fake->assertSessionStarted(fn (SessionOptions $o) => $o->profile === 'acme');
    $fake->assertVisited('https://example.com/*');
    $fake->assertNotVisited('https://evil.test');
    $fake->assertTyped('invoices', '#search');
    $fake->assertClicked('[data-pilot-ref="3"]');
    $fake->assertNoHandoffRequested();
});

it('hands back straight away when faking a handoff', function () {
    Event::fake([HandedBack::class]);
    $fake = Pilot::fake();

    $session = Pilot::start();
    $session->requestHandoff('Can you sign in for me?');

    expect($session->waitForHandback(1))->toBeTrue()
        ->and($session->refresh()->isRunning())->toBeTrue();

    $fake->assertHandoffRequested('sign in');
    Event::assertDispatched(HandedBack::class);
});

it('can hold a handoff until a person hands back', function () {
    $fake = Pilot::fake()->holdHandoffs();

    $session = Pilot::start();
    $session->requestHandoff('Two-factor code needed');

    expect($session->needsHuman())->toBeTrue();

    Pilot::find($session->id())->handBack();

    expect($session->refresh()->isRunning())->toBeTrue();
});

it('releases sessions', function () {
    $fake = Pilot::fake();

    $session = Pilot::start();
    $session->release();
    $session->release();

    expect($session->hasEnded())->toBeTrue();
    $fake->assertReleased();
});

it('shares one lazily started session between tools', function () {
    $fake = Pilot::fake();

    $fake->assertNothingStarted();

    $first = Pilot::current();
    $second = Pilot::current();

    expect($first->id())->toBe($second->id());
    $fake->assertSessionsStarted(1);

    Pilot::releaseCurrent();
    $fake->assertReleased();
});
