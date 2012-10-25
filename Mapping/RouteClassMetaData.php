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

class RouteClassMetadata extends ClassMetadata
{

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


    public function getUpdateRouteName()
    {
        return $this->updateRouteName;
    }

    public function getUpdateBasePath()
    {
        return $this->updateBasePath;
    }

    public function getBasePath()
    {
        return $this->basePath;
    }

    public function getRouteName()
    {
        return $this->routeName;
    }
}