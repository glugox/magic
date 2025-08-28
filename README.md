# Magic

[![PHP Version](https://img.shields.io/badge/php-%5E8.4-blue)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

Lightweight PHP/Laravel toolkit designed to auto-generate all essential application files from entity configurations stored in [JSON files](stubs/samples/inventory.json). It streamlines tasks like database seeding and realistic data generation using Faker, making it perfect for testing, prototyping, and automation.

With Magic, you can build full-featured Laravel applications directly from configuration files, eliminating repetitive boilerplate and speeding up development.

🔧 How it works
![magic-demo.svg](magic-demo.svg)

## Quick Start

1. Create a new Laravel project (if you don't have one already):

```bash
laravel new my-magic-app
```

- Choose Vue starter when prompted.
- Choose Laravel's built-in authentication when prompted.
- Choose Pest for testing when prompted.
- Allow Laravel to install the necessary packages when prompted.

2. Navigate to your project directory:

```bash
cd my-magic-app
```

3. Install the Magic package via Composer:

```bash
composer require glugox/magic
```
4. Publish the configuration file:

```bash
php artisan vendor:publish --provider="Glugox\Magic\MagicServiceProvider"
```

5. For quick start Magic has some demo samples which are just json configuration files located in the `stubs/samples` directory. You can use one of these samples to quickly set up your application.
To list all available samples, you can run:

```bash 
php artisan magic:list-samples
```

6. Run the magic build command with your chosen sample or your own configuration file:

```bash
php artisan magic:build --starter=inventory
```

7. If you have your own json configuration file, you can run:

```bash
php artisan magic:build --config=path/to/your/config.json
```

To see how to create your own configuration file, check the [sample configurations](./stubs/samples) directory.


8. Start your application:

```bash
npm run dev && php artisan serve
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
