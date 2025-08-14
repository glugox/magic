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
    )
    {
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
        if (!File::exists($this->sidebarPath)) {
            throw new \RuntimeException("Sidebar file not found at {$this->sidebarPath}");
        }

        $content = File::get($this->sidebarPath);

        // Generate the new mainNavItems array as Vue code
        $itemsCode = $this->generateNavItemsCode();

        // Replace the existing array in the file
        $updatedContent = preg_replace(
            '/const mainNavItems: NavItem\[\] = \[.*?\];/s',
            $itemsCode,
            $content
        );

        File::put($this->sidebarPath, $updatedContent);
    }

    /**
     * Update app name
     */
    public function updateAppName(): void
    {
        $appName = $this->config->app->getName();
        $content = File::get($this->appLogoPath);

        // Replace the app name in the file
        $updatedContent = preg_replace(
            '/<span class="mb-0.5 truncate leading-tight font-semibold">.*?<\/span>/',
            "<span class=\"mb-0.5 truncate leading-tight font-semibold\">{$appName}</span>",
            $content
        );

        File::put($this->appLogoPath, $updatedContent);
    }


    protected function generateNavItemsCode(): string
    {
        $lines = [];
        foreach ($this->config->getEntities() as $entity) {
            $lines[] = "    { title: '{$entity->getPluralName()}', href: '{$entity->getHref()}', icon: {$entity->getIcon()} },";
        }

        $itemsJs = implode("\n", $lines);

        return <<<JS
const mainNavItems: NavItem[] = [
{$itemsJs}
];
JS;
    }
}

