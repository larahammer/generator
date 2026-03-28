<?php

namespace Larahammer\Generator\Generators;

class SoftDeletesGenerator extends BaseGenerator
{
    public function generate(): string
    {
        $this->updateMigrationForSoftDeletes();
        $this->updateModelForSoftDeletes();

        return "Migration + Model updated with soft deletes";
    }

    private function updateMigrationForSoftDeletes(): void
    {
        // Find the latest migration file for this model
        $pattern = database_path("migrations/*_create_{$this->tableName()}_table.php");
        $files = glob($pattern);

        if (empty($files)) {
            return;
        }

        $migrationFile = end($files);
        $content = $this->files->get($migrationFile);

        // Add soft deletes only if not already present
        if (strpos($content, 'softDeletes') === false) {
            $content = str_replace(
                '$table->timestamps();',
                "\$table->softDeletes();\n            \$table->timestamps();",
                $content
            );

            $this->files->put($migrationFile, $content);
        }
    }

    private function updateModelForSoftDeletes(): void
    {
        $modelPath = app_path("Models/{$this->modelName()}.php");

        if (!$this->files->exists($modelPath)) {
            return;
        }

        $content = $this->files->get($modelPath);

        // Add SoftDeletes trait only if not already present
        if (strpos($content, 'SoftDeletes') === false) {
            // Add use statement
            $content = str_replace(
                'use Illuminate\Database\Eloquent\Model;',
                "use Illuminate\Database\Eloquent\Model;\nuse Illuminate\Database\Eloquent\SoftDeletes;",
                $content
            );

            // Add trait
            $content = str_replace(
                'class ' . $this->modelName() . ' extends Model',
                'class ' . $this->modelName() . " extends Model\n{\n    use SoftDeletes;",
                $content
            );

            $this->files->put($modelPath, $content);
        }
    }
}
