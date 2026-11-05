# Contributing

Thanks for helping make Browser Pilot better.

## Bugs and ideas

- Search [existing issues](https://github.com/addtobag/browserpilot/issues) first.
- For bugs, include your PHP and Laravel versions, the driver you use, and the smallest code that shows the problem.
- Security problems go through [a private advisory](https://github.com/addtobag/browserpilot/security/advisories/new), never a public issue.

## Pull requests

1. Fork the repo and create a branch from `main`.
2. `composer install`
3. Make your change and add a test for it. Tests use `Pilot::fake()` or `Http::fake()`, so they never need a real browser or API key.
4. `composer test`
5. Add a line under **Unreleased** in `CHANGELOG.md`.
6. Open the pull request with a short description of what changed and why.

Keep pull requests focused on one thing. If you're planning something big, like a new driver, open an issue first so we can agree on the approach.

## Code style

Follow the existing code and the Laravel conventions. Use typed properties and return types, and keep public methods documented.
