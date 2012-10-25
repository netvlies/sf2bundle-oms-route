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
use Netvlies\Bundle\OmsBundle\DependencyInjection\Compiler\OmsSonataAdminExtensionCompilerPass;

class AddRouteTabCompilerPass extends OmsSonataAdminExtensionCompilerPass
{
    public function process(ContainerBuilder $container)
    {
        foreach ($container->findTaggedServiceIds('sonata.admin') as $id => $tags) {
            $this->adminAddInterfaceExtension(
                $container->getDefinition($id),
                $container,
                '\Netvlies\Bundle\RouteBundle\Document\RouteAwareInterface',
                'netvlies_routing.route_tab.admin_extension'
            );
        }
    }
}
