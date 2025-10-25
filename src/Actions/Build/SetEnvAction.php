<?php

namespace Glugox\Magic\Actions\Build;

use Glugox\Magic\Attributes\ActionDescription;
use Glugox\Magic\Contracts\DescribableAction;
use Glugox\Magic\Helpers\EnvHelper;
use Glugox\Magic\Support\BuildContext;
use Glugox\Magic\Traits\AsDescribableAction;
use Glugox\Magic\Traits\CanLogSectionTitle;

#[ActionDescription(
    name: 'set_env',
    description: 'Sets specific environment variables in the .env file for the application.',
    parameters: ['context' => 'The BuildContext containing the Config object, the configuration instance that has info for app and all entities.']
)]
class SetEnvAction implements DescribableAction
{
    use AsDescribableAction;
    use CanLogSectionTitle;

    protected BuildContext $context;

    public function __invoke(BuildContext $context): BuildContext
    {
        $this->logInvocation($this->describe()->name);

        $this->context = $context;

        if ($context->isPackageBuild()) {
            return $context;
        }

        /**
         * SESSION_SECURE_COOKIE=false
         * SESSION_DOMAIN=orchestrator.test
         * SANCTUM_STATEFUL_DOMAINS=orchestrator.test
         */
        $appUrl = config('app.url');
        $domain = parse_url($appUrl, PHP_URL_HOST) ?: 'localhost';

        EnvHelper::setEnvValue('SESSION_SECURE_COOKIE', 'false');
        EnvHelper::setEnvValue('SESSION_DOMAIN', $domain);
        EnvHelper::setEnvValue('SANCTUM_STATEFUL_DOMAINS', $domain);

        // MAGIC_DEV_MODE
        EnvHelper::setEnvValue('MAGIC_DEV_MODE', $context->getConfig()->app->isDevMode() ? 'true' : 'false');

        return $context;
    }
}
