# Larahammer Generator

[![Latest Version on Packagist](https://img.shields.io/packagist/v/larahammer/generator.svg?style=flat-square)](https://packagist.org/packages/larahammer/generator)
[![Total Downloads](https://img.shields.io/packagist/dt/larahammer/generator.svg?style=flat-square)](https://packagist.org/packages/larahammer/generator)
[![License](https://img.shields.io/github/license/larahammer/generator.svg?style=flat-square)](LICENSE.md)

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
composer require larahammer/generator
```

That's it. Laravel auto-discovers the package via its Service Provider.

---

## Usage

```bash
php artisan larahammer:make {ModelName} {field:type ...}
```

### Basic Example

```bash
php artisan larahammer:make Product name:string price:decimal stock:integer status:enum(active,inactive)
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
php artisan larahammer:make Product name:string price:decimal --target=api
php artisan larahammer:make Post title:string body:text --target=filament
php artisan larahammer:make Order amount:decimal note:text:nullable --target=all
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
php artisan larahammer:make User email:string:unique bio:text:nullable
```

---

## What Gets Generated

For a command like:
```bash
php artisan larahammer:make Post title:string body:text published:boolean --target=all
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

## Advanced Options

### Generate with Role System

Add user roles with automatic migration and seeder:

```bash
php artisan larahammer:make Product name:string --with-roles
```

This generates:
- `app/Models/Role.php` — Role model with relationships
- `database/migrations/xxxx_create_roles_table.php` — Roles table
- `database/seeders/RoleSeeder.php` — Seed predefined roles

**Seed roles:**
```bash
php artisan db:seed --class=RoleSeeder
```

Default roles created: `admin`, `editor`, `viewer`, `user`

### Generate Filament Admin Panel

Create a complete admin panel with User and Role management:

```bash
php artisan larahammer:make Product name:string --with-admin
```

This generates:
- `app/Filament/Resources/UserResource.php` — User management with role selection
- `app/Filament/Resources/RoleResource.php` — Role management
- All necessary Filament pages (List, Create, Edit)

**Access admin panel** at `/admin` (requires Filament v3 installed):
- User Management: `/admin/users`
- Role Management: `/admin/roles`

### Generate Landing Page

Create a ready-to-use landing page:

```bash
php artisan larahammer:make Product name:string --with-landing
```

This generates:
- `resources/views/landing.blade.php` — Beautiful landing page
- `app/Http/Controllers/LandingController.php` — Landing controller

**Add route to `routes/web.php`:**
```php
use App\Http\Controllers\LandingController;

Route::get('/', [LandingController::class, 'index'])->name('home');
```

### Generate Security Middleware

Add role-based access control and admin panel protection:

```bash
php artisan larahammer:make Product name:string --with-security-middleware
```

This generates:
- `app/Http/Middleware/CheckRole.php` — Role-based access control middleware
- `app/Http/Middleware/AdminPanelProtection.php` — Admin panel protection middleware
- Updated `app/Models/User.php` with role relationships and helper methods

**Register middleware in `app/Http/Kernel.php`:**
```php
protected $routeMiddleware = [
    // ... other middleware ...
    'role' => \App\Http\Middleware\CheckRole::class,
    'admin' => \App\Http\Middleware\AdminPanelProtection::class,
];
```

**Usage in routes:**
```php
// Restrict to specific roles
Route::middleware('role:admin,editor')->group(function () {
    Route::get('/content', ContentController::class);
});

// Restrict to admin only
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/dashboard', AdminController::class);
});
```

**User model helpers:**
```php
auth()->user()->hasRole('admin');           // Check single role
auth()->user()->hasAnyRole(['admin', 'editor']); // Check multiple roles
auth()->user()->isAdmin();                  // Check if admin
```

### Combine Options

```bash
php artisan larahammer:make Product name:string price:decimal \
  --target=filament \
  --with-roles \
  --with-admin \
  --with-landing \
  --with-security-middleware
```

---

## After Generation

Standard setup steps:

```bash
php artisan migrate
php artisan db:seed --class=ProductSeeder
php artisan serve
```

**If using `--with-roles`:**
```bash
php artisan db:seed --class=RoleSeeder  # seed roles
```

**If using `--with-admin`:**
- Ensure Filament v3 is installed: `composer require filament/filament`
- Run admin panel: `php artisan serve`
- Access at: `http://localhost:8000/admin`

**If using `--with-landing`:**
- Register the landing route in `routes/web.php`
- Landing page accessible at home route

---

## Advanced Features — 3 Phases

### **Phase 1: Factories & Data Management**

#### Generate Model Factories

```bash
php artisan larahammer:make Product name:string price:decimal --with-factories
```

**Generates:** `database/factories/ProductFactory.php` with auto-generated Faker data

**Usage:**
```php
// Create 10 products
Product::factory(10)->create();

// Create with specific state
Product::factory(5)->active()->create();

// Create with relationships
Product::factory(20)
    ->has(Review::factory(3))
    ->has(Tag::factory(5))
    ->create();
```

**Enhanced Seeder:** Automatically generates advanced seeder with factory usage instead of basic array data.

#### Add Soft Deletes

```bash
php artisan larahammer:make Product name:string --with-soft-deletes
```

**Generates:**
- Migration with `softDeletes()` column
- Model with `SoftDeletes` trait
- Automatic scopes for include/exclude deleted records

**Usage:**
```php
$product->delete();              // Soft delete
$product->restore();              // Restore
$product->forceDelete();           // Permanent delete
Product::withTrashed()->get();    // Include soft deleted
Product::onlyTrashed()->get();    // Only soft deleted
```

---

### **Phase 2: Security & Authorization**

#### Generate Authorization Policies

```bash
php artisan larahammer:make Product name:string --with-policies
```

**Generates:** `app/Policies/ProductPolicy.php` with CRUD authorization gates

**Features:**
- `viewAny()`, `view()` — View authorization
- `create()` — Creation authorization
- `update()` — Update authorization (checks ownership)
- `delete()` — Delete authorization
- `restore()` / `forceDelete()` — Soft delete operations

**Usage in Controller:**
```php
public function update(Product $product)
{
    $this->authorize('update', $product);
    // Your update logic
}
```

**Usage in Blade:**
```blade
@can('update', $product)
    <button>Edit Product</button>
@endcan
```

**Register Policy** (in `app/Providers/AuthServiceProvider.php`):
```php
protected $policies = [
    Product::class => ProductPolicy::class,
];
```

#### API Authentication & Rate Limiting

```bash
php artisan larahammer:make Product name:string --with-api-auth
```

**Generates:**
- `ApiAuthentication` middleware for token validation
- Rate limiting configuration
- Bearer token validation with Sanctum

**Setup in `routes/api.php`:**
```php
Route::middleware('api.auth')->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
});
```

**Create API token for client:**
```php
$token = auth()->user()->createToken('api-token')->plainTextToken;
```

**Rate limiting:**
```php
Route::middleware('throttle:100,1')->group(function () {
    // Max 100 requests per minute
    Route::get('/products/search', SearchController::class);
});
```

---

### **Phase 3: Testing & Auditing**

#### Generate Automated Tests

```bash
php artisan larahammer:make Product name:string --with-tests
```

**Generates:**
- `tests/Feature/ProductCrudTest.php` — Full CRUD feature tests
- `tests/Unit/ProductTest.php` — Unit tests
- Test helpers with factory integration

**Feature Tests Include:**
- Test list/show/create/update/delete operations
- Test validation failures
- Test authentication requirements
- Response assertions

**Run tests:**
```bash
php artisan test
php artisan test tests/Feature/ProductCrudTest.php
php artisan test --filter=test_can_create_record
```

#### Activity Logging & Audit Trail

```bash
php artisan larahammer:make Product name:string --with-audit-log
```

**Generates:**
- `ActivityLog` model and migration
- `ProductObserver` class for automatic logging
- Audit trail with before/after changes

**Features:**
- Automatically logs: created, updated, deleted, restored events
- Records: user, IP address, user agent, timestamp
- Stores: before and after data for changes
- JSON-storable change data

**Register observer** (in `app/Providers/EventServiceProvider.php`):
```php
use App\Models\Product;
use App\Observers\ProductObserver;

public function boot(): void
{
    Product::observe(ProductObserver::class);
}
```

**Query activity logs:**
```php
// Get all activity for a product
$product->activityLogs()->get();

// Get creation activity only
ActivityLog::byAction('created')->byModel(Product::class)->get();

// Get recent activity (last 24 hours)
ActivityLog::recent()->get();

// Check who updated what
$log = ActivityLog::where('action', 'updated')->first();
echo $log->user->name;           // Who made the change
echo $log->changes['before'];    // Old data
echo $log->changes['after'];     // New data
```

---

## Super Command — All Features Combined

Generate everything at once:

```bash
php artisan larahammer:make Product \
  name:string \
  price:decimal \
  stock:integer \
  status:enum(active,inactive) \
  --target=filament \
  --with-roles \
  --with-admin \
  --with-landing \
  --with-security-middleware \
  --with-factories \
  --with-soft-deletes \
  --with-policies \
  --with-api-auth \
  --with-tests \
  --with-audit-log
```

**This generates 80+ files instantly with:**
- ✅ Complete CRUD (Migration, Model, Controller)
- ✅ Role system & admin panel (Filament)
- ✅ Landing page
- ✅ Security middleware
- ✅ Model factories with Faker
- ✅ Soft deletes
- ✅ Authorization policies
- ✅ API authentication & rate limiting
- ✅ Feature & unit tests
- ✅ Activity logging with audit trail



## Customizing Stubs

Publish the stubs to your project and modify them freely:

```bash
php artisan vendor:publish --tag=larahammer-stubs
```

Stubs will be published to `stubs/larahammer/`. Larahammer will use your custom stubs over the package defaults automatically.

---

## Configuration

Publish the config:

```bash
php artisan vendor:publish --tag=larahammer-config
```

Options in `config/larahammer.php`:

```php
'default_target' => 'blade',   // Skip the prompt — set your default
'force'          => false,     // Overwrite existing files by default
```

---

## Force Overwrite

```bash
php artisan larahammer:make Product name:string --force
```

---

## License

MIT — free to use in personal and commercial projects.
