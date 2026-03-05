# Testing Conventions

- `tests/unit/Integration/` is **not** a real integration-test suite.
- Tests under `tests/unit/Integration/` are isolated unit tests for classes in the `Fundrik\WordPress\Integration` namespace.
- Real integration tests that use a real WordPress runtime and database belong in `tests/integration/`.
- If `phpunit` fails because `mbstring` is missing, run PHPUnit with `php -c .tmp/php.ini vendor/bin/phpunit`.
- Do not call private/protected methods via reflection in tests to force coverage.
- If target lines cannot be reached through the public API, report this explicitly and align on refactoring or coverage exclusions instead of testing internals directly.
- Test `do_action` with `Brain\Monkey\Actions\expectDone` and `apply_filters` with `Brain\Monkey\Filters\expectApplied` instead of aliasing hook functions manually.
- When tests need fake/dummy classes, create them in `tests/unit/Fixtures/` instead of declaring ad-hoc anonymous/helper classes inside test files.

# Exception Message Policy

- Keep exception message text developer-focused, concise, and in English.
- Use stable templates:
  - Validation: `<Field> must <constraint>. Given: <value>.`
  - Business rule: `Cannot <action> <entity> "<id>": <reason>.`
  - Infrastructure/read failures: `Failed to <action> <entity> "<id>".`
  - Post-action side-effect failures: `<Entity> "<id>" was <past participle>, but <side effect> failed.`
- End every exception message with a period.
- Do not build logic on `message` text.
- Use exception class as the primary discriminator.
- Add `stage`/`reason` only when there are 2+ meaningful failure branches that require different handling.
- Preserve low-level details via `previous` exceptions, not by concatenating nested messages into the top-level message.

# Editing Conventions

- When editing files in this repository, preserve Windows line endings (`CRLF`).

# Architecture Conventions

- `Infrastructure` contains technical implementations of system ports and orchestration that should remain platform-agnostic where possible.
- `Integration` contains platform adapters that depend on WordPress APIs (`do_action`, `add_action`, `WP_*`, REST hooks, etc.).
- Port interface docblocks should use the standard wording `Provides the <inbound|outbound> port for ...` for consistency across the codebase.
