<?php

namespace Prajwal\CrudGenerator\Tests\Feature;

use Prajwal\CrudGenerator\Tests\TestCase;
use Illuminate\Support\Facades\File;

class CrudGenerateCommandTest extends TestCase
{
    /** @test */
    public function it_generates_web_crud_files()
    {
        $this->artisan('crud:generate Post --fields="title:string,body:text"')
            ->assertExitCode(0);

        $this->assertTrue(File::exists(app_path('Models/Post.php')));
        $this->assertTrue(File::exists(app_path('Http/Controllers/PostController.php')));
        $this->assertTrue(File::exists(app_path('Http/Requests/StorePostRequest.php')));
        $this->assertTrue(File::exists(app_path('Http/Requests/UpdatePostRequest.php')));
        $this->assertTrue(File::exists(resource_path('views/posts/index.blade.php')));
        $this->assertTrue(File::exists(resource_path('views/posts/create.blade.php')));
        $this->assertTrue(File::exists(resource_path('views/posts/edit.blade.php')));
        $this->assertTrue(File::exists(resource_path('views/posts/show.blade.php')));
        $this->assertTrue(File::exists(resource_path('views/posts/_form.blade.php')));

        // Check if controller extends WebSuperController
        $controllerContent = File::get(app_path('Http/Controllers/PostController.php'));
        $this->assertStringContainsString('extends WebSuperController', $controllerContent);
        
        // Check for correct namespace in controller
        $this->assertStringContainsString('namespace App\Http\Controllers;', $controllerContent);
    }

    /** @test */
    public function it_generates_api_crud_files()
    {
        $this->artisan('crud:generate Post --fields="title:string,body:text" --api')
            ->assertExitCode(0);

        $this->assertTrue(File::exists(app_path('Models/Post.php')));
        $this->assertTrue(File::exists(app_path('Http/Controllers/PostController.php')));
        $this->assertTrue(File::exists(app_path('Http/Requests/StorePostRequest.php')));
        $this->assertTrue(File::exists(app_path('Http/Requests/UpdatePostRequest.php')));
        $this->assertTrue(File::exists(app_path('Http/Resources/PostResource.php')));
        
        // Views should not be generated for API
        $this->assertFalse(File::exists(resource_path('views/posts')));

        // Check if controller extends ApiSuperController
        $controllerContent = File::get(app_path('Http/Controllers/PostController.php'));
        $this->assertStringContainsString('extends ApiSuperController', $controllerContent);
        
        // Check if it uses PostResource
        $this->assertStringContainsString('protected ?string $resource = PostResource::class;', $controllerContent);
    }

    /** @test */
    public function it_replaces_placeholders_with_and_without_spaces()
    {
        // This test ensures that {{namespace}} and {{ namespace }} are both handled
        $this->artisan('crud:generate Post --fields="title:string"')
             ->assertExitCode(0);

        $controllerContent = File::get(app_path('Http/Controllers/PostController.php'));
        
        // If replacement failed, these {{ }} would still be there
        $this->assertStringNotContainsString('{{', $controllerContent);
        $this->assertStringNotContainsString('}}', $controllerContent);
        
        // Verify specifically some replacements
        $this->assertStringContainsString('namespace App\Http\Controllers;', $controllerContent);
        $this->assertStringContainsString('class PostController extends WebSuperController', $controllerContent);
    }
}
