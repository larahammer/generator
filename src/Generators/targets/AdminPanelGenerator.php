<?php

namespace Larahammer\Generator\Generators\targets;

use Larahammer\Generator\Generators\BaseGenerator;

class AdminPanelGenerator extends BaseGenerator
{
    public function generate(): string
    {
        $this->generateUserResource();
        $this->generateRoleResource();
        $this->generateAdminConfig();

        return "app/Filament/Resources/{User,Role}Resource.php + Admin Panel Config";
    }

    private function generateUserResource(): void
    {
        $stub    = $this->getStub('filament/user_resource.stub');
        $path    = app_path('Filament/Resources/UserResource.php');
        $this->writeFile($path, $stub);

        // Generate pages
        $pages = ['List', 'Create', 'Edit'];
        foreach ($pages as $page) {
            $pageStub = $this->getStub("filament/user-page-{$page}.stub");
            $dir      = app_path('Filament/Resources/UserResource/Pages');
            $this->writeFile("{$dir}/{$page}Users.php", $pageStub);
        }
    }

    private function generateRoleResource(): void
    {
        $stub    = $this->getStub('filament/role_resource.stub');
        $path    = app_path('Filament/Resources/RoleResource.php');
        $this->writeFile($path, $stub);

        // Generate pages
        $pages = ['List', 'Create', 'Edit'];
        foreach ($pages as $page) {
            $pageStub = $this->getStub("filament/role-page-{$page}.stub");
            $dir      = app_path('Filament/Resources/RoleResource/Pages');
            $this->writeFile("{$dir}/{$page}Roles.php", $pageStub);
        }
    }

    private function generateAdminConfig(): void
    {
        $stub    = $this->getStub('filament/admin_config.stub');
        $path    = base_path('config/filament.php');

        // Only write if file doesn't exist
        if (!$this->files->exists($path)) {
            $this->writeFile($path, $stub);
        }
    }
}
