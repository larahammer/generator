<?php

namespace Larahammer\Generator\Generators;

class ModelGenerator extends BaseGenerator
{
    public function generate(): string
    {
        $stub    = $this->getStub('model.stub');
        $content = $this->replacePlaceholders($stub, [
            'model'     => $this->modelName(),
            'table'     => $this->tableName(),
            'fillable'  => $this->buildFillable(),
            'casts'     => $this->buildCasts(),
        ]);

        $path = app_path("Models/{$this->modelName()}.php");
        $this->writeFile($path, $content);

        return "app/Models/{$this->modelName()}.php";
    }

    private function buildFillable(): string
    {
        $fields = array_map(
            fn($f) => "        '{$f['name']}'",
            $this->fields
        );

        return implode(",\n", $fields);
    }

    private function buildCasts(): string
    {
        $casts = [];

        foreach ($this->fields as $field) {
            $cast = match($field['type']) {
                'boolean', 'bool' => "'boolean'",
                'integer', 'int', 'bigint' => "'integer'",
                'decimal', 'float' => "'float'",
                'date'             => "'date'",
                'datetime', 'timestamp' => "'datetime'",
                'json'             => "'array'",
                default            => null,
            };

            if ($cast) {
                $casts[] = "        '{$field['name']}' => {$cast}";
            }
        }

        return empty($casts) ? '' : implode(",\n", $casts);
    }
}
