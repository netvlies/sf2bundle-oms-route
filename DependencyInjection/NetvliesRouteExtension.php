<?php

namespace Netvlies\Bundle\RouteBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class NetvliesRouteExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        foreach ($config as $key => $value) {
            $container->setParameter(sprintf('netvlies_routing.%s', $key), $value);
        }

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');


        $cacheDirectory = '%kernel.cache_dir%/routing_annotations';
        $cacheDirectory = $container->getParameterBag()->resolveValue($cacheDirectory);
        if (!is_dir($cacheDirectory)) {
            mkdir($cacheDirectory, 0777, true);
        }

        // the cache directory should be the first argument of the cache service
        $container
            ->getDefinition('netvlies_routing.metadata_cache')
            ->replaceArgument(0, $cacheDirectory)
        ;
    }
}
