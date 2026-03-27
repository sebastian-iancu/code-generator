# Code Generator — openEHR

## Docker-First Workflow

All PHP and Composer commands MUST be run via Docker Compose. Never execute PHP/Composer directly on the host.

```bash
docker compose run --rm app composer <command>
```

## Quick Reference

```bash
# Install dependencies
docker compose run --rm app composer install

# Run tests
docker compose run --rm app composer run phpunit

# Static analysis (level 7)
docker compose run --rm app composer run phpstan

# Syntax lint
docker compose run --rm app composer run phplint

# Run generator
docker compose run --rm app composer run generate -- <command> [args]
docker compose run --rm app composer run generate -- list
```

## Architecture

Reader → Model → Writer pipeline:

- **Reader** (`src/Reader/`): Parses XMI and BMM JSON inputs
- **Model** (`src/Model/`): Internal UML and BMM representations
- **Writer** (`src/Writer/`): Generates PlantUML, AsciiDoc, YAML, JSON outputs
- **Formatters** (`src/Writer/Formatter/`): Output formatting strategies
- **Console** (`bin/Command/`): Symfony Console commands (entrypoint: `bin/generate`)

## Namespaces

- `OpenEHR\Tools\CodeGen\` → `src/`
- `Console\` → `bin/`
- `Tests\` → `tests/`

## Coding Conventions

- PHP 8.3, PSR-12 strict
- Constructor property promotion, typed properties
- No dynamic properties
- Short array syntax

## Important Rules

- **Do NOT edit files in `code/` except `code/BMM-JSON/` ** — these are generated artifacts
- **Do NOT edit `composer.lock` manually** — use Composer commands
- PHPStan baseline: `tests/phpstan-baseline.neon` — prefer fixing types over adding exceptions
- PHPStan excludes: `src/Model/Uml` and `src/Writer/UmlToBmmWriter.php`
- Test files go in `tests/`, suffix `Test.php`, namespace `Tests\`
- Use `sys_get_temp_dir()` for temporary files in tests, clean up in `tearDown`

## Generator Commands

```bash
# BMM JSON -> YAML / PlantUML / AsciiDoc / Split JSON
docker compose run --rm app composer run generate -- bmm:yaml <package...|all>
docker compose run --rm app composer run generate -- bmm:plantuml <package...|all>
docker compose run --rm app composer run generate -- bmm:adoc <package...|all>
docker compose run --rm app composer run generate -- bmm:split <package...|all>

# XMI -> BMM
docker compose run --rm app composer run generate -- xmi:bmm <xmi-schema...>
docker compose run --rm app composer run generate -- xmi:all [internal]
```
