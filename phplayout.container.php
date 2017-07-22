<?php

namespace Drupal\Module\phplayout;

use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use MakinaCorpus\Drupal\Layout\DependencyInjection\Compiler\ItemTypeRegisterPass;
use MakinaCorpus\Layout\LayoutBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Service provider for the phplayout module.
 */
class ServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ItemTypeRegisterPass());
    }

    /**
     * {@inhertidoc}
     */
    public function registerBundles()
    {
        return [
            new LayoutBundle(),
        ];
    }
}
