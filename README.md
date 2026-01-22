# CRUD Generator Package for Laravel

A simple local Laravel package to instantly scaffold complete CRUD modules using a single Artisan command. Built on top of Laravel Breeze for rapid admin panel development.

---

## ✨ Features

- Generate model, migration, controller, request, views, and routes
- **Unified SuperController**: A single base class handles both Web (Blade/Redirects) and API (JSON) logic dynamically.
- **Thin Controllers**: Generated controllers are lightweight, containing only configuration.
- Uses model's `$fillable` fields for automatic mass assignment
- Lifecycle hooks: `beforeCreate`, `afterCreate`, `beforeUpdate`, `afterUpdate`
- Blade view generation with simple form and table layout
- Laravel Breeze compatible (uses `layouts.app`)

---

## 🔧 Installation

1. **Create local package directory**:

   ```bash
   mkdir -p packages/prajwal/CrudGenerator
   ```

2. **Update `composer.json` autoload**:

   ```json
   "autoload": {
       "psr-4": {
           "App\\": "app/",
           "prajwal\\CrudGenerator\\": "packages/prajwal/CrudGenerator/src/"
       }
   }
   ```

   Then run:

   ```bash
   composer dump-autoload
   ```

3. **Register the command in `AppServiceProvider`**:

   In `app/Providers/AppServiceProvider.php`:

   ```php
   use prajwal\CrudGenerator\Commands\CrudGenerateCommand;

   public function boot(): void
   {
       if ($this->app->runningInConsole()) {
           $this->commands([
               CrudGenerateCommand::class,
           ]);
       }
   }
   ```

---

## ⚙️ Usage

### Generate Web CRUD

```bash
php artisan crud:generate Post --fields="title:string,content:text"
```

### Generate API CRUD

```bash
php artisan crud:generate Post --fields="title:string,content:text" --api
```

### Generated Files:

- `app/Models/Post.php`
- `database/migrations/...create_posts_table.php`
- `app/Http/Requests/StorePostRequest.php`
- `app/Http/Controllers/PostController.php` (Extends `SuperController`)
- Blade views in `resources/views/posts/` (Web only)
- Routes appended to `routes/web.php` or `routes/api.php`

---

## 🔄 SuperController

All generated controllers extend `SuperController` which lives in:

```
packages/prajwal/CrudGenerator/src/Http/Controllers/SuperController.php
```

### Responsibilities:

- **Dual Mode Support**: Automatically switches between JSON (API) and Blade Views (Web) based on the request type or `$isApi` property.
- **Configuration Based**: Generated controllers only need to define `$model` and `$request`.
- **Dynamic Routing**: Automatically computes view paths and route names based on the model.

### Lifecycle Hook Methods:

```php
protected function beforeCreate(array $data, Request $request) { ... }
protected function afterCreate(Model $model, Request $request) { ... }
protected function beforeUpdate(array $data, Request $request, Model $model) { ... }
protected function afterUpdate(Model $model, Request $request) { ... }
```

---

## 👁‍🗨️ Example Controller Output (Web)

```php
namespace App\Http\Controllers;

use App\Models\Post;
use App\Http\Requests\StorePostRequest;
use prajwal\CrudGenerator\Http\Controllers\SuperController;

class PostController extends SuperController
{
    protected string $model = Post::class;
    protected ?string $request = StorePostRequest::class;
}
```

---

## 🚀 Roadmap Ideas

- Auto-generate `$fillable` fields from DB schema
- Smart form inputs based on column types
- Relationship detection and rendering
- File/image upload support (Basic support already in SuperController)

---

## 📄 License

This package is personal-use only. Built by Prajwal.
