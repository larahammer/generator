<?php

namespace Larahammer\Generator\Generators;

class LandingPageGenerator extends BaseGenerator
{
    public function generate(): string
    {
        $this->generateLandingView();
        $this->generateLandingController();

        return "resources/views/landing.blade.php + app/Http/Controllers/LandingController.php";
    }

    private function generateLandingView(): void
    {
        $stub    = $this->getStub('landing.blade.stub');
        $path    = resource_path('views/landing.blade.php');
        $this->writeFile($path, $stub);
    }

    private function generateLandingController(): void
    {
        $stub    = $this->getStub('landing_controller.stub');
        $path    = app_path('Http/Controllers/LandingController.php');
        $this->writeFile($path, $stub);
    }
}
