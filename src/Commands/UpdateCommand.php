<?php

namespace Larahammer\Generator\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Larahammer\Generator\Support\FieldParser;

class UpdateCommand extends Command
{
    protected $signature = 'larahammer:update
                            {name : Model name to update (e.g. Product)}
                            {--add-field=* : New field definitions (e.g. --add-field=discount:decimal --add-field=notes:text:nullable)}
                            {--remove-field=* : Fields to remove (e.g. --remove-field=old_column)}
                            {--force : Overwrite existing files}';

    protected $description = 'Update an existing CRUD — add or remove fields without regenerating everything.';

    protected Filesystem $files;

    public function __construct()
    {
        parent::__construct();
        $this->files = new Filesystem();
    }

    public function handle(): int
    {
        $name          = Str::studly($this->argument('name'));
        $addFields     = FieldParser::parse($this->option('add-field'));
        $removeFields  = $this->option('remove-field');

        if (empty($addFields) && empty($removeFields)) {
            $this->error('No fields specified. Use --add-field or --remove-field.');
            return self::FAILURE;
        }

        $this->info("🔄 Larahammer Update — updating <fg=cyan>{$name}</>");
        $this->newLine();

        if (!empty($addFields)) {
            $this->runStep('Alter Migration', fn() => $this->generateAlterMigration($name, $addFields));
            $this->runStep('Update Model $fillable', fn() => $this->updateModelFillable($name, $addFields));
            $this->runStep('Update Request rules', fn() => $this->updateRequest($name, $addFields));
            $this->runStep('Update Blade views', fn() => $this->updateBladeViews($name, $addFields));
        }

        if (!empty($removeFields)) {
            $this->runStep('Drop columns Migration', fn() => $this->generateDropMigration($name, $removeFields));
            $this->runStep('Remove from Model $fillable', fn() => $this->removeFromFillable($name, $removeFields));
            $this->runStep('Remove from Request rules', fn() => $this->removeFromRequest($name, $removeFields));
        }

        $this->newLine();
        $this->info("✅  Done! <fg=cyan>{$name}</> updated successfully.");
        $this->newLine();
        $this->line('<fg=yellow>Next steps:</>');
        $this->line('  Run <fg=cyan>php artisan migrate</> to apply the new migration.');
        $this->newLine();

        return self::SUCCESS;
    }

    private function generateAlterMigration(string $name, array $fields): string
    {
        $table     = Str::snake(Str::plural($name));
        $timestamp = now()->format('Y_m_d_His');
        $columns   = $this->buildAlterColumns($fields);

        $content = <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('{$table}', function (Blueprint \$table) {
{$columns}
        });
    }

    public function down(): void
    {
        Schema::table('{$table}', function (Blueprint \$table) {
            \$table->dropColumn(['{$this->fieldNames($fields)}']);
        });
    }
};
PHP;

        $filename = "{$timestamp}_add_fields_to_{$table}_table.php";
        $path     = database_path("migrations/{$filename}");
        $this->files->put($path, $content);

        return "database/migrations/{$filename}";
    }

    private function generateDropMigration(string $name, array $removeFields): string
    {
        $table     = Str::snake(Str::plural($name));
        $timestamp = now()->format('Y_m_d_His');
        $columns   = implode("', '", $removeFields);

        $content = <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('{$table}', function (Blueprint \$table) {
            \$table->dropColumn(['{$columns}']);
        });
    }

    public function down(): void
    {
        // Re-add columns manually if needed
    }
};
PHP;

        $filename = "{$timestamp}_remove_fields_from_{$table}_table.php";
        $path     = database_path("migrations/{$filename}");
        $this->files->put($path, $content);

        return "database/migrations/{$filename}";
    }

    private function updateModelFillable(string $name, array $fields): string
    {
        $modelPath = app_path("Models/{$name}.php");

        if (!$this->files->exists($modelPath)) {
            throw new \RuntimeException("Model {$name}.php not found.");
        }

        $content   = $this->files->get($modelPath);
        $newFields = implode(",\n", array_map(fn($f) => "        '{$f['name']}'", $fields));

        // Inject before closing bracket of $fillable
        $content = preg_replace(
            '/protected \$fillable = \[(.*?)\];/s',
            "protected \$fillable = [$1,\n{$newFields}\n    ];",
            $content
        );

        $this->files->put($modelPath, $content);

        return "app/Models/{$name}.php";
    }

    private function removeFromFillable(string $name, array $removeFields): string
    {
        $modelPath = app_path("Models/{$name}.php");

        if (!$this->files->exists($modelPath)) {
            throw new \RuntimeException("Model {$name}.php not found.");
        }

        $content = $this->files->get($modelPath);

        foreach ($removeFields as $field) {
            $content = preg_replace("/\s*'{$field}',?/", '', $content);
        }

        $this->files->put($modelPath, $content);

        return "app/Models/{$name}.php";
    }

    private function updateRequest(string $name, array $fields): string
    {
        $requestPath = app_path("Http/Requests/{$name}Request.php");

        if (!$this->files->exists($requestPath)) {
            throw new \RuntimeException("{$name}Request.php not found.");
        }

        $content  = $this->files->get($requestPath);
        $newRules = implode(",\n", array_map(
            fn($f) => "            '{$f['name']}' => '" . FieldParser::toValidationRule($f) . "'",
            $fields
        ));

        // Inject before closing bracket of rules array
        $content = preg_replace(
            '/return \[(.*?)\];/s',
            "return [$1,\n{$newRules}\n        ];",
            $content
        );

        $this->files->put($requestPath, $content);

        return "app/Http/Requests/{$name}Request.php";
    }

    private function removeFromRequest(string $name, array $removeFields): string
    {
        $requestPath = app_path("Http/Requests/{$name}Request.php");

        if (!$this->files->exists($requestPath)) {
            throw new \RuntimeException("{$name}Request.php not found.");
        }

        $content = $this->files->get($requestPath);

        foreach ($removeFields as $field) {
            $content = preg_replace("/\s*'{$field}' => '[^']*',?/", '', $content);
        }

        $this->files->put($requestPath, $content);

        return "app/Http/Requests/{$name}Request.php";
    }

    private function updateBladeViews(string $name, array $fields): string
    {
        $route   = Str::kebab(Str::plural($name));
        $varName = Str::camel($name);
        $views   = ['index', 'create', 'edit', 'show'];
        $updated = [];

        foreach ($views as $view) {
            $viewPath = resource_path("views/{$route}/{$view}.blade.php");

            if (!$this->files->exists($viewPath)) {
                continue;
            }

            $content = $this->files->get($viewPath);

            foreach ($fields as $field) {
                $label     = ucwords(str_replace('_', ' ', $field['name']));
                $name_attr = $field['name'];
                $inputType = FieldParser::toInputType($field);

                if ($view === 'index') {
                    // Add table header
                    $content = str_replace(
                        '<th class="px-4 py-3 text-right',
                        "<th class=\"px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider\">{$label}</th>\n                    <th class=\"px-4 py-3 text-right",
                        $content
                    );
                    // Add table row
                    $content = str_replace(
                        '<td class="px-4 py-3 text-right',
                        "<td class=\"px-4 py-3 text-sm text-gray-900\">{{ \${$varName}->{$name_attr} }}</td>\n                        <td class=\"px-4 py-3 text-right",
                        $content
                    );
                } elseif (in_array($view, ['create', 'edit', 'show'])) {
                    $readonly = $view === 'show' ? 'disabled' : '';
                    $newField = <<<HTML

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{$label}</label>
                    <input type="{$inputType}" name="{$name_attr}" value="{{ old('{$name_attr}', \${$varName}->{$name_attr} ?? '') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500" {$readonly} />
                    @error('{$name_attr}') <p class="text-red-500 text-xs mt-1">{{ \$message }}</p> @enderror
                </div>
HTML;
                    // Inject before closing </form> or </div> of form
                    $content = str_replace('</form>', $newField . "\n            </form>", $content);
                }
            }

            $this->files->put($viewPath, $content);
            $updated[] = $view;
        }

        return "resources/views/{$route}/[" . implode(', ', $updated) . ']';
    }

    private function buildAlterColumns(array $fields): string
    {
        $lines = [];

        foreach ($fields as $field) {
            $method = FieldParser::toMigrationMethod($field);
            $name   = $field['name'];

            if ($method === 'enum') {
                $values = "'" . implode("', '", $field['enum_values']) . "'";
                $line   = "\$table->enum('{$name}', [{$values}])";
            } elseif ($method === 'decimal') {
                $line = "\$table->decimal('{$name}', 15, 2)";
            } else {
                $line = "\$table->{$method}('{$name}')";
            }

            if ($field['nullable']) {
                $line .= '->nullable()';
            }

            $line .= '->after(\'' . ($fields[array_key_first($fields)]['name'] ?? 'id') . '\')';

            $lines[] = '            ' . $line . ';';
        }

        return implode("\n", $lines);
    }

    private function fieldNames(array $fields): string
    {
        return implode("', '", array_column($fields, 'name'));
    }

    private function runStep(string $label, callable $fn): void
    {
        try {
            $result = $fn();
            $this->line("  <fg=green>✓</> {$label}" . ($result ? " → <fg=gray>{$result}</>" : ''));
        } catch (\Exception $e) {
            $this->line("  <fg=red>✗</> {$label} — <fg=red>{$e->getMessage()}</>");
        }
    }
}