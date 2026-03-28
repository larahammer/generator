<?php

namespace Larahammer\Generator\Generators;

use Illuminate\Support\Str;

class RoleGenerator extends BaseGenerator
{
    public function generate(): string
    {
        $this->generateMigration();
        $this->generateModel();
        $this->generateSeeder();

        return "app/Models/Role.php + database/migrations/create_roles_table";
    }

    private function generateMigration(): void
    {
        $timestamp = now()->format('Y_m_d_His');
        $stub      = $this->getStub('role_migration.stub');
        
        $path = database_path("migrations/{$timestamp}_create_roles_table.php");
        $this->writeFile($path, $stub);
    }

    private function generateModel(): void
    {
        $stub    = $this->getStub('role_model.stub');
        $path    = app_path('Models/Role.php');
        $this->writeFile($path, $stub);
    }

    private function generateSeeder(): void
    {
        $stub    = $this->getStub('role_seeder.stub');
        $path    = database_path('seeders/RoleSeeder.php');
        $this->writeFile($path, $stub);
    }
}
