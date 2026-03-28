<?php

namespace Larahammer\Generator\Generators;

use Illuminate\Support\Str;

class FactoryGenerator extends BaseGenerator
{
    public function generate(): string
    {
        $this->generateFactory();
        $this->generateAdvancedSeeder();

        return "database/factories/{$this->modelName()}Factory.php + Enhanced seeder";
    }

    private function generateFactory(): void
    {
        $stub = $this->getStub('factory.stub');
        $content = $this->replacePlaceholders($stub, [
            'model' => $this->modelName(),
            'model_variable' => $this->variableName(),
            'field_definitions' => $this->buildFieldDefinitions(),
        ]);

        $path = database_path("factories/{$this->modelName()}Factory.php");
        $this->writeFile($path, $content);
    }

    private function generateAdvancedSeeder(): void
    {
        $stub = $this->getStub('seeder_advanced.stub');
        $content = $this->replacePlaceholders($stub, [
            'model' => $this->modelName(),
            'model_variable' => $this->variableName(),
            'table_name' => $this->tableName(),
        ]);

        $path = database_path("seeders/{$this->modelName()}Seeder.php");
        $this->writeFile($path, $content);
    }

    private function buildFieldDefinitions(): string
    {
        $lines = [];

        foreach ($this->fields as $field) {
            $type = $field['type'];
            $name = $field['name'];
            $faker = $this->fakerMethodForType($type, $field);

            $lines[] = "            '{$name}' => {$faker},";
        }

        return implode("\n", $lines);
    }

    private function fakerMethodForType(string $type, array $field): string
    {
        return match ($type) {
            'string' => "\$this->faker->word()",
            'text', 'longtext' => "\$this->faker->paragraph()",
            'integer', 'int' => "\$this->faker->numberBetween(1, 100)",
            'bigint' => "\$this->faker->numberBetween(1000, 9999999)",
            'decimal', 'float' => "\$this->faker->randomFloat(2, 10, 1000)",
            'boolean', 'bool' => "\$this->faker->boolean()",
            'date' => "\$this->faker->date()",
            'datetime', 'timestamp' => "\$this->faker->dateTime()",
            'email' => "\$this->faker->unique()->safeEmail()",
            'uuid' => "\$this->faker->uuid()",
            'json' => "json_encode(\$this->faker->words(3))",
            'enum' => "'{$field['enum_values'][0]}'",
            default => "\$this->faker->word()",
        };
    }
}
