<?php

namespace Glugox\Magic\Actions\Build;

use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Helpers\StubHelper;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Support\ControllerBaseResolver;
use Glugox\Magic\Support\MagicNamespaces;
use Glugox\Magic\Support\MagicPaths;
use Glugox\Magic\Traits\AsDescribableAction;
use Glugox\Magic\Traits\CanLogSectionTitle;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

#[ActionDescription(
    name: 'enable_attachable',
    description: 'Copies all necessary files for image attachments if any entity requires images.'
)]
class EnableAttachableAction implements DescribableAction
{
    use AsDescribableAction, CanLogSectionTitle;

    protected string $stubsDir;

    private BuildContext $context;

    public function __construct()
    {
        $this->stubsDir = __DIR__.'/../../../stubs';
    }

    public function __invoke(BuildContext $context): BuildContext
    {
        $this->context = $context;
        $this->logInvocation($this->describe()->name);

        if (! $context->getConfig()->anyEntityHasImages()) {
            Log::channel('magic')->info('No entities require images. Skipping Attachable setup.');

            return $context;
        }

        // Define files to copy with source, destination, and optional timestamp for migrations
        $filesToCopy = [
            ['src' => 'migration/create_attachments_table.php', 'dest' => 'database/migrations', 'timestamped' => true],
            ['src' => 'traits/HasImages.php', 'dest' => 'app/Traits'],
            ['src' => 'models/Attachment.php', 'dest' => 'app/Models'],
            ['src' => 'api-resources/AttachmentResource.php', 'dest' => 'app/Http/Resources'],
            ['src' => 'controllers/AttachmentController.php', 'dest' => 'app/Http/Controllers'],
            ['src' => 'jobs/ProcessAttachment.php', 'dest' => 'app/Jobs'],
            ['src' => 'routes/attachable.php', 'dest' => 'routes'],
            ['src' => 'config/attachments.php', 'dest' => 'config'],
            ['src' => 'placeholders/default.png', 'dest' => 'public/images/placeholders'],
        ];

        if (MagicPaths::isUsingPackage()) {
            $filesToCopy = array_values(array_filter($filesToCopy, function (array $file) {
                return ! in_array($file['src'], [
                    'traits/HasImages.php',
                    'models/Attachment.php',
                    'jobs/ProcessAttachment.php',
                    'routes/attachable.php',
                    'config/attachments.php',
                ], true);
            }));
        }

        foreach ($filesToCopy as $file) {
            $this->copyFile($file);
        }

        if (! MagicPaths::isUsingPackage()) {
            $this->includeAttachableRoutes();
        }

        return $context;
    }

    /**
     * Copies a file from the stubs directory to the specified destination.
     *
     * @param  array{src: string, dest: string, timestamped?: bool}  $file
     */
    protected function copyFile(array $file): void
    {
        $source = $this->stubsDir.'/'.$file['src'];
        $destinationDir = $this->resolveDestinationDirectory($file['dest']);

        if (! is_dir($destinationDir)) {
            mkdir($destinationDir, 0755, true);
            $this->context->registerCreatedFolder($destinationDir);
        }

        $filename = basename($file['src']);
        if (! empty($file['timestamped'])) {
            $filename = date('Y_m_d_His').'_'.$filename;
        }

        $destination = $destinationDir.'/'.$filename;

        if ($file['src'] === 'controllers/AttachmentController.php') {
            $contents = file_get_contents($source);
            $contents = StubHelper::replaceBaseNamespace($contents);

            $base = ControllerBaseResolver::resolve($this->context->isPackageBuild());
            $import = $base['import'];
            if ($import !== '') {
                $import .= "\n";
            }

            $contents = str_replace(
                ['{{controllerBaseImport}}', '{{controllerBaseClass}}'],
                [$import, $base['class']],
                $contents
            );

            if (MagicPaths::isUsingPackage()) {
                $baseNamespace = MagicNamespaces::base();
                $contents = str_replace([
                    'use '.$baseNamespace.'\\Jobs\\ProcessAttachment;',
                    'use '.$baseNamespace.'\\Models\\Attachment;',
                ], [
                    'use Glugox\\Module\\Jobs\\ProcessAttachment;',
                    'use Glugox\\Module\\Models\\Attachment;',
                ], $contents);
            }

            file_put_contents($destination, $contents);
        } else {
            copy($source, $destination);
        }

        $this->context->registerGeneratedFile($destination);

        Log::channel('magic')->info("Copied {$filename} to {$file['dest']}");
    }

    protected function includeAttachableRoutes(): void
    {
        $apiRoutesFile = MagicPaths::routes('api.php');
        if (! file_exists($apiRoutesFile)) {
            File::ensureDirectoryExists(dirname($apiRoutesFile));
            File::put($apiRoutesFile, "<?php\n\n");
            $this->context->registerGeneratedFile($apiRoutesFile);
        }

        $includeLine = MagicPaths::isUsingPackage()
            ? "require __DIR__.'/attachable.php';\n"
            : "require base_path('routes/attachable.php');\n";

        if (! str_contains(file_get_contents($apiRoutesFile), $includeLine)) {
            file_put_contents($apiRoutesFile, "\n".$includeLine, FILE_APPEND);
            $this->context->registerUpdatedFile($apiRoutesFile);
            Log::channel('magic')->info('Included attachable routes in routes/api.php');
        } else {
            Log::channel('magic')->info('Attachable routes already included in routes/api.php');
        }
    }

    protected function resolveDestinationDirectory(string $destination): string
    {
        return match (true) {
            str_starts_with($destination, 'app/') => MagicPaths::app(mb_substr($destination, 4)),
            $destination === 'app' => MagicPaths::app(),
            str_starts_with($destination, 'database/') => MagicPaths::database(mb_substr($destination, 9)),
            $destination === 'database' => MagicPaths::database(),
            str_starts_with($destination, 'routes') => MagicPaths::routes(mb_trim(mb_substr($destination, 6), '/')),
            str_starts_with($destination, 'config') => MagicPaths::base($destination),
            str_starts_with($destination, 'public') => MagicPaths::base($destination),
            default => MagicPaths::base($destination),
        };
    }
}
