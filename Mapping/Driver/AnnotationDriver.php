<?php
/*
 * This file is part of the Netvlies RouteBundle
 *
 * (c) Netvlies Internetdiensten
 * author: M. de Krijger <mdekrijger@netvlies.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netvlies\Bundle\RouteBundle\Mapping\Driver;

use Metadata\Driver\DriverInterface;
use Metadata\ClassMetadata;
use Doctrine\Common\Annotations\Reader;
use Netvlies\Bundle\RouteBundle\Mapping\RouteClassMetadata;
use Netvlies\Bundle\RouteBundle\Mapping\Annotations as OMSROUTE;


class AnnotationDriver implements DriverInterface
{

    private $reader;

    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    public function loadMetadataForClass(\ReflectionClass $class)
    {
        $classMetadata = new RouteClassMetadata($class->getName());

        $classAnnotations = $this->reader->getClassAnnotations($class);
        foreach($classAnnotations as $classAnnot){
            if($classAnnot instanceof OMSROUTE\Routing){
                $classMetadata->basePath = $classAnnot->basePath;
                $classMetadata->routeName = $classAnnot->routeName;
                $classMetadata->updateBasePath = $classAnnot->updateBasePath;
                $classMetadata->updateRouteName = $classAnnot->updateRouteName;
            }
        }

        return $classMetadata;
    }
}