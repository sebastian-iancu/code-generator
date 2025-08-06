# Development Guidelines for XMI Code Generator

This document provides guidelines for developing and maintaining the XMI Code Generator project.

## Build/Configuration Instructions

### Docker Setup

The project uses Docker for development and execution:

```bash
# First-time setup (install dependencies)
docker compose run --rm php composer install

# Run the generator application
docker compose run --rm php ./bin/generate <command>

# Run all predefined generators
docker compose run --rm php ./bin/generate all

# List available commands
docker compose run --rm php ./bin/generate list
```

### Environment Requirements

- PHP 8.3
- Docker and Docker Compose
- Required PHP extensions: json, libxml, simplexml

### Project Structure

- `/src`: Main source code
- `/code`: Generated output files and XMI schema files (input)
- `/bin`: Console application entry points
- `/tests`: Test files and configuration

## Testing Information

### Running Tests

Tests are executed using PHPUnit:

```bash
# Run tests using composer script
docker compose run --rm php composer run phpunit

# Run tests directly
docker compose run --rm php ./vendor/bin/phpunit --configuration tests/phpunit.xml
```

### Test Structure

- Tests should be placed in the `/tests` directory
- Test files should follow the naming convention `*Test.php`
- Test classes should extend `PHPUnit\Framework\TestCase`
- Test methods should be prefixed with `test`

### Adding New Tests

1. Create a new test file in the `/tests` directory (or appropriate subdirectory)
2. Name the file with the suffix `Test.php` (e.g., `MyFeatureTest.php`)
3. Define a class that extends `PHPUnit\Framework\TestCase`
4. Implement test methods that begin with `test`

Example test file structure:

```php
<?php

namespace Tests\YourNamespace;

use PHPUnit\Framework\TestCase;
use OpenEHR\Tools\CodeGen\YourClass;

class YourClassTest extends TestCase
{
    public function testYourFeature(): void
    {
        // Arrange
        $instance = new YourClass();
        
        // Act
        $result = $instance->yourMethod();
        
        // Assert
        $this->assertEquals('expected value', $result);
    }
}
```

### Code Quality Tools

The project includes several code quality tools:

- **PHPStan**: Static analysis tool
  ```bash
  docker compose run --rm php composer run phpstan
  ```

- **PHP Parallel Lint**: Syntax checker
  ```bash
  docker compose run --rm php composer run phplint
  ```

## Additional Development Information

### Code Style

The project follows PSR-4 autoloading standards. While there's no explicit code style configuration, the code generally follows PSR-12 coding style guidelines.

### Generator Commands

The main functionality is exposed through the Symfony Console application in `/bin/generate`. New generator commands should:

1. Be placed in the `bin/Command` directory
2. Extend the Symfony Console Command class
3. Be registered in the console application

### Working with XMI Files

- XMI schema files should be placed in the `/schemas` directory
- Generated output is written to the `/code` directory
- The application supports generating:
  - BMM JSON files
  - Internal model files

### Debugging

For debugging, you can:

1. Use the Symfony Console's verbose mode (`-v`, `-vv`, or `-vvv`)
2. Add logging to your code
3. Run the application with Xdebug (requires additional Docker configuration)

### Common Issues

- If you encounter memory issues, adjust the memory limit in `tests/phpunit.xml` or use the `-d memory_limit=XXX` PHP option
- For Docker permission issues, check that the user ID in `docker-compose.yml` matches your host user ID
