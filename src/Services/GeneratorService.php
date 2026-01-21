<?php

namespace prajwal\CrudGenerator\Services;

use Illuminate\Support\Str;

class GeneratorService
{
    public function parseFields(?string $fieldsString): array
    {
        if (empty($fieldsString)) {
            return [];
        }

        $fields = [];
        $parts = explode(',', $fieldsString);

        foreach ($parts as $part) {
            $segments = explode(':', trim($part));
            $name = trim($segments[0] ?? '');
            $type = trim($segments[1] ?? 'string');
            $options = array_slice($segments, 2);

            if ($name) {
                $fields[] = [
                    'name' => $name,
                    'type' => $type,
                    'options' => $options,
                ];
            }
        }

        return $fields;
    }

    public function generateMigrationSchema(array $fields): string
    {
        $schema = [];
        foreach ($fields as $field) {
            $line = "\$table->{$field['type']}('{$field['name']}')";
            
            if (in_array('nullable', $field['options'])) {
                $line .= "->nullable()";
            }
            if (in_array('unique', $field['options'])) {
                $line .= "->unique()";
            }
            if (in_array('default', $field['options'])) {
                 // parsing default is harder, skipping for simplicity or need better syntax
            }

            $line .= ";";
            $schema[] = $line;
        }
        return implode("\n            ", $schema);
    }

    public function generateFillable(array $fields): string
    {
        $names = array_map(fn($f) => "'{$f['name']}'", $fields);
        return implode(",\n        ", $names);
    }

    public function generateValidationRules(array $fields, bool $isUpdate = false): string
    {
        $rules = [];
        foreach ($fields as $field) {
            $fieldRules = ['required'];
            
            if (in_array('nullable', $field['options'])) {
                $fieldRules = ['nullable'];
            }

            if ($field['type'] === 'string') {
                $fieldRules[] = 'string';
                $fieldRules[] = 'max:255';
            } elseif ($field['type'] === 'integer') {
                $fieldRules[] = 'integer';
            } elseif ($field['type'] === 'boolean') {
                $fieldRules[] = 'boolean';
            } elseif ($field['type'] === 'email') {
                $fieldRules[] = 'email';
            } elseif (in_array($field['type'], ['date', 'datetime'])) {
                $fieldRules[] = 'date';
            }

            if (in_array('unique', $field['options'])) {
                // simple unique rule, might need table name context
                // 'unique:table,column'
                // We'll leave it as a placeholder or generic unique
                // For robust unique on update, we need table and id.
                // We will add 'unique' but update request might need exclusion logic handled in the controller or more complex stub.
                // For now, let's keep it simple.
            }

            $ruleString = implode('|', $fieldRules);
            $rules[] = "'{$field['name']}' => '{$ruleString}',";
        }
        return implode("\n            ", $rules);
    }

    public function generateFormFields(array $fields, string $modelVariable): string
    {
        $html = [];
        foreach ($fields as $field) {
            $name = $field['name'];
            $label = Str::title(str_replace('_', ' ', $name));
            $type = $field['type'];
            
            $old = "old('{$name}', \${$modelVariable}->{$name} ?? '')";
            
            $input = "";
            if ($type === 'text' || $type === 'longtext') {
                $input = "<textarea name=\"{$name}\" id=\"{$name}\" class=\"mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50\" rows=\"3\">{{ $old }}</textarea>";
            } elseif ($type === 'boolean') {
                $checked = "old('{$name}', \${$modelVariable}->{$name} ?? false) ? 'checked' : ''";
                $input = "<div class=\"flex items-center\">
                    <input type=\"checkbox\" name=\"{$name}\" id=\"{$name}\" value=\"1\" {$checked} class=\"rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50\">
                    <span class=\"ml-2 text-sm text-gray-600\">Yes</span>
                </div>";
            } elseif (in_array($type, ['date', 'datetime'])) {
                 $input = "<input type=\"date\" name=\"{$name}\" id=\"{$name}\" value=\"{{ $old }}\" class=\"mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50\">";
            } else {
                $inputType = $type === 'email' ? 'email' : ($type === 'password' ? 'password' : 'text');
                $input = "<input type=\"{$inputType}\" name=\"{$name}\" id=\"{$name}\" value=\"{{ $old }}\" class=\"mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50\">";
            }

            $html[] = "<div class=\"mb-4\">
    <label for=\"{$name}\" class=\"block text-sm font-medium text-gray-700\">{$label}</label>
    {$input}
    @error('{$name}')
        <p class=\"text-red-500 text-xs mt-1\">{{ \$message }}</p>
    @enderror
</div>";
        }
        return implode("\n", $html);
    }

    public function generateTableHeaders(array $fields): string
    {
        $headers = [];
        foreach ($fields as $field) {
            $label = Str::title(str_replace('_', ' ', $field['name']));
            $headers[] = "<th class=\"py-3 px-6 text-left\">{$label}</th>";
        }
        return implode("\n                    ", $headers);
    }

    public function generateTableBody(array $fields, string $modelVariable): string
    {
        $cells = [];
        foreach ($fields as $field) {
            $name = $field['name'];
            $cells[] = "<td class=\"py-3 px-6 text-left whitespace-nowrap\">{{ \${$modelVariable}->{$name} }}</td>";
        }
        return implode("\n                    ", $cells);
    }

    public function generateShowFields(array $fields, string $modelVariable): string
    {
        $html = [];
        foreach ($fields as $field) {
             $name = $field['name'];
             $label = Str::title(str_replace('_', ' ', $name));
             $html[] = "<div class=\"col-span-1\">
                    <dt class=\"text-sm font-medium text-gray-500\">{$label}</dt>
                    <dd class=\"mt-1 text-sm text-gray-900\">{{ \${$modelVariable}->{$name} }}</dd>
                </div>";
        }
        return implode("\n                ", $html);
    }

    public function generateSearchLogic(array $fields): string
    {
        $searchableFields = array_filter($fields, fn($f) => in_array($f['type'], ['string', 'text', 'email']));
        if (empty($searchableFields)) {
            return '';
        }

        $code = "if (\$search = \$request->get('search')) {\n            \$query->where(function(\$q) use (\$search) {";
        
        $first = true;
        foreach ($searchableFields as $field) {
            $name = $field['name'];
            if ($first) {
                $code .= "\n                \$q->where('{$name}', 'like', \"%\$search%\")";
                $first = false;
            } else {
                $code .= "\n                  ->orWhere('{$name}', 'like', \"%\$search%\")";
            }
        }
        $code .= ";\n            });\n        }";

        return $code;
    }

    public function generateSearchInput(string $routeBase): string
    {
        return <<<HTML
    <div class="mb-4">
        <form action="{{ route('{$routeBase}.index') }}" method="GET" class="flex gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search..." class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            <button type="submit" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">Search</button>
        </form>
    </div>
HTML;
    }
}
