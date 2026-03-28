<?php

namespace Larahammer\Generator\Generators\targets;

use Larahammer\Generator\Generators\BaseGenerator;

class ApiGenerator extends BaseGenerator
{
    public function generate(): string
    {
        $this->generateController();
        $this->generateResource();

        return "app/Http/Controllers/Api/{$this->controllerName()}.php";
    }

    private function generateController(): void
    {
        $stub    = $this->getStub('api/controller.stub');
        $content = $this->replacePlaceholders($stub, [
            'model'    => $this->modelName(),
            'variable' => $this->variableName(),
            'request'  => $this->modelName() . 'Request',
            'resource' => $this->modelName() . 'Resource',
        ]);

        $path = app_path("Http/Controllers/Api/{$this->controllerName()}.php");
        $this->writeFile($path, $content);
    }

    private function generateResource(): void
    {
        $stub    = $this->getStub('api/resource.stub');
        $content = $this->replacePlaceholders($stub, [
            'model'  => $this->modelName(),
            'fields' => $this->buildResourceFields(),
        ]);

        $path = app_path("Http/Resources/{$this->modelName()}Resource.php");
        $this->writeFile($path, $content);
    }

    private function buildResourceFields(): string
    {
        $lines = ["            'id' => \$this->id"];

        foreach ($this->fields as $field) {
            $name    = $field['name'];
            $lines[] = "            '{$name}' => \$this->{$name}";
        }

        $lines[] = "            'created_at' => \$this->created_at";
        $lines[] = "            'updated_at' => \$this->updated_at";

        return implode(",\n", $lines);
    }
}
