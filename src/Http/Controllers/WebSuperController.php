<?php

namespace Prajwal\CrudGenerator\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

/**
 * Base controller for Web/Blade CRUD operations.
 * All web controllers should extend this class.
 */
abstract class WebSuperController extends Controller
{
    protected string $model;
    protected ?string $request = null;
    protected ?string $updateRequest = null;
    protected int $perPage = 10;

    /**
     * Custom redirect route after store/update/delete.
     * If null, uses auto-generated route based on model name.
     * Example: 'admin.posts.index'
     */
    protected ?string $redirectRoute = null;

    /**
     * Custom success messages
     */
    protected string $createMessage = 'Created successfully!';
    protected string $updateMessage = 'Updated successfully!';
    protected string $deleteMessage = 'Deleted successfully!';

    public function index()
    {
        $records = ($this->model)::latest()->paginate($this->perPage);
        return view($this->view('index'), compact('records'));
    }

    public function create()
    {
        return view($this->view('create'));
    }

    public function show($id)
    {
        $record = ($this->model)::findOrFail($id);
        return view($this->view('show'), compact('record'));
    }

    public function store(Request $defaultRequest)
    {
        $request = $this->resolveRequest($defaultRequest, 'store');
        $data = $request->validated();

        if (method_exists($this, 'beforeCreate')) {
            $data = $this->beforeCreate($data, $request) ?? $data;
        }

        $model = ($this->model)::create($data);

        if (method_exists($this, 'afterCreate')) {
            $this->afterCreate($model, $request);
        }

        return redirect()->route($this->getRedirectRoute())->with('success', $this->createMessage);
    }

    public function edit($id)
    {
        $record = ($this->model)::findOrFail($id);
        return view($this->view('edit'), compact('record'));
    }

    public function update(Request $defaultRequest, $id)
    {
        $record  = ($this->model)::findOrFail($id);
        $request = $this->resolveRequest($defaultRequest, 'update');
        $data    = $request->validated();

        if (method_exists($this, 'beforeUpdate')) {
            $data = $this->beforeUpdate($data, $request, $record) ?? $data;
        }

        $record->update($data);

        if (method_exists($this, 'afterUpdate')) {
            $this->afterUpdate($record, $request);
        }

        return redirect()->route($this->getRedirectRoute())->with('success', $this->updateMessage);
    }

    public function destroy($id)
    {
        $record = ($this->model)::findOrFail($id);
        $record->delete();

        return redirect()->route($this->getRedirectRoute())->with('success', $this->deleteMessage);
    }

    /* -------------------- helpers -------------------- */

    /**
     * Get the redirect route for after store/update/delete operations.
     */
    protected function getRedirectRoute(string $action = 'index'): string
    {
        if ($this->redirectRoute) {
            return $this->redirectRoute;
        }
        return $this->routeName($action);
    }

    /**
     * Blade view key, e.g.: blogs.index or admin.blogs.index
     */
    protected function view(string $view): string
    {
        $folder = Str::plural(Str::snake(class_basename($this->model)));
        $prefix = $this->controllerPrefixDot();
        return ltrim($prefix . ($prefix ? '.' : '') . $folder . '.' . $view, '.');
    }

    /**
     * Route name, e.g.: blogs.index or admin.blogs.index
     */
    protected function routeName(string $action): string
    {
        $name   = Str::plural(Str::snake(class_basename($this->model)));
        $prefix = $this->controllerPrefixDot();
        return trim(($prefix ? $prefix . '.' : '') . $name . '.' . $action, '.');
    }

    /**
     * Resolve a FormRequest if provided, else use the default Request.
     */
    protected function resolveRequest(Request $fallback, string $operation = 'store')
    {
        $requestClass = match($operation) {
            'update' => $this->updateRequest ?? $this->request,
            default  => $this->request,
        };

        return $requestClass ? app($requestClass) : $fallback;
    }

    /**
     * Compute controller namespace prefix after "Http\Controllers\", converted to dot.case
     */
    protected function controllerPrefixDot(): string
    {
        $ns = static::class;
        $needle = 'Http\\Controllers\\';
        $pos = strpos($ns, $needle);
        if ($pos === false) {
            return '';
        }
        $after = substr($ns, $pos + strlen($needle));
        $segments = explode('\\', $after);
        array_pop($segments);

        if (empty($segments)) {
            return '';
        }

        $segments = array_map(fn ($s) => Str::snake($s), $segments);
        return implode('.', $segments);
    }

    /**
     * Handle file uploads for fields present in the request.
     */
    protected function handleFileUploads(Request $request, array $data): array
    {
        foreach ($request->allFiles() as $key => $file) {
            if (array_key_exists($key, $data)) {
                $folder = Str::plural(Str::snake(class_basename($this->model)));
                $path = $file->store("uploads/{$folder}", 'public');
                $data[$key] = $path;
            }
        }
        return $data;
    }
}
