# LaraForge Generator

[![Latest Version on Packagist](https://img.shields.io/packagist/v/laraforge/generator.svg?style=flat-square)](https://packagist.org/packages/laraforge/generator)
[![Total Downloads](https://img.shields.io/packagist/dt/laraforge/generator.svg?style=flat-square)](https://packagist.org/packages/laraforge/generator)
[![License](https://img.shields.io/github/license/laraforge/generator.svg?style=flat-square)](LICENSE.md)

**Scaffold a complete Laravel CRUD in seconds.** One command generates migration, model, controller, form request, views, and routes — for Blade, Filament v3, or REST API.

---

## Requirements

| Package | Version |
|---|---|
| PHP | ^8.1 |
| Laravel | ^10.0 \| ^11.0 \| ^12.0 \| ^13.0 |
| Filament *(optional)* | ^3.0 |

---

## Installation

```bash
composer require laraforge/generator
```

That's it. Laravel auto-discovers the package via its Service Provider.

---

## Usage

```bash
php artisan laragen:make {ModelName} {field:type ...}
```

### Basic Example

```bash
php artisan laragen:make Product name:string price:decimal stock:integer status:enum(active,inactive)
```

You'll be prompted to choose an output target:

```
Select output target:
  [blade]    Blade + Tailwind
  [filament] Filament v3 Panel
  [api]      REST API (JSON)
  [all]      All of the above
```

### Skip the Prompt — Pass Target Directly

```bash
php artisan laragen:make Product name:string price:decimal --target=api
php artisan laragen:make Post title:string body:text --target=filament
php artisan laragen:make Order amount:decimal note:text:nullable --target=all
```

---

## Field Types

| Type | Migration Column | Notes |
|---|---|---|
| `string` | `string` | Default |
| `text` | `text` | |
| `integer` / `int` | `integer` | |
| `bigint` | `bigInteger` | |
| `decimal` | `decimal(15,2)` | |
| `boolean` / `bool` | `boolean` | |
| `date` | `date` | |
| `datetime` | `dateTime` | |
| `json` | `json` | Cast to array in model |
| `uuid` | `uuid` | |
| `foreignId` | `foreignId()->constrained()` | Auto cascade |
| `enum(a,b,c)` | `enum` | Values in parentheses |

### Modifiers

Append `:nullable` or `:unique` after the type:

```bash
php artisan laragen:make User email:string:unique bio:text:nullable
```

---

## What Gets Generated

For a command like:
```bash
php artisan laragen:make Post title:string body:text published:boolean --target=all
```

```
✓ Migration    → database/migrations/xxxx_create_posts_table.php
✓ Model        → app/Models/Post.php
✓ Request      → app/Http/Requests/PostRequest.php
✓ Seeder       → database/seeders/PostSeeder.php
✓ Controller   → app/Http/Controllers/PostController.php         (blade)
✓ Views        → resources/views/posts/{index,create,edit,show}  (blade)
✓ Controller   → app/Http/Controllers/Api/PostController.php     (api)
✓ Resource     → app/Http/Resources/PostResource.php             (api)
✓ Resource     → app/Filament/Resources/PostResource.php         (filament)
✓ Pages        → app/Filament/Resources/PostResource/Pages/      (filament)
✓ Routes       → injected into routes/web.php and routes/api.php
```

---

## After Generation

```bash
php artisan migrate
php artisan db:seed --class=PostSeeder
php artisan serve
```

For Filament, make sure you've run:
```bash
php artisan filament:install
```

---

## Customizing Stubs

Publish the stubs to your project and modify them freely:

```bash
php artisan vendor:publish --tag=laraforge-stubs
```

Stubs will be published to `stubs/laraforge/`. LaraForge will use your custom stubs over the package defaults automatically.

---

## Configuration

Publish the config:

```bash
php artisan vendor:publish --tag=laraforge-config
```

Options in `config/laraforge.php`:

```php
'default_target' => 'blade',   // Skip the prompt — set your default
'force'          => false,     // Overwrite existing files by default
```

---

## Force Overwrite

```bash
php artisan laragen:make Product name:string --force
```

---

## License

MIT — free to use in personal and commercial projects.
