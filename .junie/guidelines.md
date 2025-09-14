Project: openEHR Code Generator (PHP 8.3)

Audience: Advanced contributors familiar with Composer, PHPUnit 12, PHPStan 2, Symfony Console, and code generation workflows.

**IMPORTANT: Docker-first workflow**
- All PHP and Composer commands MUST be run via docker compose. Do not execute PHP/Composer directly on the host.
- The provided `docker-compose.yml` defines the application service (service name: `app`) and volumes for source and vendor.

1. Build and Configuration
- Requirements (host)
  - Docker Engine 24+ and Docker Compose v2.
  - OS: Linux/WSL2 or macOS recommended. Windows is supported; prefer WSL2 for consistent paths.

- Container runtime (image)
  - PHP 8.3 with ext-json, ext-libxml, ext-simplexml; Xdebug optional for coverage.
  - Composer 2.x available inside the container.

- Install (inside container via compose)
```
docker compose run --rm app composer install --no-interaction --prefer-dist
```

- Autoloaders: PSR-4
  - Console\ -> bin/
  - OpenEHR\Tools\CodeGen\ -> src/
  - Dev tests: Tests\ -> tests/

- Environment notes
  - Symfony Console entrypoint: bin/generate (PHP script, not a phar).
  - Use composer scripts via compose:
```
docker compose run --rm app composer run generate -- <command> [args]
```
  - Some configs write caches and reports to `/tmp/phpunit` inside the container. Ensure the directory is creatable; map it as a volume if you need host access.
  - Large memory operations: phpstan script runs with --memory-limit 2G (inside container). Ensure the container has enough memory.

- Data/inputs
  - BMM/XMI/ADL assets live under `code/` (multiple subtrees might be available). Paths are relative to the project root and visible inside the container.

2. Testing
- Config files
  - PHPUnit: tests/phpunit.xml (PHPUnit 12 syntax compatible). Bootstraps tests/bootstrap.php if present; discovery suffix Test.php under tests/.
  - Coverage output and logs are directed to /tmp/phpunit (in-container path).
  - PHPStan: tests/phpstan.neon includes tests/phpstan-baseline.neon; analyses ../src with level 7. Excludes: src/Model/Uml and src/Writer/UmlToBmmWriter.php.

- Run test suite (via compose)
```
# Lint
docker compose run --rm app composer run phplint

# Static analysis
docker compose run --rm app composer run phpstan

# Unit tests
docker compose run --rm app composer run phpunit

# Coverage
docker compose run --rm -e XDEBUG_MODE=coverage app composer run phpunit
```

- Adding tests
  - Place unit tests under `tests/`, filename suffix Test.php, PSR-4 namespace Tests\...
  - Prefer pure unit tests on `src/` classes: OpenEHR\Tools\CodeGen\...
  - Use data fixtures from `code/` minimally; tests should be deterministic and avoid modifying repo files. If temporary files are needed, use sys_get_temp_dir() and clean up in tearDown.
  - When testing console commands (`bin/generate`), use Symfony\Component\Console\Tester\CommandTester to invoke command classes directly instead of spawning processes.

- Demo test (how-to)
  - Example skeleton:
    - File: tests/Demo/SmokeTest.php
      - Namespace: Tests\Demo
      - Content:
        - test "composer autoload and constants": require vendor/autoload.php and assert key constants or basic class existence like class_exists(OpenEHR\\Tools\\CodeGen\\Model\\Bmm\\BmmModel::class) if available; otherwise assert true.
  - Run:
```
docker compose run --rm app composer run phpunit
```
  - Remove the demo test after validating your environment to keep the tree clean.

3. Development Guidelines
- Coding style
  - Follow PSR-12 for PHP. Use short array syntax, strict types where appropriate. Keep public API documented with phpdoc. Avoid dynamic properties.
  - Constructor property promotion preferred; enable typed properties throughout.

- Static analysis
```
docker compose run --rm app composer run phpstan
```
  - Keep or update tests/phpstan-baseline.neon if introducing necessary exceptions; prefer fixing types.

- Linting
```
docker compose run --rm app composer run phplint
```
  - Use for syntax validation.

- Architecture overview
  - `bin/generate` registers Symfony Console commands:
    - Console\Command\XmiToBmm, XmiToInternalModel, AllXmi, BmmJsonToYaml, BmmJsonToPlantUml, BmmJsonToAsciiDoc, BmmJsonToClassJson.
  - `src/` contains:
    - Reader: parsing BMM/XMI/YAML/etc.
    - Model: internal representations (Bmm, Uml, etc.).
    - Writer: code and document generators (e.g., PlantUML, AsciiDoc).
    - Generated/Templates: generation templates; avoid editing generated artifacts directly.

- Running generators (via compose)
  - List commands:
```
docker compose run --rm app composer run generate -- list
```

  - Typical flows:
```
# BMM JSON -> YAML
docker compose run --rm app composer run generate -- bmm:yaml <package...|all>

# BMM JSON -> PlantUML
docker compose run --rm app composer run generate -- bmm:plantuml <package...|all>

# BMM JSON -> AsciiDoc tables
docker compose run --rm app composer run generate -- bmm:adoc <package...|all>

# BMM JSON -> split into per-class JSON files
docker compose run --rm app composer run generate -- bmm:split <package...|all>

# XMI -> BMM
docker compose run --rm app composer run generate -- xmi:bmm <xmi-schema...>

# XMI -> dump internal model (debugging)
docker compose run --rm app composer run generate -- xmi:internal-model <name> <xmi-schema...>

# Generate a predefined set of XMI -> BMM transformations
docker compose run --rm app composer run generate -- xmi:all [internal]
```

  - Note: exact command names per Console\Command classes; inspect bin/Command directory for options.

- Performance and memory
```
# Increase PHP memory limit for a single invocation inside the container
docker compose run --rm -e PHP_MEMORY_LIMIT=2G app php -d memory_limit=2G vendor/bin/phpunit
```

  - Large BMM sets can require >1G RAM; adjust as needed or configure php.ini in the image.

- Troubleshooting
  - If phpunit complains about schema URL, ignore; configuration still works with PHPUnit 12 runtime.
  - Ensure /tmp/phpunit exists and is writable inside the container; create it or mount a tmp volume if needed.
  - Console verbosity: use -v, -vv, or -vvv with bin/generate for more output.
  - Docker permissions: if you hit file permission issues on generated artifacts, consider running the container with matching UID/GID via compose or adjust volume permissions.
  - On Windows, prefer WSL2 based Docker for path stability. Avoid running PHP/Composer on the host.

- Docker specifics
```
# Build images
docker compose build

# One-off Composer install
docker compose run --rm app composer install

# Remove volumes if dependencies look stale
docker compose down -v
```

  - Keep volumes: vendor is typically cached via volume; if dependencies look stale, remove the volume and reinstall.

4. Releasing and CI (if applicable)
- Prefer reproducible builds; commit composer.lock when updating dependencies.
- Use composer scripts via docker-compose to ensure consistent environments across contributors.

Notes
- This document is intentionally focused on project-specific conventions and scripts. Update when introducing new Console commands, directories, or test tooling.
