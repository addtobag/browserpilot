# Changelog

All notable changes to `browserpilot/browserpilot` are documented here. This project follows [Semantic Versioning](https://semver.org).

## [Unreleased]

### Added

- Drivers for Pilot Cloud, Browserbase, Steel and local Chrome.
- `Pilot::session()` builder with saved logins (profiles), regions, timeouts and metadata.
- Page API: `goto`, `click`, `type`, `press`, `select`, `extract`, `snapshot`, `screenshot`, `evaluate`, `waitFor`.
- Laravel AI SDK tools: `Browse`, `Click`, `TypeText`, `ReadPage`, `Screenshot` and `AskHuman`.
- Human take-over with `HandoffRequested` and `HandedBack` events.
- `<livewire:pilot-live-view>` component.
- `Pilot::fake()` with assertions.
- `php artisan pilot:install`.
