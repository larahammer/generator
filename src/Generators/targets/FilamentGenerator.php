<?php

namespace Larahammer\Generator\Generators\targets;

use Larahammer\Generator\Generators\BaseGenerator;
use Larahammer\Generator\Support\FieldParser;

class FilamentGenerator extends BaseGenerator
{
    public function generate(): string
    {
        $stub    = $this->getStub('filament/resource.stub');
        $content = $this->replacePlaceholders($stub, [
            'model'          => $this->modelName(),
            'variable'       => $this->variableName(),
            'route'          => $this->routeName(),
            'form_fields'    => $this->buildFormFields(),
            'table_columns'  => $this->buildTableColumns(),
            'table_filters'  => $this->buildTableFilters(),
        ]);

        $path = app_path("Filament/Resources/{$this->modelName()}Resource.php");
        $this->writeFile($path, $content);

        // Generate Pages
        $this->generatePages();

        return "app/Filament/Resources/{$this->modelName()}Resource.php";
    }

    private function buildFormFields(): string
    {
        $lines = [];

        foreach ($this->fields as $field) {
            $name  = $field['name'];
            $label = ucwords(str_replace('_', ' ', $name));

            $line = match($field['type']) {
                'text', 'longtext' => "                Forms\Components\Textarea::make('{$name}')\n                    ->label('{$label}')\n                    ->columnSpanFull()",
                'boolean', 'bool'  => "                Forms\Components\Toggle::make('{$name}')\n                    ->label('{$label}')",
                'integer', 'int', 'bigint' => "                Forms\Components\TextInput::make('{$name}')\n                    ->label('{$label}')\n                    ->numeric()",
                'decimal', 'float' => "                Forms\Components\TextInput::make('{$name}')\n                    ->label('{$label}')\n                    ->numeric()\n                    ->step(0.01)",
                'date'             => "                Forms\Components\DatePicker::make('{$name}')\n                    ->label('{$label}')",
                'datetime', 'timestamp' => "                Forms\Components\DateTimePicker::make('{$name}')\n                    ->label('{$label}')",
                'enum'             => $this->buildSelectField($field),
                default            => "                Forms\Components\TextInput::make('{$name}')\n                    ->label('{$label}')\n                    ->maxLength(255)",
            };

            if ($field['nullable']) {
                $line .= "\n                    ->nullable()";
            }

            $lines[] = $line;
        }

        return implode(",\n\n", $lines);
    }

    private function buildSelectField(array $field): string
    {
        $name    = $field['name'];
        $label   = ucwords(str_replace('_', ' ', $name));
        $options = array_combine($field['enum_values'], array_map('ucfirst', $field['enum_values']));
        $optStr  = json_encode($options, JSON_PRETTY_PRINT);

        return "                Forms\Components\Select::make('{$name}')\n                    ->label('{$label}')\n                    ->options({$optStr})";
    }

    private function buildTableColumns(): string
    {
        $lines = [];

        foreach ($this->fields as $field) {
            $name  = $field['name'];
            $label = ucwords(str_replace('_', ' ', $name));

            $line = match($field['type']) {
                'boolean', 'bool' => "                Tables\Columns\IconColumn::make('{$name}')\n                    ->label('{$label}')\n                    ->boolean()",
                'date'            => "                Tables\Columns\TextColumn::make('{$name}')\n                    ->label('{$label}')\n                    ->date()",
                'datetime', 'timestamp' => "                Tables\Columns\TextColumn::make('{$name}')\n                    ->label('{$label}')\n                    ->dateTime()",
                default           => "                Tables\Columns\TextColumn::make('{$name}')\n                    ->label('{$label}')\n                    ->searchable()\n                    ->sortable()",
            };

            $lines[] = $line;
        }

        return implode(",\n\n", $lines);
    }

    private function buildTableFilters(): string
    {
        $enumFields = array_filter($this->fields, fn($f) => $f['type'] === 'enum');

        if (empty($enumFields)) {
            return '//';
        }

        $lines = [];
        foreach ($enumFields as $field) {
            $name    = $field['name'];
            $label   = ucwords(str_replace('_', ' ', $name));
            $options = array_combine($field['enum_values'], array_map('ucfirst', $field['enum_values']));
            $optStr  = json_encode($options);

            $lines[] = "                Tables\Filters\SelectFilter::make('{$name}')\n                    ->label('{$label}')\n                    ->options({$optStr})";
        }

        return implode(",\n\n", $lines);
    }

    private function generatePages(): void
    {
        $pages = ['List', 'Create', 'Edit'];

        foreach ($pages as $page) {
            $stub    = $this->getStub("filament/page-{$page}.stub");
            $content = $this->replacePlaceholders($stub, [
                'model' => $this->modelName(),
            ]);

            $dir  = app_path("Filament/Resources/{$this->modelName()}Resource/Pages");
            $this->writeFile("{$dir}/{$page}{$this->modelName()}.php", $content);
        }
    }
}
