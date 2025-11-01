<?php

namespace Glugox\Orchestrator\Services;

use Glugox\Orchestrator\ModuleDescriptor;
use Glugox\Orchestrator\ModuleManifest;
use Glugox\Orchestrator\Support\ModuleDiscovery;
use Glugox\Orchestrator\Support\OrchestratorConfig;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class ModuleRegistry
{
    /**
     * @var array<string, ModuleDescriptor>
     */
    protected array $modules = [];

    /**
     * @var array<string, mixed>
     */
    protected array $specs = [];

    public function __construct(
        protected OrchestratorConfig $config,
        protected ModuleDiscovery $discovery,
        protected ModuleManifest $manifest
    ) {
        $cached = $this->loadCachedModules();

        if ($cached !== []) {
            $this->modules = $cached;
        } else {
            $this->refresh();
        }
    }

    public function config(): OrchestratorConfig
    {
        return $this->config;
    }

    public function manifest(): ModuleManifest
    {
        return $this->manifest;
    }

    /**
     * @return Collection<int, ModuleDescriptor>
     */
    public function all(): Collection
    {
        return Collection::make(array_values($this->modules));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function specs(): Collection
    {
        return Collection::make($this->specs);
    }

    public function get(string $id): ModuleDescriptor
    {
        $module = $this->modules[$id] ?? null;

        if (! $module) {
            throw new InvalidArgumentException(sprintf('Module [%s] is not registered in the orchestrator manifest.', $id));
        }

        return $module;
    }

    public function setInstalled(string $id, bool $installed): ModuleDescriptor
    {
        $module = $this->get($id);
        $module->markInstalled($installed);

        if ($installed && $module->isEnabled() === false && $this->config->autoEnable()) {
            $module->markEnabled(true);
        }

        $this->modules[$id] = $module;

        return $module;
    }

    public function setEnabled(string $id, bool $enabled): ModuleDescriptor
    {
        $module = $this->get($id);
        $module->markEnabled($enabled);
        $this->modules[$id] = $module;

        return $module;
    }

    /**
     * @return array<string, ModuleDescriptor>
     */
    public function refresh(bool $writeManifest = true): array
    {
        $state = [];

        foreach ($this->modules as $module) {
            $state[$module->id()] = [
                ModuleDescriptor::ATTRIBUTE_INSTALLED => $module->isInstalled(),
                ModuleDescriptor::ATTRIBUTE_ENABLED => $module->isEnabled(),
            ];
        }

        foreach ($this->manifest->load() as $attributes) {
            if (! is_array($attributes) || ! isset($attributes[ModuleDescriptor::ATTRIBUTE_ID])) {
                continue;
            }

            $state[$attributes[ModuleDescriptor::ATTRIBUTE_ID]] = [
                ModuleDescriptor::ATTRIBUTE_INSTALLED => (bool) ($attributes[ModuleDescriptor::ATTRIBUTE_INSTALLED] ?? $this->config->autoInstall()),
                ModuleDescriptor::ATTRIBUTE_ENABLED => (bool) ($attributes[ModuleDescriptor::ATTRIBUTE_ENABLED] ?? $this->config->autoEnable()),
            ];
        }

        $modules = [];

        foreach ($this->discovery->discover() as $module) {
            $override = $state[$module->id()] ?? null;

            if ($override) {
                $module->markInstalled($override[ModuleDescriptor::ATTRIBUTE_INSTALLED]);
                $module->markEnabled($override[ModuleDescriptor::ATTRIBUTE_ENABLED]);
            }

            $modules[$module->id()] = $module;
        }

        ksort($modules);

        $this->modules = $modules;


        // Specs

        $specs = [];
        foreach ($this->discovery->discoverSpecs() as $spec) {
            $specs[$spec->id()] = $spec;
        }

        ksort($specs);
        $this->specs = $specs;


        if ($writeManifest) {
            $this->persist();
        }

        return $this->modules;
    }

    public function persist(): void
    {
        $payload = [];

        foreach ($this->modules as $module) {
            $payload[$module->id()] = $module->toArray();
        }

        $this->manifest->write($payload);
    }

    public function clear(): void
    {
        $this->modules = [];
        $this->manifest->delete();
    }

    /**
     * @return array<string, ModuleDescriptor>
     */
    protected function loadCachedModules(): array
    {
        if (! $this->manifest->exists()) {
            return [];
        }

        $data = $this->manifest->load();
        $modules = [];

        foreach ($data as $attributes) {
            if (! is_array($attributes) || ! isset($attributes[ModuleDescriptor::ATTRIBUTE_ID])) {
                continue;
            }

            $descriptor = ModuleDescriptor::fromArray($attributes);
            $modules[$descriptor->id()] = $descriptor;
        }

        return $modules;
    }
}
