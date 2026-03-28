<?php

namespace Larahammer\Generator\Generators;

use Illuminate\Support\Str;
use Larahammer\Generator\Support\FieldParser;

class MigrationGenerator extends BaseGenerator
{
    public function generate(): string
    {
        $stub    = $this->getStub('migration.stub');
        $columns = $this->buildColumns();

        $content = $this->replacePlaceholders($stub, [
            'table'   => $this->tableName(),
            'columns' => $columns,
        ]);

        $filename  = date('Y_m_d_His') . '_create_' . $this->tableName() . '_table.php';
        $path      = database_path("migrations/{$filename}");

        $this->writeFile($path, $content);

        return "database/migrations/{$filename}";
    }

    private function buildColumns(): string
    {
        $lines = [];

        foreach ($this->fields as $field) {
            $method  = FieldParser::toMigrationMethod($field);
            $name    = $field['name'];
            $line    = '';

            if ($method === 'enum') {
                $values = "'" . implode("', '", $field['enum_values']) . "'";
                $line   = "\$table->enum('{$name}', [{$values}])";
            } elseif ($method === 'foreignId') {
                $line = "\$table->foreignId('{$name}')->constrained()->cascadeOnDelete()";
            } elseif ($method === 'decimal') {
                $line = "\$table->decimal('{$name}', 15, 2)";
            } else {
                $line = "\$table->{$method}('{$name}')";
            }

            if ($field['nullable']) {
                $line .= '->nullable()';
            }

            if ($field['unique']) {
                $line .= '->unique()';
            }

            $lines[] = '            ' . $line . ';';
        }

        return implode("\n", $lines);
    }
}
