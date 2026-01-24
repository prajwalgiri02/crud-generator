<?php

namespace Prajwal\CrudGenerator\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

/**
 * Base controller for API CRUD operations.
 * All API controllers should extend this class.
 */
abstract class ApiSuperController extends Controller
{
    protected string $model;
    protected ?string $request = null;
    protected ?string $updateRequest = null;
    protected ?string $resource = null;
    protected int $perPage = 10;

    public function index()
    {
        $records = ($this->model)::latest()->paginate($this->perPage);
        return $this->transform($records);
    }

    public function show($id)
    {
        $record = ($this->model)::findOrFail($id);
        return $this->transform($record);
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

        return $this->transform($model, 201);
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

        return $this->transform($record);
    }

    public function destroy($id)
    {
        $record = ($this->model)::findOrFail($id);
        $record->delete();

        return response()->json(null, 204);
    }

    /* -------------------- helpers -------------------- */

    /**
     * Transform the record(s) using the resource class if provided.
     */
    protected function transform($data, int $status = 200)
    {
        if (!$this->resource) {
            return response()->json($data, $status);
        }

        if ($data instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator) {
            return ($this->resource)::collection($data)->response()->setStatusCode($status);
        }

        return (new $this->resource($data))->response()->setStatusCode($status);
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
