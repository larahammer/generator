<?php

namespace Larahammer\Generator\Generators;

use Illuminate\Support\Str;

class RouteGenerator extends BaseGenerator
{
    private string $target;

    public function __construct(string $name, string $target, bool $force = false)
    {
        parent::__construct($name, [], $force);
        $this->target = $target;
    }

    public function generate(): string
    {
        if (in_array($this->target, ['api', 'all'])) {
            $this->injectApiRoute();
        }

        if (in_array($this->target, ['blade', 'all'])) {
            $this->injectWebRoute();
        }

        if ($this->target === 'filament') {
            return 'Filament handles its own routing via resource registration.';
        }

        return 'Routes injected successfully.';
    }

    private function injectWebRoute(): void
    {
        $routeFile = base_path('routes/web.php');
        $routeLine = "\nRoute::resource('" . $this->routeName() . "', \\App\\Http\\Controllers\\" . $this->controllerName() . "::class);";

        $this->appendIfNotExists($routeFile, $routeLine);
    }

    private function injectApiRoute(): void
    {
        $routeFile = base_path('routes/api.php');

        if (! file_exists($routeFile)) {
            file_put_contents($routeFile, "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n");
        }

        $routeLine = "\nRoute::apiResource('" . $this->routeName() . "', \\App\\Http\\Controllers\\Api\\" . $this->controllerName() . "::class);";

        $this->appendIfNotExists($routeFile, $routeLine);
    }

    private function appendIfNotExists(string $file, string $line): void
    {
        $content = file_get_contents($file);

        if (! str_contains($content, trim($line))) {
            file_put_contents($file, $content . $line . "\n");
        }
    }
}
