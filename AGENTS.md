# Testing Conventions

- `tests/unit/Integration/` is **not** a real integration-test suite.
- Tests under `tests/unit/Integration/` are isolated unit tests for classes in the `Fundrik\WordPress\Integration` namespace.
- Real integration tests that use a real WordPress runtime and database belong in `tests/integration/`.
- If `phpunit` fails because `mbstring` is missing, run PHPUnit with `php -c .tmp/php.ini vendor/bin/phpunit`.
- Do not call private/protected methods via reflection in tests to force coverage.
- If target lines cannot be reached through the public API, report this explicitly and align on refactoring or coverage exclusions instead of testing internals directly.

# Editing Conventions

- When editing files in this repository, preserve Windows line endings (`CRLF`).
