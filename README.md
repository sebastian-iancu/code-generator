# Introduction
XMI Code Generator

This is console application made in PHP to generate code (types, modesl, etc.) using on openEHR XMI files as input. 
It can generate followings: 
 - BMM JSON files
 - (Internal Model) 

## Structure
The main source code located ub `/src`.
XMI schema files need to be placed in `/schema`.
The output code is generated under `/code`.
The code-generator is a Symfony Console application defined in `/bin/generate`.

## Usage
This can be run this using attached docker compose.
```bash
docker compose run --rm php ...
```

For first time usage the `/vendor` composer directory needs to be populated:
```bash
docker compose run --rm php composer install
```

The generator application can be run either via composer:
```bash
docker compose run --rm php composer run generate <...>
```
or directly:
```bash
docker compose run --rm php ./bin/generate <...>
```

Generating all files as predefined in application:
```bash
docker compose run --rm php ./bin/generate all
```
or list available generators
```bash
docker compose run --rm php ./bin/generate list
```
