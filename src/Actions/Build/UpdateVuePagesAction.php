<?php

namespace Glugox\Magic\Actions\Build;

use Glugox\Magic\Actions\Files\GenerateFileAction;
use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Support\MagicPaths;
use Glugox\Magic\Traits\AsDescribableAction;
use Glugox\Magic\Traits\CanLogSectionTitle;
use Illuminate\Support\Facades\File;
use RuntimeException;

#[ActionDescription(
    name: 'update_vue_pages',
    description: 'Updates Vue.js files like sidebar and app logo with the latest configuration details, such as navigation items and application name.',
    parameters: ['context' => 'The BuildContext containing the Config object, the configuration instance that has info for app and all entities.']
)]
class UpdateVuePagesAction implements DescribableAction
{
    use AsDescribableAction, CanLogSectionTitle;

    /**
     * Context with config
     */
    protected BuildContext $context;

    /**
     * Path to the AppLogo.vue file
     */
    protected string $appLogoPath;

    /**
     * Path to the AppSidebar.vue file
     */
    protected string $sidebarPath;

    /**
     * Path to Vue component stubs for fallback scaffolding.
     */
    protected string $componentStubPath;

    public function __construct()
    {
        // Path to your Vue file
        $this->sidebarPath = MagicPaths::resource('js/components/AppSidebar.vue');
        $this->appLogoPath = MagicPaths::resource('js/components/AppLogo.vue');
        $this->componentStubPath = __DIR__.'/../../../stubs/laravel/resources/js/components';
    }

    public function __invoke(BuildContext $context): BuildContext
    {
        // Log section title
        $this->logInvocation($this->describe()->name);

        $this->context = $context;

        $this->updateSidebar();
        $this->updateAppName();

        return $this->context;
    }

    /**
     * Update the sidebar Vue file with the latest navigation items.
     *
     * @throws RuntimeException
     */
    public function updateSidebar()
    {
        [$existing, $content] = $this->resolveComponentContents(
            $this->sidebarPath,
            'AppSidebar.vue',
            'Sidebar file not found at '
        );

        // Generate the new mainNavItems array as Vue code
        $itemsCode = $this->generateNavItemsCode();

        // Replace the existing array in the file
        $content = preg_replace(
            '/const mainNavItems: NavItem\[\] = \[.*?\];/s',
            $itemsCode,
            $content
        );

        // Update imports as well
        $content = $this->updateImports($content);

        app(GenerateFileAction::class)($this->sidebarPath, $content);

        if ($existing) {
            $this->context->registerUpdatedFile($this->sidebarPath);

            return;
        }

        $this->context->registerGeneratedFile($this->sidebarPath);
    }

    /**
     * Update app name
     */
    public function updateAppName(): void
    {
        $appName = $this->context->getConfig()->app->name;
        [$existing, $content] = $this->resolveComponentContents(
            $this->appLogoPath,
            'AppLogo.vue',
            'App logo file not found at '
        );

        // Replace the app name in the file
        $updatedContent = str_replace(
            'Laravel Starter Kit',
            $appName,
            $content
        );

        // Action call -- use the GenerateFileAction to update the file
        app(GenerateFileAction::class)($this->appLogoPath, $updatedContent);

        if ($existing) {
            $this->context->registerUpdatedFile($this->appLogoPath);

            return;
        }

        $this->context->registerGeneratedFile($this->appLogoPath);
    }

    /**
     * Generate the navigation items code for the sidebar.
     */
    protected function generateNavItemsCode(): string
    {
        $lines = [];
        foreach ($this->context->getConfig()->entities as $entity) {
            $lines[] = "    { title: '{$entity->getPluralName()}', href: '{$entity->getHref()}', icon: {$entity->getIcon()} },";
        }

        $itemsJs = implode("\n", $lines);

        return <<<JS
const mainNavItems: NavItem[] = [
{$itemsJs}
];
JS;
    }

    /**
     * Add a cleaned-up lucide import after all existing imports.
     * Leaves original imports untouched.
     */
    protected function updateImports(string $content): string
    {
        // Collect needed icons from entities
        $icons = [];
        foreach ($this->context->getConfig()->entities as $entity) {
            $icons[] = $entity->getIcon();
        }
        $icons = array_unique($icons);
        sort($icons);

        // 🔍 Extract already-imported icons from file
        preg_match_all("/import\s*{\s*([^}]*)}\s*from\s*'lucide-vue-next';/", $content, $matches);
        $alreadyImported = [];
        if (! empty($matches[1])) {
            foreach ($matches[1] as $importGroup) {
                foreach (explode(',', $importGroup) as $icon) {
                    $alreadyImported[] = mb_trim($icon);
                }
            }
        }

        // 🚫 Remove duplicates
        $icons = array_diff($icons, $alreadyImported);

        // If nothing new, just remove the old magic block
        if (empty($icons)) {
            return preg_replace(
                '/\/\/ magic:icons-start-\+.*?\/\/ magic:icons-end\s*/s',
                '',
                $content
            );
        }

        $iconsCode = 'import { '.implode(', ', $icons)." } from 'lucide-vue-next';";

        // Remove any previously inserted block
        $content = preg_replace(
            '/\/\/ magic:icons-start-\+.*?\/\/ magic:icons-end\s*/s',
            '',
            $content
        );

        // Insert after the last import line
        if (preg_match_all('/^import .*;$/m', $content, $matches, PREG_OFFSET_CAPTURE)) {
            $lastImport = end($matches[0]);
            $pos = $lastImport[1] + mb_strlen($lastImport[0]);

            $before = mb_substr($content, 0, $pos);
            $after = mb_substr($content, $pos);

            $content = $before."\n// magic:icons-start-+\n{$iconsCode}\n// magic:icons-end\n".$after;
        } else {
            $content = "// magic:icons-start-+\n{$iconsCode}\n// magic:icons-end\n".$content;
        }

        return $content;
    }

    /**
     * Resolve the contents for a Vue component, scaffolding from stubs when needed.
     */
    protected function resolveComponentContents(string $path, string $stubFile, string $missingMessagePrefix): array
    {
        if (File::exists($path)) {
            return [true, File::get($path)];
        }

        if (! MagicPaths::isUsingPackage()) {
            throw new RuntimeException($missingMessagePrefix.$path);
        }

        $stubPath = $this->componentStubPath.'/'.$stubFile;

        if (! File::exists($stubPath)) {
            throw new RuntimeException("Vue stub not found: {$stubPath}");
        }

        return [false, File::get($stubPath)];
    }
}
