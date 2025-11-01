# ModelMeta

[![PHP Version](https://img.shields.io/badge/php-%5E8.4-blue)](https://www.php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

**ModelMeta** is a PHP package to define metadata for models, including fields, types, validation rules, and relationships.

---

## Features

* Define model fields with **type-safe classes** (`TextField`, `EmailField`, `EnumField`, etc.)
* Fluent API for setting **validation rules**, `nullable`, `required`, `default`, `sortable`, `unique` and more
* Supports numeric ranges (`min`, `max`) and precision (`step`) for decimal/float fields
* Explicit **enum values** support
* Controls for **visibility** in forms and tables (`showInForm`, `showInTable`)
* Readonly and hidden field flags
* Integration-ready for custom admin panels or CRUD generators
* Ready for automated tests with Pest

---

## Installation

```bash
composer require glugox/model-meta
```

---

## Basic Usage

```php
use Glugox\ModelMeta\Fields\Text;
use Glugox\ModelMeta\Fields\Email;
use Glugox\ModelMeta\Fields\Enum;
use Glugox\ModelMeta\Fields\Decimal;
use Glugox\ModelMeta\FieldType;

class UserMeta
{
    public function fields(): array
    {
        return [
            Text::make('first_name')->required()->sortable(),
            Text::make('last_name')->nullable(),
            Email::make('email')->required(),
            Enum::make('role', ['admin', 'editor', 'user']),
            Decimal::make('balance')->default(0)->step(0.01)->min(0),
        ];
    }
}
```

---

## Fluent API Example

```php
use Glugox\ModelMeta\Fields\Text;

$field = Text::make('username')
    ->required()
    ->nullable() // automatically adds 'nullable' rule
    ->default('guest')
    ->sortable()
    ->unique('users', 'username');
```

---

## Field Types

Supported field types include:

* Basic: `ID`, `String`, `Text`, `LongText`, `MediumText`, `Char`
* Numbers: `Integer`, `SmallInteger`, `TinyInteger`, `BigInteger`, `Decimal`, `Float`, `Double`
* Special: `Email`, `Password`, `Phone`, `Username`, `Slug`, `URL`, `UUID`, `Token`, `Secret`
* Date/Time: `Date`, `DateTime`, `Time`, `Timestamp`, `Year`
* Boolean / Binary: `Boolean`, `Binary`
* File: `File`, `Image`
* JSON: `JSON`, `JSONB`
* Enumeration: `Enum` (with values)

---

## Relations

Currently supports:

* `BelongsTo`
* `HasOne`
* `HasMany`
* `BelongsToMany`

Define relations via dedicated relation classes when building meta for entities.

---

## Testing

Pest is recommended:

```bash
composer require pestphp/pest --dev
```

Example test:

```php
use Dummies\UserMeta;
use Glugox\ModelMeta\FieldType;

it('defines UserMeta fields', function () {
    $meta = new UserMeta();
    $fields = $meta->fields();

    expect($fields)->toHaveCount(5)
        ->and($fields[0]->type)->toBe(FieldType::STRING)
        ->and($fields[0]->required)->toBeTrue();
});
```

---

## Contributing

Contributions are welcome! Please open issues or pull requests.

---

## License

MIT License. See [LICENSE](LICENSE) file for details.
