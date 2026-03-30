<?php

namespace Larahammer\Generator\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class RelationCommand extends Command
{
    protected $signature = 'larahammer:relation
                            {model : The model that owns the relation (e.g. Post)}
                            {--belongs-to=* : BelongsTo relation (e.g. --belongs-to=User)}
                            {--has-many=* : HasMany relation (e.g. --has-many=Comment)}
                            {--has-one=* : HasOne relation (e.g. --has-one=Profile)}
                            {--belongs-to-many=* : BelongsToMany (e.g. --belongs-to-many=Tag)}
                            {--with-migration : Generate foreign key migration}
                            {--force : Overwrite existing files}';

    protected $description = 'Add relationships to an existing model — belongsTo, hasMany, hasOne, belongsToMany.';

    protected Filesystem $files;

    public function __construct()
    {
        parent::__construct();
        $this->files = new Filesystem();
    }

    public function handle(): int
    {
        $model = Str::studly($this->argument('model'));

        $belongsTo       = $this->option('belongs-to');
        $hasMany         = $this->option('has-many');
        $hasOne          = $this->option('has-one');
        $belongsToMany   = $this->option('belongs-to-many');
        $withMigration   = $this->option('with-migration');

        if (empty($belongsTo) && empty($hasMany) && empty($hasOne) && empty($belongsToMany)) {
            $this->error('No relations specified. Use --belongs-to, --has-many, --has-one, or --belongs-to-many.');
            return self::FAILURE;
        }

        $this->info("🔗 Larahammer Relation — adding relations to <fg=cyan>{$model}</>");
        $this->newLine();

        $modelPath = app_path("Models/{$model}.php");

        if (!$this->files->exists($modelPath)) {
            $this->error("Model {$model}.php not found at {$modelPath}");
            return self::FAILURE;
        }

        $content = $this->files->get($modelPath);

        // --- belongsTo ---
        foreach ($belongsTo as $related) {
            $related = Str::studly($related);
            $this->runStep("Add belongsTo {$related}", function() use (&$content, $model, $related, $withMigration) {
                $content = $this->injectRelation($content, $this->buildBelongsTo($related));
                $content = $this->addImport($content, $related);

                if ($withMigration) {
                    $this->generateForeignKeyMigration($model, $related, 'belongsTo');
                }
                return "belongsTo {$related}";
            });
        }

        // --- hasMany ---
        foreach ($hasMany as $related) {
            $related = Str::studly($related);
            $this->runStep("Add hasMany {$related}", function() use (&$content, $model, $related, $withMigration) {
                $content = $this->injectRelation($content, $this->buildHasMany($related));
                $content = $this->addImport($content, $related);

                if ($withMigration) {
                    $this->generateForeignKeyMigration($related, $model, 'hasMany');
                }
                return "hasMany {$related}";
            });
        }

        // --- hasOne ---
        foreach ($hasOne as $related) {
            $related = Str::studly($related);
            $this->runStep("Add hasOne {$related}", function() use (&$content, $model, $related, $withMigration) {
                $content = $this->injectRelation($content, $this->buildHasOne($related));
                $content = $this->addImport($content, $related);

                if ($withMigration) {
                    $this->generateForeignKeyMigration($related, $model, 'hasOne');
                }
                return "hasOne {$related}";
            });
        }

        // --- belongsToMany ---
        foreach ($belongsToMany as $related) {
            $related = Str::studly($related);
            $this->runStep("Add belongsToMany {$related}", function() use (&$content, $model, $related, $withMigration) {
                $content = $this->injectRelation($content, $this->buildBelongsToMany($related));
                $content = $this->addImport($content, $related);

                if ($withMigration) {
                    $this->generatePivotMigration($model, $related);
                }
                return "belongsToMany {$related}";
            });
        }

        // Save updated model
        $this->files->put($modelPath, $content);

        $this->newLine();
        $this->info("✅  Done! Relations added to <fg=cyan>{$model}</>");

        if ($withMigration) {
            $this->newLine();
            $this->line('<fg=yellow>Next steps:</>');
            $this->line('  Run <fg=cyan>php artisan migrate</> to apply foreign key migrations.');
        }

        $this->newLine();

        return self::SUCCESS;
    }

    private function buildBelongsTo(string $related): string
    {
        $method    = Str::camel($related);
        $fk        = Str::snake($related) . '_id';

        return <<<PHP

    /**
     * Get the {$method} that owns this model.
     */
    public function {$method}()
    {
        return \$this->belongsTo({$related}::class, '{$fk}');
    }
PHP;
    }

    private function buildHasMany(string $related): string
    {
        $method = Str::camel(Str::plural($related));

        return <<<PHP

    /**
     * Get all {$method} for this model.
     */
    public function {$method}()
    {
        return \$this->hasMany({$related}::class);
    }
PHP;
    }

    private function buildHasOne(string $related): string
    {
        $method = Str::camel($related);

        return <<<PHP

    /**
     * Get the {$method} associated with this model.
     */
    public function {$method}()
    {
        return \$this->hasOne({$related}::class);
    }
PHP;
    }

    private function buildBelongsToMany(string $related): string
    {
        $method    = Str::camel(Str::plural($related));
        $pivot     = $this->getPivotTable($related);

        return <<<PHP

    /**
     * Get all {$method} for this model.
     */
    public function {$method}()
    {
        return \$this->belongsToMany({$related}::class, '{$pivot}');
    }
PHP;
    }

    private function injectRelation(string $content, string $relation): string
    {
        // Inject before closing brace of class
        $lastBrace = strrpos($content, '}');
        return substr($content, 0, $lastBrace) . $relation . "\n}" ;
    }

    private function addImport(string $content, string $related): string
    {
        $import = "use App\\Models\\{$related};";

        if (str_contains($content, $import)) {
            return $content;
        }

        return str_replace(
            'use Illuminate\Database\Eloquent\Model;',
            "use Illuminate\\Database\\Eloquent\\Model;\n{$import}",
            $content
        );
    }

    private function generateForeignKeyMigration(string $model, string $parent, string $type): void
    {
        $table     = Str::snake(Str::plural($model));
        $fk        = Str::snake($parent) . '_id';
        $timestamp = now()->format('Y_m_d_His');

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
            \$table->foreignId('{$fk}')->nullable()->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('{$table}', function (Blueprint \$table) {
            \$table->dropForeign(['{$fk}']);
            \$table->dropColumn('{$fk}');
        });
    }
};
PHP;

        $filename = "{$timestamp}_add_{$fk}_to_{$table}_table.php";
        $this->files->put(database_path("migrations/{$filename}"), $content);
    }

    private function generatePivotMigration(string $model, string $related): void
    {
        $pivotTable = $this->getPivotTable($related, $model);
        $fk1        = Str::snake($model) . '_id';
        $fk2        = Str::snake($related) . '_id';
        $table1     = Str::snake(Str::plural($model));
        $table2     = Str::snake(Str::plural($related));
        $timestamp  = now()->format('Y_m_d_His');

        $content = <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$pivotTable}', function (Blueprint \$table) {
            \$table->id();
            \$table->foreignId('{$fk1}')->constrained()->cascadeOnDelete();
            \$table->foreignId('{$fk2}')->constrained()->cascadeOnDelete();
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$pivotTable}');
    }
};
PHP;

        $filename = "{$timestamp}_create_{$pivotTable}_table.php";
        $this->files->put(database_path("migrations/{$filename}"), $content);
    }

    private function getPivotTable(string $model, string $related = ''): string
    {
        $models = [Str::snake($model), Str::snake($related ?: $model)];
        sort($models);
        return implode('_', $models);
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