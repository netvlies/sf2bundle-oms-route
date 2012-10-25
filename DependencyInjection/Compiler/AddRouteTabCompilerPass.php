<?php
/**
 * Created by JetBrains PhpStorm.
 * User: sjopet
 * Date: 31-1-12
 * Time: 13:16
 * To change this template use File | Settings | File Templates.
 */

namespace Netvlies\Bundle\RouteBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;
use Sonata\AdminBundle\Admin\AdminExtension;

use Netvlies\Bundle\RouteBundle\Route\RouteAccessInterface;

class AddRouteTabCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        foreach ($container->findTaggedServiceIds('sonata.admin') as $id => $tags) {

            $tabEnabled = true;
            foreach ($tags as $attributes) {
                if (isset($attributes['extensions']) && $attributes['extensions'] == false) {
                    $tabEnabled = false;
                    break;
                }
            }

            if(!$tabEnabled){
                continue;
            }

            $admin = $container->getDefinition($id);
            $class = $admin->getArgument(1);
            $instance = new $class();

            if ($instance instanceof RouteAccessInterface) {
                $extension = new Reference('netvlies_oms.route_tab.admin_extension');
                $admin->addMethodCall('addExtension', array($extension));
            }
        }
    }
}