# Magic

[![PHP Version](https://img.shields.io/badge/php-%5E8.4-blue)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
![Coverage](https://codecov.io/gh/glugox/magic/branch/main/graph/badge.svg)

Magic is a lightweight PHP/Laravel toolkit that **auto-generates application files from entity configurations** stored in [JSON files](stubs/samples/inventory.json).

It streamlines tasks like database migrations, seeding, and realistic data generation with Faker — making it ideal for testing, prototyping, and automation.

With Magic, you can build full-featured Laravel applications directly from configuration files, eliminating repetitive boilerplate and speeding up development.


Watch on YouTube:

[![Watch the video](https://img.youtube.com/vi/4nD5k1bXGmA/hqdefault.jpg)](https://www.youtube.com/watch?v=8DZjpWIrGAE)

---

## 🔧 How it works

![magic-demo.svg](magic-demo.svg)

---

## 🚀 Quick Start

1. **Create a new Laravel project** (if you don’t have one yet):

   ```bash
   laravel new my-magic-app
   ```

   When prompted:
    - Choose **Vue** starter.
    - Choose **Laravel’s built-in authentication**.
    - Choose **Pest** for testing.
    - Allow Laravel to install the required packages.

2. **Navigate into your project**:

   ```bash
   cd my-magic-app
   ```

3. **Install Magic via Composer**:

   ```bash
   composer require glugox/magic --dev
   ```

4. **Publish the configuration file**:

   ```bash
   php artisan vendor:publish --provider="Glugox\Magic\MagicServiceProvider"
   ```

5. **Explore demo samples** (JSON config files in `stubs/samples`):

   ```bash
   php artisan magic:list-samples
   ```

6. **Build your app** using a starter:

   ```bash
   php artisan magic:build --starter=inventory
   ```

7. **Or use your own config file**:

   ```bash
   php artisan magic:build --config=path/to/your/config.json
   ```

   👉 See the [sample configurations](./stubs/samples) directory for examples.

8. **Run the app**:

   ```bash
   npm run dev
   php artisan serve
   ```

---

## ✨ Features

- **Entity Management** – Define entities with fields, relations, casts.
- **CRUD Generation** – Auto-generate CRUD for every entity.
- **Migrations** – Create DB migrations from your definitions.
- **Seeding** – Seed sample or Faker-based data.
- **Config-Driven** – JSON-powered structure & behavior.
- **Extensible** – Override stubs, extend generators.
- **Laravel Native** – Fully integrated into Laravel workflow.
- **Development Mode** – Quickly spin up test data.
- **Search & Sort** – Mark fields as searchable/sortable.
- **Hidden Fields** – Exclude fields from forms/views.
- **Casts** – Ensure correct field data types.
- **Relations** – Define `hasMany`, `belongsTo`, etc.

---

## 📦 Starter Kits

Use predefined configurations to bootstrap your app:

- Task Management
- E-commerce
- Education
- Resume Builder

**How to use:**

1. Pick a starter from the [samples](./stubs/samples) directory (use file name as starter name).
2. Run:

   ```bash
   php artisan magic:build --starter=task
   ```

---

## 🤝 Contributing / Contact

Got ideas or feedback?  
Reach out via [email](mailto:ervinbeciragic@gmail.com).

---

## 📄 License

The MIT License (MIT). See [LICENSE](LICENSE.md) for details.


composer require glugox/magic:@dev --prefer-source --repository='{"type":"path","url":"/Users/ervin/Code/github.com/glugox/magic"}'
