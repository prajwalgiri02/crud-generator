# 🚀 Laravel Instant CRUD Generator

A powerful Laravel package to instantly scaffold complete CRUD modules (Model, Migration, Controller, Requests, Resources, Views, and Routes) using a single Artisan command.

Designed for developers who want to avoid boilerplate while maintaining full control through clean inheritance and lifecycle hooks.

---

## ✨ Features

- **Blazing Fast**: Generate a full module in seconds.
- **Full Scaffolding**: Generates Model, Migration, Controller, Store/Update Requests, API Resources, and Blade Views.
- **Smart Separation**: Automatically generates appropriate logic for both Web (Blade views) and API (JSON) interfaces.
- **Thin Controllers**: Generated controllers are lightweight and easy to maintain.
- **API Resources**: Automatic data transformation for API responses.
- **Modern UI**: Blade views generated with Tailwind CSS (Breeze compatible).
- **Lifecycle Hooks**: Easy customization via `beforeCreate`, `afterCreate`, `beforeUpdate`, and `afterUpdate`.
- **Customizable Pagination**: Configure pagination limits per controller.
- **Advanced Features**: Built-in support for Search and Soft Deletes.

---

## 🔧 Installation

Install the package via composer:

```bash
composer require prajwalgiri/crud-generator
```

The package will automatically register itself using Laravel's package discovery.

---

## ⚙️ Usage (The CLI)

The core command is `php artisan crud:generate`.

### 1. Simple Web CRUD

```bash
php artisan crud:generate Post --fields="title:string,content:text,is_published:boolean"
```

### 2. API CRUD (with Resources)

```bash
php artisan crud:generate Post --fields="title:string,content:text" --api
```

### 3. Advanced Options

- `--soft-deletes`: Adds soft delete support to Model, Migration, and Controller.
- `--search`: Adds a search bar and logic to your index page (Web).
- `--force`: Overwrites existing files.

---

## 🏗️ Field Definition Syntax

Define your schema directly in the command:
`--fields="name:type:option1:option2"`

| Feature        | Syntax                | Result                                 |
| :------------- | :-------------------- | :------------------------------------- |
| **Basic**      | `title:string`        | `$table->string('title')` + Text Input |
| **Large Text** | `content:text`        | `$table->text('content')` + Textarea   |
| **Nullable**   | `bio:string:nullable` | Allows empty values in DB & Validation |
| **Unique**     | `email:string:unique` | Adds unique constraint                 |
| **Boolean**    | `active:boolean`      | Checkbox in UI + Boolean in DB         |
| **Dates**      | `published_at:date`   | Date picker in UI                      |

---

## 🎮 Controller Customization

Generated controllers allow for easy customization without overriding core logic:

### Customization Properties

You can define these properties in your generated controllers:

```php
protected int $perPage = 15; // Custom pagination
protected ?string $redirectRoute = 'admin.dashboard'; // Custom redirect (Web)
protected string $createMessage = 'Successfully created!'; // Custom messages (Web)
```

### ⚓ Lifecycle Hooks

Use hooks in your generated controller to add custom logic:

```php
protected function beforeCreate(array $data, Request $request)
{
    $data['user_id'] = auth()->id();
    return $data; // Must return the data array
}

protected function afterCreate($model, $request)
{
    // Send notification, log activity, etc.
}
```

---

## 📄 License

MIT License. Built with ❤️ by Prajwal Giri.
