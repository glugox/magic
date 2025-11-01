# AI Toolset

[![Latest Version on Packagist](https://img.shields.io/packagist/v/glugox/ai.svg?style=flat-square)](https://packagist.org/packages/glugox/ai)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/glugox/ai/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/glugox/ai/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/glugox/ai/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/glugox/ai/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/glugox/ai.svg?style=flat-square)](https://packagist.org/packages/glugox/ai)

AI toolset for Glugox. This package provides a set of tools and utilities to integrate AI capabilities into your applications.

## Installation

You can install the package via composer:

```bash
composer require glugox/ai
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="ai-config"
```

This is the contents of the published config file:

```php
return [
];
```

## Usage

```php
echo Ai::ask("What is 2 + 2?")->text();
// Outputs: 4
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [ervin](https://github.com/glugox)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
