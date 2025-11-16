# Introduction
Code Generator based on openEHR type specifications

This is a PHP console application designed to generate code (types, models, etc.) using openEHR specification files as input. 
It can generate the following types of files: 
 - BMM JSON files
 - BMM YAML files
 - XMI Internal Model files
 - PlantUML class and package diagrams
 - Split per-class BMM JSON files

For detailed development guidelines, see [.junie/guidelines.md](.junie/guidelines.md) (Docker-first, contributor-focused).

## Structure
The main source code is located in the `/src` directory.
XMI schema files need to be placed in `/code/XMI`.
The output code is generated under the `/code` directory.
The code-generator is a Symfony Console application defined in `/bin/generate`.
Installed dependencies are located in the `/vendor` directory.

## Requirements
- Docker Engine 24+ and Docker Compose v2
- Container: PHP 8.3 with ext-json, ext-libxml, ext-simplexml; Composer 2.x available inside the container
- All PHP/Composer commands must be run via docker compose (service: app)

## Installation
1. Clone the repository
2. Run `docker compose build` to build the Docker image
3. Run `docker compose run --rm app composer install` to install dependencies

## Usage
The generator application can be run either via `composer`:
```bash
docker compose run --rm app composer run generate <...>
```
or directly:
```bash
docker compose run --rm app ./bin/generate <...>
```

List the available commands for the generator tool (see list of available commands prefixed with `generate:`):
```bash
docker compose run --rm app ./bin/generate list
```



Generate all files as predefined in the application:
```bash
docker compose run --rm app ./bin/generate all
```

### XmiToBmm Command

The `xmi:bmm` command (with aliases `xmi` and `uml`) generates BMM (Basic Meta-Model) files from UML XMI schema files.

#### Usage
```bash
docker compose run --rm app ./bin/generate xmi:bmm <schema1> [schema2] [...]
````
#### Examples
```bash
# Single schema
docker compose run --rm app ./bin/generate xmi:bmm BASE-v1.2.0

# Multiple schemas (dependencies first)
docker compose run --rm app ./bin/generate xmi:bmm BASE-v1.2.0 RM-v1.1.0
```

### Generate a predefined set from XMI

The `xmi:all` command (alias: `all`) runs a curated set of XMI -> BMM transformations. Optionally, you can dump the intermediate internal model for debugging.

#### Usage
```bash
# Generate BMM from a predefined set of XMI schemas
docker compose run --rm app ./bin/generate all
# Dump internal model variants instead
docker compose run --rm app ./bin/generate xmi:all internal
```

### BMM Export Commands

Convert BMM JSON files to YAML format:
```bash
docker compose run --rm app ./bin/generate bmm:yaml <filename>
```
Examples
```bash
# Specific files
docker compose run --rm app ./bin/generate bmm:yaml openehr_base_1.3.0 openehr_rm_1.2.0
# Or convert all
docker compose run --rm app ./bin/generate bmm:yaml all
```

Convert BMM JSON files to PlantUML diagrams:
```bash
docker compose run --rm app ./bin/generate bmm:plantuml <filename>
```
Examples
```bash
# Specific files
docker compose run --rm app ./bin/generate bmm:plantuml openehr_base_1.3.0 openehr_rm_1.2.0
# Or convert all
docker compose run --rm app ./bin/generate bmm:plantuml all
```

Convert BMM JSON files to AsciiDoc tables, tabs, and tabs with diagrams:
```bash
docker compose run --rm app ./bin/generate bmm:adoc <filename>
```
Examples
```bash
# Specific files
docker compose run --rm app ./bin/generate bmm:adoc openehr_rm_1.2.0
# Or convert all
docker compose run --rm app ./bin/generate bmm:adoc all
```


## Testing

Run lint, static analysis, and unit tests inside the container:
```bash
# Lint
docker compose run --rm app composer run phplint

# Static analysis
docker compose run --rm app composer run phpstan

# Unit tests
docker compose run --rm app composer run phpunit

# Coverage
docker compose run --rm -e XDEBUG_MODE=coverage app composer run phpunit
```

Troubleshooting:
- Ensure /tmp/phpunit exists and is writable inside the container; mount a tmp volume if you need host access.
- On Windows, prefer WSL2-backed Docker for stable paths.
