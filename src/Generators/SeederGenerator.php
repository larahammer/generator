<?php

namespace Larahammer\Generator\Generators;

class SeederGenerator extends BaseGenerator
{
    public function generate(): string
    {
        $stub    = $this->getStub('seeder.stub');
        $content = $this->replacePlaceholders($stub, [
            'model'    => $this->modelName(),
            'variable' => $this->variableName(),
            'factory'  => $this->modelName() . 'Factory',
        ]);

        $path = database_path("seeders/{$this->modelName()}Seeder.php");
        $this->writeFile($path, $content);

        return "database/seeders/{$this->modelName()}Seeder.php";
    }
}
