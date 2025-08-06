# Introduction
Code Generator based on openEHR type specifications

This is a PHP console application designed to generate code (types, models, etc.) using openEHR specification files as input. 
It can generate the following types of files: 
 - BMM JSON files
 - XMI Internal Model files

For detailed development guidelines, please refer to the [guidelines.md](guidelines.md) file.

## Structure
The main source code is located in the `/src` directory.
XMI schema files need to be placed in `/code/XMI`.
The output code is generated under the `/code` directory.
The code-generator is a Symfony Console application defined in `/bin/generate`.

## Usage
You can run this application using the attached Docker Compose configuration:
```bash
docker compose run --rm php ...
```

For first-time usage, the `/vendor` composer directory needs to be populated:
```bash
docker compose run --rm php composer install
```

The generator application can be run either via `composer`:
```bash
docker compose run --rm php composer run generate <...>
```
or directly:
```bash
docker compose run --rm php ./bin/generate <...>
```

Generate all files as predefined in the application:
```bash
docker compose run --rm php ./bin/generate all
```

List the available commands for the generator tool (see list of available commands prefixed with `generate:`):
```bash
docker compose run --rm php ./bin/generate list
```
