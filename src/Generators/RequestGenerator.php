<?php

namespace Larahammer\Generator\Generators;

use Larahammer\Generator\Support\FieldParser;

class RequestGenerator extends BaseGenerator
{
    public function generate(): string
    {
        $stub    = $this->getStub('request.stub');
        $content = $this->replacePlaceholders($stub, [
            'model' => $this->modelName(),
            'rules' => $this->buildRules(),
        ]);

        $path = app_path("Http/Requests/{$this->modelName()}Request.php");
        $this->writeFile($path, $content);

        return "app/Http/Requests/{$this->modelName()}Request.php";
    }

    private function buildRules(): string
    {
        $rules = [];

        foreach ($this->fields as $field) {
            $rule    = FieldParser::toValidationRule($field);
            $rules[] = "            '{$field['name']}' => '{$rule}'";
        }

        return implode(",\n", $rules);
    }
}
