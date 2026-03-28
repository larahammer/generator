<?php

namespace Larahammer\Generator\Generators;

class AuditLogGenerator extends BaseGenerator
{
    public function generate(): string
    {
        $this->generateAuditLogModel();
        $this->generateAuditLogMigration();
        $this->generateActivityObserver();

        return "ActivityLog model + migration + Observer";
    }

    private function generateAuditLogModel(): void
    {
        $stub = $this->getStub('audit_log_model.stub');
        $path = app_path('Models/ActivityLog.php');
        $this->writeFile($path, $stub);
    }

    private function generateAuditLogMigration(): void
    {
        $timestamp = now()->format('Y_m_d_His');
        $stub = $this->getStub('audit_log_migration.stub');

        $path = database_path("migrations/{$timestamp}_create_activity_logs_table.php");
        $this->writeFile($path, $stub);
    }

    private function generateActivityObserver(): void
    {
        $stub = $this->getStub('activity_observer.stub');
        $content = $this->replacePlaceholders($stub, [
            'model' => $this->modelName(),
            'model_variable' => $this->variableName(),
        ]);

        $path = app_path("Observers/{$this->modelName()}Observer.php");
        $this->writeFile($path, $content);
    }
}
