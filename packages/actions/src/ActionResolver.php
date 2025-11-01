<?php

namespace Glugox\Actions;

use Glugox\Actions\Contracts\Action;

/**
 * Find action's class by name or alias like 'user.export
 */
class ActionResolver
{

    /**
     * Resolve action class by its name
     *
     * @param string $actionName
     * @return string|null
     */
    public static function resolve(string $actionName): ?string
    {
        $actions = [];

        $actionsDirectory = app_path("Actions");
        if (is_dir($actionsDirectory)) {
            $files = scandir($actionsDirectory);
            foreach ($files as $file) {
                if (str_ends_with($file, '.php')) {
                    $className = pathinfo($file, PATHINFO_FILENAME);
                    $actions[] = "App\\Actions\\{$className}";
                }
            }
        }

        foreach ($actions as $actionClass) {
            if (!class_exists($actionClass)) {
                continue;
            }
            /** @var Action $instance */
            $instance = new $actionClass();
            if (method_exists($instance, 'name')) {
                $name = $instance->name();
                if ($actionName === $name) {
                    return $actionClass;
                }
            }
        }
        return null;
    }
}