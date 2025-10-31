<?php

namespace Glugox\Magic\Support;

class ControllerBaseResolver
{
    /**
     * Resolve the base controller import and class for generated controllers.
     *
     * @return array{import: string, class: string}
     */
    public static function resolve(bool $packageBuild): array
    {
        if ($packageBuild) {
            return [
                'import' => 'use Glugox\\Module\\Http\\Controller as ModuleController;',
                'class' => 'ModuleController',
            ];
        }

        return [
            'import' => 'use '.MagicNamespaces::httpControllers('Controller').';',
            'class' => 'Controller',
        ];
    }
}
