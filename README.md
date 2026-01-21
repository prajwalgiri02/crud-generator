# CRUD Generator Package for Laravel

A simple local Laravel package to instantly scaffold complete CRUD modules using a single Artisan command. Built on top of Laravel Breeze for rapid admin panel development.

---

## ✨ Features

-   Generate model, migration, controller, request, views, and routes
-   SuperController handles all CRUD logic dynamically
-   Uses model's `$fillable` fields for automatic mass assignment
-   Lifecycle hooks: `beforeCreate`, `afterCreate`, `beforeUpdate`, `afterUpdate`
-   Blade view generation with simple form and table layout
-   Laravel Breeze compatible (uses `layouts.app`)

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

Generate a new CRUD module by running:

```bash
php artisan crud:generate Post
```

This will generate:

-   `app/Models/Post.php`
-   `database/migrations/...create_posts_table.php`
-   `app/Http/Requests/StorePostRequest.php`
-   `app/Http/Controllers/PostController.php`
-   Blade views in `resources/views/posts/`
-   `Route::resource('posts', PostController::class);` appended to `routes/web.php`

---

## 🔄 SuperController

All generated controllers extend `SuperController` which lives in:

```
packages/prajwal/CrudGenerator/src/Http/Controllers/SuperController.php
```

### Responsibilities:

-   Handles all CRUD operations dynamically
-   Uses the provided model's `$fillable` fields
-   Uses a FormRequest for validation
-   Automatically returns views based on model name

### Lifecycle Hook Methods:

```php
protected function beforeCreate(array $data, Request $request) { ... }
protected function afterCreate(Model $model, Request $request) { ... }
protected function beforeUpdate(array $data, Request $request, Model $model) { ... }
protected function afterUpdate(Model $model, Request $request) { ... }
```

These allow custom logic like file uploads, notifications, logging, etc.

---

## 👁‍🗨️ Example Controller Output

```php
namespace App\Http\Controllers;

use App\Models\Post;
use App\Http\Requests\StorePostRequest;
use prajwal\CrudGenerator\Http\Controllers\SuperController;

class PostController extends SuperController
{
    protected string $model = Post::class;
    protected string $request = StorePostRequest::class;

    // Optional lifecycle methods:
    // protected function beforeCreate(array $data, $request) { ... }
    // protected function afterCreate($model, $request) { ... }
}
```

---

## 🚀 Roadmap Ideas (optional future additions)

-   Auto-generate `$fillable` fields from DB schema
-   Smart form inputs based on column types
-   Relationship detection and rendering
-   File/image upload support
-   API Resource generation

---

## 📄 License

This package is personal-use only. Built by Prajwal.
