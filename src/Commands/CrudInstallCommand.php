<?php

namespace prajwal\CrudGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CrudInstallCommand extends Command
{
    protected $signature = 'crud:install {--force : Overwrite existing files}';

    protected $description = 'Install the CRUD Generator requirements (Layouts, Config)';

    public function handle()
    {
        $this->info('Installing CRUD Generator...');

        // 1. Publish Layout
        $this->publishLayout();

        // 2. Publish Config (Optional, usually done via vendor:publish)
        // We can just remind the user or force publish if we want.
        $this->call('vendor:publish', [
            '--tag' => 'crud-config',
            '--force' => $this->option('force'), 
        ]);

        $this->info('CRUD Generator installed successfully!');
    }

    protected function publishLayout()
    {
        $layoutDir = resource_path('views/layouts');
        $layoutPath = "{$layoutDir}/app.blade.php";

        if (!File::exists($layoutDir)) {
            File::makeDirectory($layoutDir, 0755, true);
        }

        if (File::exists($layoutPath) && !$this->option('force')) {
            if ($this->components->confirm("Layout file already exists at {$layoutPath}. Do you want to overwrite it?")) {
                File::copy(__DIR__ . '/../../stubs/views/layouts/app.blade.stub', $layoutPath);
                $this->info("Layout published: {$layoutPath}");
            } else {
                $this->warn("Skipped publishing layout.");
            }
        } else {
            File::copy(__DIR__ . '/../../stubs/views/layouts/app.blade.stub', $layoutPath);
            $this->info("Layout published: {$layoutPath}");
        }
    }
}
