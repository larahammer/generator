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
                            {--with-admin : Generate complete Filament admin panel}
                            {--with-security-middleware : Generate CheckRole and AdminPanelProtection middleware}
                            {--force : Overwrite existing files}';

    protected $description = 'Scaffold a full CRUD (migration, model, controller, views/resource, routes) from a single command.';

    public function handle(): int
    {
        $name   = $this->argument('name');
        $fields = FieldParser::parse($this->argument('fields'));
        $target = $this->resolveTarget();
        $force  = $this->option('force');

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

        $this->line('  ' . ($this->option('with-roles') || $this->option('with-admin') ? '4' : '3') . '. Seed dummy data: <fg=cyan>php artisan db:seed --class=' . $name . 'Seeder</>');
        $this->newLine();

        if ($this->option('with-landing')) {
            $this->line('<fg=blue>i</> Landing page generated at <fg=cyan>resources/views/landing.blade.php</>');
            $this->line('  Add route: <fg=cyan>Route::get(\'/\', [LandingController::class, \'index\'])->name(\'home\');</> in routes/web.php');
            $this->newLine();
        }

        if ($this->option('with-security-middleware')) {
            $this->line('<fg=blue>i</> Security middleware generated:');
            $this->line('  - <fg=cyan>CheckRole</> middleware for role-based access control');
            $this->line('  - <fg=cyan>AdminPanelProtection</> middleware for admin panel protection');
            $this->line('  Add to <fg=cyan>app/Http/Kernel.php</> in $routeMiddleware:');
            $this->line('    \'role\' => \App\Http\Middleware\CheckRole::class,');
            $this->line('    \'admin\' => \App\Http\Middleware\AdminPanelProtection::class,');
            $this->line('  See <fg=cyan>stubs/kernel_middleware_config.stub</> for route examples');
            $this->newLine();
        }
    }
}
