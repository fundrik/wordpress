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
- Do not add runtime no-op code like `unset( $unused )` only to silence linting for fixed callback signatures; prefer a targeted `phpcs:ignore` with a short reason instead.
- When a method overrides a parent class method or implements an interface method, add the `#[Override]` attribute.

# Docblock Conventions

- Treat docblocks as concise API reference text, not prose paragraphs.
- Keep class/interface/enum/trait summary lines as one sentence in present tense, ending with a period.
- Prefer stable summary verbs by artifact role:
  - `Represents ...` for value objects, DTOs, commands, and read models.
  - `Provides ...` for services, factories, and ports when they expose an entry point or capability.
  - `Creates ...`, `Returns ...`, `Checks ...`, `Formats ...`, `Converts ...` for methods, based on what they do.
- For port interfaces, use the standard summary wording `Provides the <inbound|outbound> port for ...`.
- In `@param`, `@return`, and `@throws` descriptions, use short noun phrases or outcome phrases, not full explanatory sentences.
- Do not start `@param`, `@return`, or `@throws` descriptions with `The`; prefer `Campaign ID.` over `The campaign ID.`.
- Prefer `ID` over `identifier` in docblocks when referring to concrete IDs or HTML `id` attributes.
- Keep tag descriptions in sentence case and end them with a period.
- For booleans in `@return`, prefer `True when ...`.
- For nullable values, prefer explicit endings such as `..., if configured.` or `..., null otherwise.`.
- For dependency `@param` docblocks, keep one-line descriptions that state the dependency role and purpose, such as `Writes structured boot-unit logs.` or `Provides registered block types.`.

# Architecture Conventions

- `Infrastructure` contains technical implementations of system ports and orchestration that should remain platform-agnostic where possible.
- `Integration` contains platform adapters that depend on WordPress APIs (`do_action`, `add_action`, `WP_*`, REST hooks, etc.).
- Port interface docblocks should use the standard wording `Provides the <inbound|outbound> port for ...` for consistency across the codebase.

# Project Scripts

- Source of truth for runnable project commands is the `scripts` section in `composer.json` and `package.json`.
- Before running lint/tests/build/e2e/integration commands, read scripts from those files and execute via `composer run <script>` or `npm run <script>`.
