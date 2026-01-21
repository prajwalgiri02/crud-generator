<?php

namespace Prajwal\CrudGenerator;

use Illuminate\Support\ServiceProvider;
use Prajwal\CrudGenerator\Commands\CrudGenerateCommand;
use Prajwal\CrudGenerator\Commands\CrudInstallCommand;

class CrudGeneratorServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            $this->packagePath('config/crud-forms.php') => config_path('crud-forms.php'),
        ], 'crud-config');

        $this->publishes([
            $this->packagePath('stubs') => base_path('stubs/crud-generator'),
        ], 'crud-stubs');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            $this->packagePath('config/crud-forms.php'),
            'crud-forms'
        );

        $this->commands([
            CrudGenerateCommand::class,
            CrudInstallCommand::class,
        ]);
    }

    private function packagePath(string $path): string
    {
        // Package root = one level above /src
        return dirname(__DIR__) . '/' . ltrim($path, '/');
    }
}
