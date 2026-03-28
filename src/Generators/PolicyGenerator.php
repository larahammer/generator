<?php

namespace Larahammer\Generator\Generators;

class PolicyGenerator extends BaseGenerator
{
    public function generate(): string
    {
        $this->generatePolicy();
        $this->generatePolicyRegistration();

        return "app/Policies/{$this->modelName()}Policy.php + Gate registrations";
    }

    private function generatePolicy(): void
    {
        $stub = $this->getStub('policy.stub');
        $content = $this->replacePlaceholders($stub, [
            'model' => $this->modelName(),
            'model_variable' => $this->variableName(),
        ]);

        $path = app_path("Policies/{$this->modelName()}Policy.php");
        $this->writeFile($path, $content);
    }

    private function generatePolicyRegistration(): void
    {
        $stub = $this->getStub('policy_registration.stub');
        $content = $this->replacePlaceholders($stub, [
            'model' => $this->modelName(),
            'namespace' => "App\Models\\{$this->modelName()}",
            'policy_namespace' => "App\Policies\\{$this->modelName()}Policy",
        ]);

        // Write to storage so user can copy-paste
        $path = storage_path("larahammer/{$this->modelName()}Policy-registration.php");
        $this->files->makeDirectory(dirname($path), 0755, true, true);
        $this->files->put($path, $content);
    }
}
