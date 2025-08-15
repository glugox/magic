# Magic

[![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

Magic — A lightweight PHP/Laravel toolkit for effortless configuration management, database seeding, and realistic fake data generation with Faker. Perfect for testing, prototyping, and automation.
Build full-featured apps from configuration files.

🔧 How it works
![magic-demo.svg](magic-demo.svg)

## Installation

### 1. Install via Composer

```bash
composer require glugox/magic
```

### 2. Publish the configuration file

```bash
php artisan vendor:publish --provider="Glugox\Magic\MagicServiceProvider"
```

### 3. Configure the package
Edit the `config/magic.php` file to set up your application configuration. This file allows you to define various settings and behaviors for your app.

### 4. Create your app configuration file 
You need app configuration file as json file in which you can define your app configuration like
```json
{
    "app": {
        "name": "UNO"
    },
    "entities": [
        {
            "name": "User",
            "fields": [
                { "name": "id", "type": "bigIncrements", "nullable": false },
                { "name": "name", "type": "string", "nullable": false, "sortable": true, "searchable": true },
                { "name": "email", "type": "string", "nullable": false, "unique": true, "sortable": true, "searchable": true },
                { "name": "password", "type": "string", "nullable": false, "hidden": true }
            ],
            "relations": [
                { "type": "hasMany", "entity": "Address", "foreign_key": "user_id" },
                { "type": "hasMany", "entity": "Resume", "foreign_key": "user_id" }
            ],
            "casts": {
                "email_verified_at": "datetime"
            }
        },
        {
            "name": "Address",
            "fields": [
                { "name": "id", "type": "bigIncrements", "nullable": false },
                { "name": "user_id", "type": "unsignedBigInteger", "nullable": false },
                { "name": "street", "type": "string", "nullable": false, "searchable": true },
                { "name": "city", "type": "string", "nullable": false, "searchable": true },
                { "name": "country", "type": "string", "nullable": false, "searchable": true },
                { "name": "postal_code", "type": "string", "nullable": true, "searchable": true }
            ],
            "relations": [
                { "type": "belongsTo", "entity": "User", "foreign_key": "user_id" }
            ]
        },
        {
            "name": "Resume",
            "fields": [
                { "name": "id", "type": "bigIncrements", "nullable": false },
                { "name": "user_id", "type": "unsignedBigInteger", "nullable": false },
                { "name": "title", "type": "string", "nullable": false, "searchable": true },
                { "name": "content", "type": "longText", "nullable": false, "searchable": true }
            ],
            "relations": [
                { "type": "belongsTo", "entity": "User", "foreign_key": "user_id" }
            ]
        }
    ],
    "dev": {
        "seedEnabled": true,
        "seedCount": 20
    }
}
```
You can place this file wherever you want, but make sure to update the `config/magic.php` file with the correct path to your configuration file,
or you can pass the path to the command line when running the magic command.

### 5. Run the magic (command)

```bash
php artisan magic:build --config=path/to/your/config.json
```

### 6. Woala! Your app is ready!

Run the standard Laravel commands to start your application:

```bash
nom run dev && php artisan serve
```

## Magic Features

- **Entity Management**: Define entities with fields, relations, and casts.
- **CRUD Operations**: Automatically generate CRUD operations for each entity.
- **Database Migrations**: Create migrations based on your entity definitions.
- **Seed Data**: Optionally seed your database with sample data.
- **Configuration**: Use a JSON configuration file to define your app's structure and behavior.
- **Customizable**: Easily extend and customize the generated code to fit your needs.
- **Laravel Integration**: Seamlessly integrates with Laravel's ecosystem.
- **Development Mode**: Enable development mode to seed your database with sample data.
- **Searchable and Sortable Fields**: Define fields that can be searched and sorted in your application.
- **Hidden Fields**: Specify fields that should be hidden in the generated forms and views.
- **Casts**: Define casts for fields to ensure data types are handled correctly.
- **Relations**: Define relationships between entities, such as `hasMany` and `belongsTo`.
- 

## Starters

- **Starter Kits**: Use the provided starter kits to quickly set up your application with predefined entities and configurations.

- **These are just predefined configurations that you can use to start your application quickly.**

Available starter kits:

- Task Management
- E-commerce
- Education
- Resume Builder

How to use a starter kit:
1. Choose a starter kit from the [starter kits](./stubs/samples) directory. Use the file name as the starter kit name.
2. Run the magic command with the `--starter` option:

```bash
php artisan magic:build --starter=task-management
```

## Ready to join?

Contact me at [email](mailto:ervinbeciragic@gmail.com) for any questions or suggestions.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
