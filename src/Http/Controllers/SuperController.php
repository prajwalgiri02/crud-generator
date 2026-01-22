<?php

namespace prajwal\CrudGenerator\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

abstract class SuperController extends Controller
{
    protected string $model;
    protected ?string $request = null;
    protected bool $isApi = false;

    public function index()
    {
        $records = ($this->model)::latest()->paginate(10);
        
        if ($this->wantsJson()) {
            return response()->json($records);
        }
        
        return view($this->view('index'), compact('records'));
    }

    public function create()
    {
        if ($this->wantsJson()) {
            return response()->json(['message' => 'Not supported in API'], 405);
        }
        return view($this->view('create'));
    }

    public function show($id)
    {
        $record = ($this->model)::findOrFail($id);
        
        if ($this->wantsJson()) {
            return response()->json($record);
        }
        
        return view($this->view('show'), compact('record'));
    }

    public function store(Request $defaultRequest)
    {
        $request = $this->resolveRequest($defaultRequest);
        $data = $request->validated();

        if (method_exists($this, 'beforeCreate')) {
            $data = $this->beforeCreate($data, $request) ?? $data;
        }

        $model = ($this->model)::create($data);

        if (method_exists($this, 'afterCreate')) {
            $this->afterCreate($model, $request);
        }

        if ($this->wantsJson()) {
            return response()->json($model, 201);
        }

        return redirect()->route($this->routeName('index'))->with('success', 'Created!');
    }

    public function edit($id)
    {
        if ($this->wantsJson()) {
            return response()->json(['message' => 'Not supported in API'], 405);
        }
        $record = ($this->model)::findOrFail($id);
        return view($this->view('edit'), compact('record'));
    }

    public function update(Request $defaultRequest, $id)
    {
        $record  = ($this->model)::findOrFail($id);
        $request = $this->resolveRequest($defaultRequest);
        $data    = $request->validated();

        if (method_exists($this, 'beforeUpdate')) {
            $data = $this->beforeUpdate($data, $request, $record) ?? $data;
        }

        $record->update($data);

        if (method_exists($this, 'afterUpdate')) {
            $this->afterUpdate($record, $request);
        }

        if ($this->wantsJson()) {
            return response()->json($record);
        }

        return redirect()->route($this->routeName('index'))->with('success', 'Updated!');
    }

    public function destroy($id)
    {
        $record = ($this->model)::findOrFail($id);
        $record->delete();

        if ($this->wantsJson()) {
            return response()->json(null, 204);
        }

        return redirect()->route($this->routeName('index'))->with('success', 'Deleted!');
    }

    /* -------------------- helpers -------------------- */

    /**
     * Determine if the response should be JSON.
     */
    protected function wantsJson(): bool
    {
        return $this->isApi || request()->expectsJson();
    }

    /**
     * Blade view key, e.g.:
     * - blogs.index
     * - admin.blogs.index (when controller is under App\Http\Controllers\Admin)
     */
    protected function view(string $view): string
    {
        $folder = Str::plural(Str::snake(class_basename($this->model))); // e.g., blogs
        $prefix = $this->controllerPrefixDot();                          // e.g., admin or ''
        return ltrim($prefix . ($prefix ? '.' : '') . $folder . '.' . $view, '.');
    }

    /**
     * Route name, e.g.:
     * - blogs.index
     * - admin.blogs.index (when prefixed)
     */
    protected function routeName(string $action): string
    {
        $name   = Str::plural(Str::snake(class_basename($this->model))); // blogs
        $prefix = $this->controllerPrefixDot();                          // admin or ''
        return trim(($prefix ? $prefix . '.' : '') . $name . '.' . $action, '.');
    }

    /**
     * Resolve a FormRequest if provided, else use the default Request.
     */
    protected function resolveRequest(Request $fallback)
    {
        return $this->request ? app($this->request) : $fallback;
    }

    /**
     * Compute controller namespace prefix after "Http\Controllers\", converted to dot.case
     * App\Http\Controllers\Admin\AnythingController => "admin"
     * App\Http\Controllers\Admin\V1\AnythingController => "admin.v1"
     * App\Http\Controllers\AnythingController => ""
     */
    protected function controllerPrefixDot(): string
    {
        $ns = static::class; // full controller FQN
        $needle = 'Http\\Controllers\\';
        $pos = strpos($ns, $needle);
        if ($pos === false) {
            return '';
        }
        $after = substr($ns, $pos + strlen($needle)); // e.g., "Admin\BlogController"
        $segments = explode('\\', $after);
        array_pop($segments); // remove "BlogController"

        if (empty($segments)) {
            return '';
        }

        $segments = array_map(fn ($s) => Str::snake($s), $segments); // Admin\V1 => ['admin','v1']
        return implode('.', $segments); // "admin.v1"
    }

    /**
     * Handle file uploads for fields present in the request.
     * Stores files in "public/uploads/{modelName}" and returns updated data array with file paths.
     */
    protected function handleFileUploads(Request $request, array $data): array
    {
        foreach ($request->allFiles() as $key => $file) {
            // Only process if this key is part of the validated data
            if (array_key_exists($key, $data)) {
                $folder = Str::plural(Str::snake(class_basename($this->model)));
                $path = $file->store("uploads/{$folder}", 'public');
                $data[$key] = $path;
            }
        }
        return $data;
    }
}
