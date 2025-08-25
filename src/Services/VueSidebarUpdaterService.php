<?php

namespace Glugox\Magic\Services;

use Glugox\Magic\Support\Config\Config;
use Illuminate\Support\Facades\File;

class VueSidebarUpdaterService
{
    protected string $appLogoPath;

    protected string $sidebarPath;

    public function __construct(
        protected Config $config
    ) {
        // Path to your Vue file
        $this->sidebarPath = base_path('resources/js/components/AppSidebar.vue');
        $this->appLogoPath = base_path('resources/js/components/AppLogo.vue');
    }

    /**
     * Update Vue files.
     */
    public function update()
    {
        $this->updateSidebar();
        $this->updateAppName();
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

        app(FileGenerationService::class)->generateFile($this->sidebarPath, $content, true);
    }

    /**
     * Update app name
     */
    public function updateAppName(): void
    {
        $appName = $this->config->app->name;
        $content = File::get($this->appLogoPath);

        // Replace the app name in the file
        $updatedContent = preg_replace(
            '/<span class="mb-0.5 truncate leading-tight font-semibold">.*?<\/span>/',
            "<span class=\"mb-0.5 truncate leading-tight font-semibold\">{$appName}</span>",
            $content
        );

        app(FileGenerationService::class)->generateFile($this->appLogoPath, $updatedContent, true);
    }

    /**
     * Generate the navigation items code for the sidebar.
     */
    protected function generateNavItemsCode(): string
    {
        $lines = [];
        foreach ($this->config->entities as $entity) {
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
        foreach ($this->config->entities as $entity) {
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
