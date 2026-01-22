<?php

namespace prajwal\CrudGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use prajwal\CrudGenerator\Services\GeneratorService;

class CrudGenerateCommand extends Command
{
    protected $signature = 'crud:generate 
                            {name : The name of the model (e.g. Post)}
                            {--fields= : Field definitions (e.g. title:string,content:text)}
                            {--force : Overwrite existing files}
                            {--api : Generate API Controller and Routes}
                            {--soft-deletes : Enable Soft Deletes}
                            {--search : Enable Search Functionality}
                            {--only= : model|request|controller|views|routes|all}';

    protected $description = 'Generate CRUD module (Model, Migration, Controller, Requests, Views, Routes)';

    protected $generatorService;

    public function __construct(GeneratorService $generatorService)
    {
        parent::__construct();
        $this->generatorService = $generatorService;
    }

    public function handle()
    {
        $input = trim($this->argument('name'), "/\\");
        $parts = preg_split('#[\\/]+#', $input);
        $modelName = Str::studly(array_pop($parts));
        $prefixParts = array_map([Str::class, 'studly'], $parts);
        $prefixNamespace = implode('\\', $prefixParts);
        $prefixSlash = implode('/', $prefixParts);

        $fieldsString = $this->option('fields');
        $fields = $this->generatorService->parseFields($fieldsString);

        $modelNamespace = 'App\\Models';
        $controllerNamespace = 'App\\Http\\Controllers' . ($prefixNamespace ? "\\{$prefixNamespace}" : '');
        $requestNamespace = 'App\\Http\\Requests' . ($prefixNamespace ? "\\{$prefixNamespace}" : '');

        $viewPath = implode('.', array_merge(array_map([Str::class, 'snake'], $parts), [Str::plural(Str::snake($modelName))]));
        $viewPath = trim($viewPath, '.'); // e.g. admin.posts
        
        $routeBase = $viewPath; // e.g. admin.posts

        $this->info("Generating CRUD for: {$modelName}");

        // Logic for Soft Deletes
        $softDeletesImport = '';
        $softDeletesTrait = '';
        $softDeletes = '';
        if ($this->option('soft-deletes')) {
            $softDeletesImport = "use Illuminate\Database\Eloquent\SoftDeletes;";
            $softDeletesTrait = ", SoftDeletes";
            $softDeletes = '$table->softDeletes();';
        }

        // Logic for Search
        $searchLogic = '';
        $searchInput = '';
        if ($this->option('search') && !$this->option('api')) {
             $searchLogic = $this->generatorService->generateSearchLogic($fields);
             $searchInput = $this->generatorService->generateSearchInput($routeBase);
        }

        $replacements = [
            '{{namespace}}' => $modelNamespace,
            '{{class}}' => $modelName,
            '{{table}}' => Str::snake(Str::plural($modelName)),
            '{{fillable}}' => $this->generatorService->generateFillable($fields),
            '{{schema}}' => $this->generatorService->generateMigrationSchema($fields),
            '{{softDeletesImport}}' => $softDeletesImport,
            '{{softDeletesTrait}}' => $softDeletesTrait,
            '{{softDeletes}}' => $softDeletes,
            '{{relationships}}' => '', 
            
            // Controller
            '{{controllerNamespace}}' => $controllerNamespace,
            '{{modelNamespace}}' => "{$modelNamespace}\\{$modelName}",
            '{{storeRequestNamespace}}' => "{$requestNamespace}\\Store{$modelName}Request",
            '{{updateRequestNamespace}}' => "{$requestNamespace}\\Update{$modelName}Request",
            '{{storeRequest}}' => "Store{$modelName}Request",
            '{{updateRequest}}' => "Update{$modelName}Request",
            '{{model}}' => $modelName,
            '{{modelVariable}}' => Str::camel($modelName),
            '{{modelVariablePlural}}' => Str::camel(Str::plural($modelName)),
            '{{viewPath}}' => $viewPath,
            '{{routeBase}}' => $routeBase,
            '{{searchLogic}}' => $searchLogic,

            // Request
            '{{rules}}' => $this->generatorService->generateValidationRules($fields),

            // Views
            '{{formFields}}' => $this->generatorService->generateFormFields($fields, Str::camel($modelName)),
            '{{tableHeaders}}' => $this->generatorService->generateTableHeaders($fields),
            '{{tableBody}}' => $this->generatorService->generateTableBody($fields, Str::camel($modelName)),
            '{{showFields}}' => $this->generatorService->generateShowFields($fields, Str::camel($modelName)),
            '{{searchInput}}' => $searchInput,
        ];

        // 1. Model
        $this->generateFile('model.stub', app_path("Models/{$modelName}.php"), $replacements);

        // 2. Migration
        $migrationName = date('Y_m_d_His') . '_create_' . Str::snake(Str::plural($modelName)) . '_table.php';
        $this->generateFile('migration.stub', database_path("migrations/{$migrationName}"), $replacements);

        // 3. Requests
        $requestDir = app_path('Http/Requests' . ($prefixSlash ? "/{$prefixSlash}" : ''));
        if (!File::exists($requestDir)) File::makeDirectory($requestDir, 0755, true);
        
        $replacements['{{namespace}}'] = $requestNamespace;
        $replacements['{{class}}'] = "Store{$modelName}Request";
        $this->generateFile('request.store.stub', "{$requestDir}/Store{$modelName}Request.php", $replacements);

        $replacements['{{class}}'] = "Update{$modelName}Request";
        $this->generateFile('request.update.stub', "{$requestDir}/Update{$modelName}Request.php", $replacements);

        // 4. Controller
        $controllerDir = app_path('Http/Controllers' . ($prefixSlash ? "/{$prefixSlash}" : ''));
        if (!File::exists($controllerDir)) File::makeDirectory($controllerDir, 0755, true);
        
        $replacements['{{namespace}}'] = $controllerNamespace;
        $replacements['{{class}}'] = "{$modelName}Controller";
        
        if ($this->option('api')) {
             $this->generateFile('api_controller.stub', "{$controllerDir}/{$modelName}Controller.php", $replacements);
        } else {
             $this->generateFile('controller.stub', "{$controllerDir}/{$modelName}Controller.php", $replacements);
        }

        // 5. Views (Only if not API)
        if (!$this->option('api')) {
            $viewDir = resource_path("views/" . str_replace('.', '/', $viewPath));
            if (!File::exists($viewDir)) File::makeDirectory($viewDir, 0755, true);

            $this->generateFile('views/index.blade.stub', "{$viewDir}/index.blade.php", $replacements);
            $this->generateFile('views/create.blade.stub', "{$viewDir}/create.blade.php", $replacements);
            $this->generateFile('views/edit.blade.stub', "{$viewDir}/edit.blade.php", $replacements);
            $this->generateFile('views/show.blade.stub', "{$viewDir}/show.blade.php", $replacements);
            $this->generateFile('views/_form.blade.stub', "{$viewDir}/_form.blade.php", $replacements);
        }

        // 6. Routes
        if ($this->option('api')) {
             $this->appendApiRoute($routeBase, $controllerNamespace . "\\{$modelName}Controller");
        } else {
             $this->appendWebRoute($routeBase, $controllerNamespace . "\\{$modelName}Controller");
        }

        $this->info("CRUD generated successfully!");
    }

    protected function generateFile($stubName, $destination, $replacements)
    {
        if (File::exists($destination) && !$this->option('force')) {
            $this->warn("File already exists: {$destination} (Skipped)");
            return;
        }

        $stubContent = File::get(__DIR__ . '/../stubs/' . $stubName);
        $content = str_replace(array_keys($replacements), array_values($replacements), $stubContent);

        File::put($destination, $content);
        $this->info("Generated: {$destination}");
    }

    protected function appendWebRoute($routeBase, $controllerClass)
    {
        $webPath = base_path('routes/web.php');
        $routeLine = "Route::resource('{$routeBase}', \\{$controllerClass}::class);";

        if (File::exists($webPath)) {
            $content = File::get($webPath);
            if (!str_contains($content, $routeLine)) {
                File::append($webPath, "\n" . $routeLine);
                $this->info("Route added to web.php");
            } else {
                $this->warn("Route already exists in web.php");
            }
        }
    }

    protected function appendApiRoute($routeBase, $controllerClass)
    {
        $apiPath = base_path('routes/api.php');
        $routeLine = "Route::apiResource('{$routeBase}', \\{$controllerClass}::class);";

        if (!File::exists($apiPath)) {
            $this->warn("routes/api.php does not exist. Creating it...");
            File::put($apiPath, "<?php\n\nuse Illuminate\Support\Facades\Route;\n\n");
            // Note: Laravel 11 requires more setup for API routes, but creating the file is a start.
            // The user might need to run `install:api` or register the route file in `bootstrap/app.php`.
        }

        $content = File::get($apiPath);
        if (!str_contains($content, $routeLine)) {
            File::append($apiPath, "\n" . $routeLine);
            $this->info("Route added to api.php");
        } else {
            $this->warn("Route already exists in api.php");
        }
    }
}
