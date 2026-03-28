<?php

namespace Larahammer\Generator\Generators;

class ApiAuthenticationGenerator extends BaseGenerator
{
    public function generate(): string
    {
        $this->generateApiMiddleware();
        $this->generateApiRateLimitingSetup();

        return "API Authentication setup + Rate limiting middleware";
    }

    private function generateApiMiddleware(): void
    {
        $stub = $this->getStub('api_auth_middleware.stub');
        $path = app_path('Http/Middleware/ApiAuthentication.php');
        $this->writeFile($path, $stub);
    }

    private function generateApiRateLimitingSetup(): void
    {
        $stub = $this->getStub('api_rate_limiting.stub');
        $path = storage_path("larahammer/api-rate-limiting-setup.php");
        $this->files->makeDirectory(dirname($path), 0755, true, true);
        $this->files->put($path, $stub);
    }
}
