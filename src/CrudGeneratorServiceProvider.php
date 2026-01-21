<?php

namespace prajwal\CrudGenerator;

use Illuminate\Support\ServiceProvider;
use prajwal\CrudGenerator\Commands\CrudGenerateCommand;
use prajwal\CrudGenerator\Commands\CrudInstallCommand;

class CrudGeneratorServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config/crud-forms.php' => config_path('crud-forms.php'),
        ], 'crud-config');

        $this->publishes([
            __DIR__ . '/Stubs' => base_path('stubs/crud-generator'),
        ], 'crud-stubs');
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/crud-forms.php', 'crud-forms'
        );

        $this->commands([
            CrudGenerateCommand::class,
            CrudInstallCommand::class,
        ]);
    }
}
