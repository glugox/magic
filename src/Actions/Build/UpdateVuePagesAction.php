<?php

namespace Glugox\Magic\Actions\Build;


use Glugox\Magic\Actions\Files\GenerateFileAction;
use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Traits\AsDescribableAction;
use Illuminate\Support\Facades\File;

#[ActionDescription(
    name: 'update_vue_pages',
    description: 'Updates Vue.js files like sidebar and app logo with the latest configuration details, such as navigation items and application name.',
    parameters: ['context' => 'The BuildContext containing the Config object, the configuration instance that has info for app and all entities.']
)]
class UpdateVuePagesAction implements DescribableAction
{
    use AsDescribableAction;

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

    public function __construct() {
        // Path to your Vue file
        $this->sidebarPath = base_path('resources/js/components/AppSidebar.vue');
        $this->appLogoPath = base_path('resources/js/components/AppLogo.vue');
    }

    /**
     * @param BuildContext $context
     * @return BuildContext
     */
    public function __invoke(BuildContext $context): BuildContext
    {
        $this->context = $context;

        $this->updateSidebar();
        $this->updateAppName();

        return $this->context;
    }

    /**
     * Update the sidebar Vue file with the latest navigation items.
     *
     * @throws \RuntimeException
     */
    public function updateSidebar()
    {
        if (! File::exists($this->sidebarPath)) {
            throw new \RuntimeException("Sidebar file not found at {$this->sidebarPath}");
        }

        $content = File::get($this->sidebarPath);

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
        $this->context->registerUpdatedFile($this->sidebarPath);
    }

    /**
     * Update app name
     */
    public function updateAppName(): void
    {
        $appName = $this->context->getConfig()->app->name;
        $content = File::get($this->appLogoPath);

        // Replace the app name in the file
        $updatedContent = preg_replace(
            '/<span class="mb-0.5 truncate leading-tight font-semibold">.*?<\/span>/',
            "<span class=\"mb-0.5 truncate leading-tight font-semibold\">{$appName}</span>",
            $content
        );

        // Action call -- use the GenerateFileAction to update the file
        app(GenerateFileAction::class)($this->appLogoPath, $updatedContent);
        $this->context->registerUpdatedFile($this->appLogoPath);
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
                    $alreadyImported[] = trim($icon);
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
            $pos = $lastImport[1] + strlen($lastImport[0]);

            $before = substr($content, 0, $pos);
            $after = substr($content, $pos);

            $content = $before."\n// magic:icons-start-+\n{$iconsCode}\n// magic:icons-end\n".$after;
        } else {
            $content = "// magic:icons-start-+\n{$iconsCode}\n// magic:icons-end\n".$content;
        }

        return $content;
    }
}
