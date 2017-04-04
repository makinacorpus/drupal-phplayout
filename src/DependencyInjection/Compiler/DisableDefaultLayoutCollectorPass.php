<?php

namespace MakinaCorpus\Drupal\Layout\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Use this pass if you wish to implement layout collection by yourself
 *
 * @codeCoverageIgnore
 */
class DisableDefaultLayoutCollectorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('php_layout.collect_layout_event_subscriber')) {
            $container->removeDefinition('php_layout.collect_layout_event_subscriber');
        }
    }
}
