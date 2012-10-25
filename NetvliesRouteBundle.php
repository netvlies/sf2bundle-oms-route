<?php

namespace Netvlies\Bundle\RouteBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Netvlies\Bundle\RouteBundle\DependencyInjection\Compiler\AddRouteTabCompilerPass;

class NetvliesRouteBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new AddRouteTabCompilerPass());
    }
}
