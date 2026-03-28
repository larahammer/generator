<?php

namespace Larahammer\Generator\Commands;

use Illuminate\Console\Command;
use Larahammer\Generator\Generators\MigrationGenerator;
use Larahammer\Generator\Generators\ModelGenerator;
use Larahammer\Generator\Generators\RequestGenerator;
use Larahammer\Generator\Generators\SeederGenerator;
use Larahammer\Generator\Generators\RouteGenerator;
use Larahammer\Generator\Generators\RoleGenerator;
use Larahammer\Generator\Generators\LandingPageGenerator;
use Larahammer\Generator\Generators\SecurityMiddlewareGenerator;
use Larahammer\Generator\Generators\FactoryGenerator;
use Larahammer\Generator\Generators\SoftDeletesGenerator;
use Larahammer\Generator\Generators\PolicyGenerator;
use Larahammer\Generator\Generators\ApiAuthenticationGenerator;
use Larahammer\Generator\Generators\TestingGenerator;
use Larahammer\Generator\Generators\AuditLogGenerator;
use Larahammer\Generator\Generators\targets\BladeGenerator;
use Larahammer\Generator\Generators\targets\FilamentGenerator;
use Larahammer\Generator\Generators\targets\ApiGenerator;
use Larahammer\Generator\Generators\targets\AdminPanelGenerator;
use Larahammer\Generator\Support\FieldParser;

class MakeCommand extends Command
{
    protected $signature = 'larahammer:make
                            {name : Model name (e.g. Product, BlogPost)}
                            {fields* : Field definitions (e.g. name:string price:decimal status:enum(active,inactive))}
                            {--target= : Output target: blade, filament, api, all}
                            {--with-roles : Generate role system with migrations and seeders}
                            {--with-landing : Generate landing page}
                            {--with-security-middleware : Generate CheckRole and AdminPanelProtection middleware}
                            {--with-factories : Generate model factory with Faker data}
                            {--with-soft-deletes : Add soft deletes to model and migration}
                            {--with-policies : Generate authorization policy class}
                            {--with-api-auth : Generate API authentication middleware and setup}
                            {--with-tests : Generate feature and unit tests}
                            {--with-audit-log : Generate activity logging with observer}
                            {--with-admin : Generate complete Filament admin panel}
                            {--all : Generate everything (all targets + all features)}
                            {--force : Overwrite existing files}';

    protected $description = 'Scaffold a full CRUD (migration, model, controller, views/resource, routes) from a single command.';

    public function handle(): int
    {
        $name   = $this->argument('name');
        $fields = FieldParser::parse($this->argument('fields'));
        $target = $this->resolveTarget();
        $force  = $this->option('force');

        // If --all flag is set, enable everything
        if ($this->option('all')) {
            $target = 'all';
            $this->input->setOption('target', 'all');
            $this->input->setOption('with-roles', true);
            $this->input->setOption('with-admin', true);
            $this->input->setOption('with-landing', true);
            $this->input->setOption('with-security-middleware', true);
            $this->input->setOption('with-factories', true);
            $this->input->setOption('with-soft-deletes', true);
            $this->input->setOption('with-policies', true);
            $this->input->setOption('with-api-auth', true);
            $this->input->setOption('with-tests', true);
            $this->input->setOption('with-audit-log', true);
        }

        $this->info("🔨 Larahammer Generator — scaffolding <fg=cyan>{$name}</>");
        $this->newLine();

        // --- Core generators (always run) ---
        $this->runGenerator('Migration', fn() => (new MigrationGenerator($name, $fields, $force))->generate());
        $this->runGenerator('Model',     fn() => (new ModelGenerator($name, $fields, $force))->generate());
        $this->runGenerator('Request',   fn() => (new RequestGenerator($name, $fields, $force))->generate());
        $this->runGenerator('Seeder',    fn() => (new SeederGenerator($name, $fields, $force))->generate());

        // --- Optional generators ---
        if ($this->option('with-roles')) {
            $this->runGenerator('Role System', fn() => (new RoleGenerator($name, $fields, $force))->generate());
        }

        if ($this->option('with-landing')) {
            $this->runGenerator('Landing Page', fn() => (new LandingPageGenerator($name, $fields, $force))->generate());
        }

        if ($this->option('with-security-middleware')) {
            $this->runGenerator('Security Middleware', fn() => (new SecurityMiddlewareGenerator($name, $fields, $force))->generate());
        }

        // --- Fase 1: Factories & Soft Deletes ---
        if ($this->option('with-factories')) {
            $this->runGenerator('Model Factory', fn() => (new FactoryGenerator($name, $fields, $force))->generate());
        }

        if ($this->option('with-soft-deletes')) {
            $this->runGenerator('Soft Deletes', fn() => (new SoftDeletesGenerator($name, $fields, $force))->generate());
        }

        // --- Fase 2: Policies & API Auth ---
        if ($this->option('with-policies')) {
            $this->runGenerator('Authorization Policy', fn() => (new PolicyGenerator($name, $fields, $force))->generate());
        }

        if ($this->option('with-api-auth')) {
            $this->runGenerator('API Authentication', fn() => (new ApiAuthenticationGenerator($name, $fields, $force))->generate());
        }

        // --- Fase 3: Tests & Audit Logging ---
        if ($this->option('with-tests')) {
            $this->runGenerator('Tests (Feature + Unit)', fn() => (new TestingGenerator($name, $fields, $force))->generate());
        }

        if ($this->option('with-audit-log')) {
            $this->runGenerator('Activity Logging', fn() => (new AuditLogGenerator($name, $fields, $force))->generate());
        }

        // --- Target-specific generators ---
        if (in_array($target, ['blade', 'all'])) {
            $this->runGenerator('Blade Views + Controller', fn() => (new BladeGenerator($name, $fields, $force))->generate());
        }

        if (in_array($target, ['filament', 'all'])) {
            $this->runGenerator('Filament Resource', fn() => (new FilamentGenerator($name, $fields, $force))->generate());
        }

        if (in_array($target, ['api', 'all'])) {
            $this->runGenerator('API Controller + Resource', fn() => (new ApiGenerator($name, $fields, $force))->generate());
        }

        if ($this->option('with-admin')) {
            $this->runGenerator('Filament Admin Panel', fn() => (new AdminPanelGenerator($name, $fields, $force))->generate());
        }

        // --- Routes ---
        $this->runGenerator('Routes', fn() => (new RouteGenerator($name, $target, $force))->generate());

        $this->newLine();
        $this->info("✅  Done! <fg=cyan>{$name}</> CRUD scaffolded successfully.");
        $this->printNextSteps($name, $target);

        return self::SUCCESS;
    }

    private function resolveTarget(): string
    {
        $target = $this->option('target');

        if ($target && in_array($target, ['blade', 'filament', 'api', 'all'])) {
            return $target;
        }

        return $this->choice(
            'Select output target:',
            [
                'blade'    => 'Blade + Tailwind',
                'filament' => 'Filament v3 Panel',
                'api'      => 'REST API (JSON)',
                'all'      => 'All of the above',
            ],
            'blade'
        );
    }

    private function runGenerator(string $label, callable $generator): void
    {
        try {
            $result = $generator();
            $this->line("  <fg=green>✓</> {$label}" . ($result ? " → <fg=gray>{$result}</>" : ''));
        } catch (\Exception $e) {
            $this->line("  <fg=red>✗</> {$label} — <fg=red>{$e->getMessage()}</>");
        }
    }

    private function printNextSteps(string $name, string $target): void
    {
        $this->newLine();
        $this->line('<fg=yellow>Next steps:</>');
        $this->line('  1. Run <fg=cyan>php artisan migrate</>');

        if ($this->option('with-roles')) {
            $this->line('  2. Run <fg=cyan>php artisan db:seed --class=RoleSeeder</> to seed roles');
        }

        if ($this->option('with-admin')) {
            $this->line('  3. Filament admin panel ready at <fg=cyan>/admin</> (requires Filament v3)');
            $this->line('     - User Management at <fg=cyan>/admin/users</>');
            $this->line('     - Role Management at <fg=cyan>/admin/roles</>');
        } elseif ($target === 'filament' || $target === 'all') {
            $this->line('  3. Run <fg=cyan>php artisan filament:install</> if not installed yet');
        }

        $this->line('  4. Seed dummy data: <fg=cyan>php artisan db:seed --class=' . $name . 'Seeder</>');
        
        if ($this->option('with-audit-log')) {
            $this->newLine();
            $this->line('  5. Register observer for activity logging in <fg=cyan>app/Providers/EventServiceProvider.php</>:');
            $this->line('     <fg=cyan>use App\Models\\' . $name . ';');
            $this->line('     use App\Observers\\' . $name . 'Observer;');
            $this->line('     ' . $name . '::observe(' . $name . 'Observer::class);</>' );
        }

        $this->newLine();

        // --- Feature Summaries ---
        if ($this->option('with-factories')) {
            $this->line('<fg=blue>i</> Factory generated: <fg=cyan>database/factories/' . $name . 'Factory.php</>');
            $this->line('  Usage: <fg=cyan>' . $name . '::factory(10)->create();</>' );
        }

        if ($this->option('with-soft-deletes')) {
            $this->line('<fg=blue>i</> Soft deletes enabled for <fg=cyan>' . $name . '</> model');
        }

        if ($this->option('with-policies')) {
            $this->line('<fg=blue>i</> Policy generated: <fg=cyan>app/Policies/' . $name . 'Policy.php</>');
            $this->line('  Register in <fg=cyan>app/Providers/AuthServiceProvider.php</>');
        }

        if ($this->option('with-api-auth')) {
            $this->line('<fg=blue>i</> API Authentication middleware ready');
            $this->line('  Add to routes/api.php: <fg=cyan>Route::middleware(\'api.auth\')->group(...)</>');
        }

        if ($this->option('with-tests')) {
            $this->line('<fg=blue>i</> Tests generated:');
            $this->line('  - <fg=cyan>tests/Feature/' . $name . 'CrudTest.php</> (feature tests)');
            $this->line('  - <fg=cyan>tests/Unit/' . $name . 'Test.php</> (unit tests)');
            $this->line('  Run: <fg=cyan>php artisan test</>');
        }

        if ($this->option('with-landing')) {
            $this->line('<fg=blue>i</> Landing page generated at <fg=cyan>resources/views/landing.blade.php</>');
            $this->line('  Add route: <fg=cyan>Route::get(\'/\', [LandingController::class, \'index\'])->name(\'home\');</> in routes/web.php');
        }

        if ($this->option('with-security-middleware')) {
            $this->line('<fg=blue>i</> Security middleware generated');
            $this->line('  Register in <fg=cyan>app/Http/Kernel.php</> in $routeMiddleware');
        }

        $this->newLine();
    }
}
