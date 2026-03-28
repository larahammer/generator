<?php

namespace Larahammer\Generator\Generators;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

abstract class BaseGenerator
{
    protected Filesystem $files;
    protected string $name;
    protected array $fields;
    protected bool $force;

    public function __construct(string $name, array $fields, bool $force = false)
    {
        $this->files  = new Filesystem();
        $this->name   = Str::studly($name);
        $this->fields = $fields;
        $this->force  = $force;
    }

    abstract public function generate(): string;

    protected function getStub(string $stubPath): string
    {
        // Check if user has published custom stubs
        $customStub = base_path("stubs/larahammer/{$stubPath}");

        if ($this->files->exists($customStub)) {
            return $this->files->get($customStub);
        }

        return $this->files->get(__DIR__ . "/../../stubs/{$stubPath}");
    }

    protected function writeFile(string $path, string $content): void
    {
        $dir = dirname($path);

        if (! $this->files->isDirectory($dir)) {
            $this->files->makeDirectory($dir, 0755, true);
        }

        if ($this->files->exists($path) && ! $this->force) {
            throw new \RuntimeException("File already exists. Use --force to overwrite.");
        }

        $this->files->put($path, $content);
    }

    protected function replacePlaceholders(string $stub, array $replacements): string
    {
        foreach ($replacements as $key => $value) {
            $stub = str_replace("{{ {$key} }}", $value, $stub);
        }

        return $stub;
    }

    // --- Common name helpers ---

    protected function modelName(): string
    {
        return $this->name;
    }

    protected function tableName(): string
    {
        return \Illuminate\Support\Str::snake(\Illuminate\Support\Str::plural($this->name));
    }

    protected function variableName(): string
    {
        return \Illuminate\Support\Str::camel($this->name);
    }

    protected function routeName(): string
    {
        return \Illuminate\Support\Str::kebab(\Illuminate\Support\Str::plural($this->name));
    }

    protected function controllerName(): string
    {
        return $this->name . 'Controller';
    }
}
