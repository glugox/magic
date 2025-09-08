<?php

namespace Glugox\Magic\Actions\Build;

use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Traits\AsDescribableAction;
use Glugox\Magic\Traits\CanLogSectionTitle;
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

        foreach ($filesToCopy as $file) {
            $this->copyFile($file);
        }

        $this->includeAttachableRoutes();

        return $context;
    }

    protected function copyFile(array $file): void
    {
        $source = $this->stubsDir.'/'.$file['src'];
        $destinationDir = base_path($file['dest']);

        if (! is_dir($destinationDir)) {
            mkdir($destinationDir, 0755, true);
            $this->context->registerCreatedFolder($destinationDir);
        }

        $filename = basename($file['src']);
        if (! empty($file['timestamped'])) {
            $filename = date('Y_m_d_His').'_'.$filename;
        }

        $destination = $destinationDir.'/'.$filename;
        copy($source, $destination);
        $this->context->registerGeneratedFile($destination);

        Log::channel('magic')->info("Copied {$filename} to {$file['dest']}");
    }

    protected function includeAttachableRoutes(): void
    {
        $apiRoutesFile = base_path('routes/api.php');
        $includeLine = "require base_path('routes/attachable.php');\n";

        if (! str_contains(file_get_contents($apiRoutesFile), $includeLine)) {
            file_put_contents($apiRoutesFile, "\n".$includeLine, FILE_APPEND);
            $this->context->registerUpdatedFile($apiRoutesFile);
            Log::channel('magic')->info('Included attachable routes in routes/api.php');
        } else {
            Log::channel('magic')->info('Attachable routes already included in routes/api.php');
        }
    }
}
