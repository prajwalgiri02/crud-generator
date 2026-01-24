<?php

namespace Prajwal\CrudGenerator\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Prajwal\CrudGenerator\CrudGeneratorServiceProvider;
use Illuminate\Support\Facades\File;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            CrudGeneratorServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        // Clean up generated files before each test
        $this->cleanUpGeneratedFiles();
    }

    protected function tearDown(): void
    {
        // Clean up generated files after each test
        $this->cleanUpGeneratedFiles();
        parent::tearDown();
    }

    protected function cleanUpGeneratedFiles()
    {
        $files = [
            app_path('Models/Post.php'),
            app_path('Http/Controllers/PostController.php'),
            app_path('Http/Requests/StorePostRequest.php'),
            app_path('Http/Requests/UpdatePostRequest.php'),
            app_path('Http/Resources/PostResource.php'),
            resource_path('views/posts'),
        ];

        foreach ($files as $file) {
            if (File::isDirectory($file)) {
                File::deleteDirectory($file);
            } elseif (File::exists($file)) {
                File::delete($file);
            }
        }

        // Clean up migrations
        $migrationPath = database_path('migrations');
        if (File::exists($migrationPath)) {
            foreach (File::files($migrationPath) as $file) {
                if (str_contains($file->getFilename(), 'create_posts_table')) {
                    File::delete($file->getPathname());
                }
            }
        }
    }
}
