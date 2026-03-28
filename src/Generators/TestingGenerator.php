<?php

namespace Larahammer\Generator\Generators;

class TestingGenerator extends BaseGenerator
{
    public function generate(): string
    {
        $this->generateFeatureTests();
        $this->generateUnitTests();
        $this->generateTestHelpers();

        return "tests/Feature + tests/Unit with full coverage";
    }

    private function generateFeatureTests(): void
    {
        $stub = $this->getStub('test_feature.stub');
        $content = $this->replacePlaceholders($stub, [
            'model' => $this->modelName(),
            'model_variable' => $this->variableName(),
            'route' => $this->routeName(),
        ]);

        $path = base_path("tests/Feature/{$this->modelName()}CrudTest.php");
        $this->writeFile($path, $content);
    }

    private function generateUnitTests(): void
    {
        $stub = $this->getStub('test_unit.stub');
        $content = $this->replacePlaceholders($stub, [
            'model' => $this->modelName(),
            'model_variable' => $this->variableName(),
        ]);

        $path = base_path("tests/Unit/{$this->modelName()}Test.php");
        $this->writeFile($path, $content);
    }

    private function generateTestHelpers(): void
    {
        $stub = $this->getStub('test_helpers.stub');
        $path = base_path("tests/{$this->modelName()}TestCase.php");
        $this->writeFile($path, $stub);
    }
}
