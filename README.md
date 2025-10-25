# ![logo.svg](https://raw.githubusercontent.com/glugox/cloud/refs/heads/main/public/logo.svg) Magic

[![PHP Version](https://img.shields.io/badge/php-%5E8.4-blue)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
![Coverage](https://codecov.io/gh/glugox/magic/branch/main/graph/badge.svg)

A Laravel code generator that turns JSON entity definitions into a working application or a reusable Composer package. Magic wires migrations, models, controllers, Vue/Inertia pages, tests, and boilerplate Laravel setup so you can prototype or bootstrap production features in minutes.


![Magic build workflow](magic-demo.svg)

---

## Table of contents

1. [What Magic does](#what-magic-does)
2. [Requirements](#requirements)
3. [Installation](#installation)
4. [Running the generator](#running-the-generator)
5. [Understanding configuration](#understanding-configuration)
6. [Package mode](#package-mode)
7. [File generation reference](#file-generation-reference)
8. [Resetting a build](#resetting-a-build)
9. [Customising stubs & namespaces](#customising-stubs--namespaces)
10. [Testing and quality tools](#testing-and-quality-tools)
11. [Troubleshooting](#troubleshooting)
12. [Contributing](#contributing)
13. [License](#license)

---

## What Magic does

Magic reads a JSON schema that describes your domain (entities, relations, presets, UI metadata) and executes a predictable build pipeline:

1. Resolve configuration and normalise paths.
2. Publish starter assets and language files.
3. Install the required Composer and NPM dependencies (host builds only).
4. Generate migrations, models, enums, factories, seeders, REST controllers, form requests, API resources, TypeScript definitions, Vue pages, and Pest tests.
5. Register routes, Sanctum middleware, queues, and Inertia bootstrapping when targeting a Laravel application.
6. Produce a manifest of generated files so resets can be executed safely.

Every build step is implemented as a dedicated action under `src/Actions/Build`, making the pipeline easy to extend or re-order for your own needs.

---

## Requirements

| Requirement | Version |
| --- | --- |
| PHP | ^8.4 |
| Laravel | 10.x – 12.x |
| Node.js & npm | Required for front-end scaffolding |
| Composer | Latest stable |

> **Tip:** Magic ships as a regular Laravel package. You can install it globally in a host Laravel app or use package mode to generate a standalone Composer package that you pull into multiple projects.

---

## Installation

### Install into a Laravel application

1. Create or open a Laravel application that already has Breeze/Inertia (Vue) installed.
2. Require Magic as a dev dependency:
   ```bash
   composer require glugox/magic --dev
   ```
3. Publish the configuration file:
   ```bash
   php artisan vendor:publish --provider="Glugox\\Magic\\MagicServiceProvider"
   ```
4. Optional: explore the sample configurations in `stubs/samples`.

### Install for package generation

If you plan to generate re-usable features as a package, install Magic in a throwaway Laravel project and run the generator with the package flags (described below). Magic will emit a new Composer package that you can require in your real application.

---

## Running the generator

Magic exposes one primary Artisan command:

```bash
php artisan magic:build [options]
```

| Option | Description |
| --- | --- |
| `--config=` | Absolute or relative path to a JSON configuration file. |
| `--starter=` | Name of a starter config shipped with Magic (e.g. `inventory`, `task`). |
| `--set=` | Inline overrides using dot notation (`--set=app.name="Acme"`). Repeatable. |
| `--package-path=` | Destination directory for Composer package builds. Creates the folder if missing. |
| `--package-namespace=` | Root PSR-4 namespace to use when generating a package (e.g. `Acme\Inventory`). |
| `--package-name=` | Composer package name for package builds (e.g. `acme/inventory-kit`). |

Examples:

```bash
# Generate directly into the Laravel application
php artisan magic:build --starter=inventory

# Generate a package under ./packages/inventory-kit
php artisan magic:build \
  --config=stubs/samples/inventory.json \
  --package-path=packages/inventory-kit \
  --package-namespace=Acme\\Inventory \
  --package-name=acme/inventory-kit
```

Magic prevents accidental overwrites by checking for `storage/magic/generated_files.json`. Run `php artisan magic:reset` (see below) before triggering a new build in the same environment.

---

## Understanding configuration

* Configuration files follow `json-schema.json` and can be validated automatically by most editors.
* Each config declares global app metadata (name, base namespace overrides, Faker mappings, seeding flags) and an array of entities.
* Entities describe fields, relations, filters, table presets, navigation icons, and test scenarios.
* Sample configs live in [`stubs/samples`](stubs/samples); the `inventory.json` example demonstrates most features.

A minimal entity definition:

```json
{
  "app": {
    "name": "Inventory",
    "seedEnabled": true
  },
  "entities": [
    {
      "name": "Product",
      "icon": "Package",
      "fields": [
        { "name": "name", "type": "string", "rules": ["required"] },
        { "name": "sku", "type": "string", "unique": true },
        { "name": "price", "type": "decimal", "precision": 12, "scale": 2 }
      ],
      "relations": [
        { "type": "belongsTo", "name": "category", "entity": "Category" }
      ]
    }
  ]
}
```

When a build runs, the configuration is parsed into strongly typed objects (`src/Support/Config`) so generators can reason about default values and relationships safely.

---

## Package mode

Package mode rewires Magic so that **every generated file lands inside a Composer package** instead of your Laravel app. Three options must be provided: `--package-path`, `--package-namespace`, and `--package-name`.

Key behaviours:

* `MagicPaths` swaps Laravel helpers (app_path, database_path, etc.) for equivalents rooted inside the target package directory.
* `MagicNamespaces` ensures PHP namespaces resolve under your chosen base namespace (controllers, models, resources, providers, and tests are all updated).
* `InitializePackageAction` prepares the destination by creating a Laravel-like directory tree, generating a Composer manifest with PSR-4 autoloading, and scaffolding a `MagicPackageServiceProvider` that registers routes, views, migrations, and translations when installed into a host app.
* Host-only steps—environment tweaks, dependency installation, queue setup, Sanctum middleware mutations, and Vue bootstrapping—are skipped so the package remains framework-agnostic.
* Seeders generated inside a package avoid touching `DatabaseSeeder.php`; consumers can opt-in by calling the package service provider’s seeders manually.

After running the generator in package mode:

1. Commit the generated package to its own repository (or keep it inside a monorepo under `packages/`).
2. Require it from your real Laravel application via VCS or path repositories.
3. Register the generated service provider if you disable Laravel’s automatic package discovery.

---

## File generation reference

| Area | What gets generated |
| --- | --- |
| **Database** | Timestamped migrations, model factories, individual seeders. |
| **Domain** | Eloquent models, relationships, query scopes, enums, Meta classes, console commands for entity actions. |
| **HTTP layer** | API controllers, form requests, API resources, routes (`routes/app.php`, `routes/app/api.php`) and attachable asset support. |
| **Front-end** | Vue 3 pages (index, create, edit, detail), shared components, TypeScript DTOs, lucide icon imports, composables. |
| **Testing** | Pest feature tests for CRUD operations, HTTP tests, and package-specific coverage. |
| **DevOps** | Magic manifest under `storage/magic`, queue installation helpers, `.env` updates (host builds only).

Refer to the corresponding action classes in `src/Actions/Build` for implementation details.

---

## Resetting a build

Use the reset command when you need to revert the generated artefacts inside a Laravel application:

```bash
php artisan magic:reset --starter=inventory
```

`magic:reset` replays the manifest from `storage/magic/generated_files.json`, deletes migrations, models, seeders, controllers, Vue pages, TypeScript artefacts, and restores modified Laravel files to their original state before optionally refreshing the database. Package builds can be reset manually by deleting the destination directory and re-running `magic:build`.

---

## Customising stubs & namespaces

* All PHP and Vue stubs live under [`stubs/`](stubs); copy them into your application and update `config/magic.php` if you need to override defaults.
* `MagicNamespaces` lets you change the base namespace for generated classes. Provide the `--package-namespace` option or edit the configuration’s `app.namespace` value when targeting a Laravel app.
* `MagicPaths` centralises every filesystem lookup so package builds, tests, and workbench usage stay isolated. Clearing paths/namespaces after each command prevents leakage into subsequent Artisan calls.

---

## Testing and quality tools

The project uses Pest, PHPStan, Laravel Pint, and Rector. Run everything locally with:

```bash
composer install
composer test      # pest + pint --test
composer analyse   # phpstan
composer format    # pint (fixes)
```

When contributing, make sure your feature and unit tests cover new behaviour—especially when altering generators or stubs.

---

## Troubleshooting

* **`Manifest file exist` error** – run `php artisan magic:reset` before starting a new build in the same Laravel project.
* **Missing Vue components in package builds** – Magic scaffolds Vue files from built-in stubs when `resources/js/components/AppSidebar.vue` or `AppLogo.vue` is absent.
* **Routes not registered after installing a package** – confirm that the generated `MagicPackageServiceProvider` is discovered (Composer autoload dump + Laravel package discovery) and that `routes/app.php` and `routes/app/api.php` exist in your package.
* **Need to skip seeding in a package** – by design Magic avoids touching `DatabaseSeeder.php` during package builds; call package seeders manually from the consuming app if required.

---

## Contributing

Issues and pull requests are welcome! If you are planning a large change, please open an issue first so we can discuss scope and direction. When submitting a PR:

1. Add or update tests.
2. Run the QA commands listed above.
3. Document user-facing changes in this README or the changelog.

You can also reach out directly at [ervinbeciragic@gmail.com](mailto:ervinbeciragic@gmail.com).

---

## License

Magic is open-sourced software licensed under the [MIT license](LICENSE.md).
