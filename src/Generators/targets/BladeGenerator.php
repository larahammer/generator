<?php

namespace Larahammer\Generator\Generators\targets;

use Larahammer\Generator\Generators\BaseGenerator;
use Larahammer\Generator\Support\FieldParser;
use Illuminate\Support\Str;

class BladeGenerator extends BaseGenerator
{
    public function generate(): string
    {
        $this->generateController();
        $this->generateViews();

        return "app/Http/Controllers/{$this->controllerName()}.php + resources/views/" . $this->routeName();
    }

    private function generateController(): void
    {
        $stub    = $this->getStub('blade/controller.stub');
        $content = $this->replacePlaceholders($stub, [
            'model'      => $this->modelName(),
            'variable'   => $this->variableName(),
            'route'      => $this->routeName(),
            'request'    => $this->modelName() . 'Request',
        ]);

        $path = app_path("Http/Controllers/{$this->controllerName()}.php");
        $this->writeFile($path, $content);
    }

    private function generateViews(): void
    {
        $views = ['index', 'create', 'edit', 'show'];

        foreach ($views as $view) {
            $stub    = $this->getStub("blade/{$view}.blade.stub");
            $content = $this->replacePlaceholders($stub, [
                'model'      => $this->modelName(),
                'variable'   => $this->variableName(),
                'route'      => $this->routeName(),
                'fields'     => $this->buildFieldsHtml($view),
                'table_headers' => $this->buildTableHeaders(),
                'table_rows'    => $this->buildTableRows(),
            ]);

            $viewDir = resource_path("views/" . $this->routeName());
            $this->writeFile("{$viewDir}/{$view}.blade.php", $content);
        }
    }

    private function buildFieldsHtml(string $view): string
    {
        $lines = [];
        $readonly = $view === 'show' ? 'disabled' : '';
        $varName = $this->variableName();

        foreach ($this->fields as $field) {
            $inputType = FieldParser::toInputType($field);
            $label     = ucwords(str_replace('_', ' ', $field['name']));
            $name      = $field['name'];

            if ($inputType === 'textarea') {
                $lines[] = <<<HTML
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{$label}</label>
                    <textarea name="{$name}" rows="4" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500" {$readonly}>{{ old('{$name}', \${$varName}->{$name} ?? '') }}</textarea>
                    @error('{$name}') <p class="text-red-500 text-xs mt-1">{{ \$message }}</p> @enderror
                </div>
                HTML;
            } elseif ($inputType === 'select') {
                $options = '';
                foreach ($field['enum_values'] as $val) {
                    $label2   = ucfirst($val);
                    $options .= "\n                        <option value=\"{$val}\" {{ old('{$name}', \${$varName}->{$name} ?? '') == '{$val}' ? 'selected' : '' }}>{$label2}</option>";
                }
                $lines[] = <<<HTML
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{$label}</label>
                    <select name="{$name}" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500" {$readonly}>{$options}
                    </select>
                    @error('{$name}') <p class="text-red-500 text-xs mt-1">{{ \$message }}</p> @enderror
                </div>
                HTML;
            } else {
                $lines[] = <<<HTML
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{$label}</label>
                    <input type="{$inputType}" name="{$name}" value="{{ old('{$name}', \${$varName}->{$name} ?? '') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500" {$readonly} />
                    @error('{$name}') <p class="text-red-500 text-xs mt-1">{{ \$message }}</p> @enderror
                </div>
                HTML;
            }
        }

        return implode("\n", $lines);
    }

    private function buildTableHeaders(): string
    {
        $headers = array_map(
            fn($f) => '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">' . ucwords(str_replace('_', ' ', $f['name'])) . '</th>',
            $this->fields
        );

        return implode("\n                        ", $headers);
    }

    private function buildTableRows(): string
    {
        $varName = $this->variableName();
        $rows = array_map(
            fn($f) => "<td class=\"px-4 py-3 text-sm text-gray-900\">{{ \${$varName}->{$f['name']} }}</td>",
            $this->fields
        );

        return implode("\n                        ", $rows);
    }
}
