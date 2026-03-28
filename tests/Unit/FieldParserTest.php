<?php

namespace Larahammer\Generator\Tests\Unit;

use Larahammer\Generator\Support\FieldParser;
use Larahammer\Generator\Tests\TestCase;

class FieldParserTest extends TestCase
{
    /** @test */
    public function it_parses_simple_string_field(): void
    {
        $fields = FieldParser::parse(['name:string']);

        $this->assertEquals('name', $fields[0]['name']);
        $this->assertEquals('string', $fields[0]['type']);
        $this->assertFalse($fields[0]['nullable']);
    }

    /** @test */
    public function it_parses_nullable_modifier(): void
    {
        $fields = FieldParser::parse(['bio:text:nullable']);

        $this->assertTrue($fields[0]['nullable']);
    }

    /** @test */
    public function it_parses_enum_with_values(): void
    {
        $fields = FieldParser::parse(['status:enum(active,inactive,pending)']);

        $this->assertEquals('enum', $fields[0]['type']);
        $this->assertEquals(['active', 'inactive', 'pending'], $fields[0]['enum_values']);
    }

    /** @test */
    public function it_maps_to_correct_migration_method(): void
    {
        $field = FieldParser::parse(['price:decimal'])[0];
        $this->assertEquals('decimal', FieldParser::toMigrationMethod($field));
    }

    /** @test */
    public function it_maps_boolean_to_checkbox_input(): void
    {
        $field = FieldParser::parse(['active:boolean'])[0];
        $this->assertEquals('checkbox', FieldParser::toInputType($field));
    }

    /** @test */
    public function it_generates_correct_validation_rule_for_enum(): void
    {
        $field = FieldParser::parse(['status:enum(active,inactive)'])[0];
        $rule  = FieldParser::toValidationRule($field);

        $this->assertStringContainsString('in:active,inactive', $rule);
    }
}
