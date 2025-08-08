# Introduction
Code Generator based on openEHR type specifications

This is a PHP console application designed to generate code (types, models, etc.) using openEHR specification files as input. 
It can generate the following types of files: 
 - BMM JSON files
 - BMM YAML files
 - XMI Internal Model files
 - PlantUML class and package diagrams

For detailed development guidelines, please refer to the [guidelines.md](guidelines.md) file.

## Structure
The main source code is located in the `/src` directory.
XMI schema files need to be placed in `/code/XMI`.
The output code is generated under the `/code` directory.
The code-generator is a Symfony Console application defined in `/bin/generate`.
Installed dependencies are located in the `/vendor` directory.

## Requirements
- PHP 8.0+
- Docker

## Installation
1. Clone the repository
2. Run `docker compose build` to build the Docker image
3. Run `docker compose run --rm php composer install` to install dependencies

## Usage
The generator application can be run either via `composer`:
```bash
docker compose run --rm php composer run generate <...>
```
or directly:
```bash
docker compose run --rm php ./bin/generate <...>
```

List the available commands for the generator tool (see list of available commands prefixed with `generate:`):
```bash
docker compose run --rm php ./bin/generate list
```



Generate all files as predefined in the application:
```bash
docker compose run --rm php ./bin/generate all
```

### XmiToBmm Command

The `xmi:bmm` command (with aliases `xmi` and `uml`) generates BMM (Basic Meta-Model) files from UML XMI schema files.

#### Usage
```bash
docker compose run --rm php ./bin/generate xmi:bmm <schema1> [schema2] [...]
````
#### Examples
```bash
# Single schema
docker compose run --rm php ./bin/generate xmi:bmm BASE-v1.2.0

# Multiple schemas (dependencies first)
docker compose run --rm php ./bin/generate xmi:bmm BASE-v1.2.0 RM-v1.1.0
```

### Command to extract All XMI to JSON BMM 

The `all` command generates all predefined files in the application, including JSON BMM files and other supported formats.

#### Usage
```bash
docker compose run --rm php ./bin/generate all
```

### BMM Export Commands

Convert BMM JSON files to YAML format:
```bash
docker compose run --rm php ./bin/generate bmm:yaml <filename>
```
Usage
```bash
docker compose run --rm php ./bin/generate bmm:yaml BASE-v1.2.0 RM-v1.1.0
# Or convert all: 
docker compose run --rm php ./bin/generate bmm:yaml all
```

Convert BMM JSON files to PlantUML diagrams:
```bash
docker compose run --rm php ./bin/generate bmm:plantuml <filename>
```
Usage
```bash
docker compose run --rm php ./bin/generate bmm:plantuml BASE-v1.2.0 RM-v1.1.0
# Or convert all: 
docker compose run --rm php ./bin/generate bmm:plantuml all
```

