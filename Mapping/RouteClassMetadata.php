<?php
/*
 * This file is part of the Netvlies DoctrineBridgeBundle
 *
 * (c) Netvlies Internetdiensten
 * author: M. de Krijger <mdekrijger@netvlies.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netvlies\Bundle\RouteBundle\Mapping;

use Metadata\ClassMetadata;
use Metadata\MergeableInterface;

class RouteClassMetadata extends ClassMetadata implements \Serializable, MergeableInterface
{

    public $basePath;

    public $routeName;

    public $updateBasePath;

    public $updateRouteName = true;


    public function serialize()
    {
        return serialize(array(
            $this->name,
            $this->methodMetadata,
            $this->propertyMetadata,
            $this->fileResources,
            $this->createdAt,
            $this->basePath,
            $this->routeName,
            $this->updateBasePath,
            $this->updateRouteName
        ));
    }

    public function unserialize($str)
    {
        list(
            $this->name,
            $this->methodMetadata,
            $this->propertyMetadata,
            $this->fileResources,
            $this->createdAt,
            $this->basePath,
            $this->routeName,
            $this->updateBasePath,
            $this->updateRouteName
            ) = unserialize($str);

        $this->reflection = new \ReflectionClass($this->name);
    }


    public function merge(MergeableInterface $object)
    {

        if(!is_null($object->basePath)){
            $this->basePath = $object->basePath;
        }

        if(!is_null($object->routeName)){
            $this->routeName = $object->routeName;
        }

        if(!is_null($object->updateBasePath)){
            $this->updateBasePath = $object->updateBasePath;
        }

        if(!is_null($object->updateRouteName)){
            $this->updateRouteName = $object->updateRouteName;
        }
    }


}