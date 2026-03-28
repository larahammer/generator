<?php

namespace Larahammer\Generator\Support;

class FieldParser
{
    /**
     * Parse CLI field definitions into structured array.
     *
     * Supported formats:
     *   name:string
     *   price:decimal
     *   status:enum(active,inactive,pending)
     *   body:text:nullable
     *   user_id:foreignId
     *
     * @param  array<string>  $rawFields
     * @return array<array{name: string, type: string, options: array, nullable: bool, enum_values: array}>
     */
    public static function parse(array $rawFields): array
    {
        return array_map(fn($raw) => self::parseField($raw), $rawFields);
    }

    private static function parseField(string $raw): array
    {
        $parts    = explode(':', $raw);
        $name     = $parts[0];
        $type     = $parts[1] ?? 'string';
        $modifiers = array_slice($parts, 2);

        $enumValues = [];
        if (str_starts_with($type, 'enum(')) {
            preg_match('/enum\(([^)]+)\)/', $type, $matches);
            $enumValues = isset($matches[1]) ? explode(',', $matches[1]) : [];
            $type       = 'enum';
        }

        return [
            'name'        => $name,
            'type'        => $type,
            'nullable'    => in_array('nullable', $modifiers),
            'unique'      => in_array('unique', $modifiers),
            'enum_values' => $enumValues,
            'raw'         => $raw,
        ];
    }

    /**
     * Map field type to Laravel migration column method.
     */
    public static function toMigrationMethod(array $field): string
    {
        $map = [
            'string'    => 'string',
            'text'      => 'text',
            'longtext'  => 'longText',
            'integer'   => 'integer',
            'int'       => 'integer',
            'bigint'    => 'bigInteger',
            'decimal'   => 'decimal',
            'float'     => 'float',
            'boolean'   => 'boolean',
            'bool'      => 'boolean',
            'date'      => 'date',
            'datetime'  => 'dateTime',
            'timestamp' => 'timestamp',
            'json'      => 'json',
            'uuid'      => 'uuid',
            'foreignId' => 'foreignId',
            'enum'      => 'enum',
        ];

        return $map[$field['type']] ?? 'string';
    }

    /**
     * Map field type to HTML input type for Blade forms.
     */
    public static function toInputType(array $field): string
    {
        return match($field['type']) {
            'text', 'longtext' => 'textarea',
            'integer', 'int', 'bigint', 'decimal', 'float' => 'number',
            'boolean', 'bool' => 'checkbox',
            'date'            => 'date',
            'datetime', 'timestamp' => 'datetime-local',
            'enum'            => 'select',
            default           => 'text',
        };
    }

    /**
     * Map field type to Laravel validation rule.
     */
    public static function toValidationRule(array $field): string
    {
        $nullable = $field['nullable'] ? 'nullable|' : 'required|';

        return match($field['type']) {
            'integer', 'int', 'bigint' => $nullable . 'integer',
            'decimal', 'float'         => $nullable . 'numeric',
            'boolean', 'bool'          => $nullable . 'boolean',
            'date'                     => $nullable . 'date',
            'datetime', 'timestamp'    => $nullable . 'date',
            'enum'                     => $nullable . 'in:' . implode(',', $field['enum_values']),
            'text', 'longtext'         => $nullable . 'string',
            default                    => $nullable . 'string|max:255',
        };
    }
}
