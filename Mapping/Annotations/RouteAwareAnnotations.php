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

namespace Netvlies\Bundle\RouteBundle\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class Routing
{
    /**
     * @var string
     * parameter routing_root is used as prefix
     */
    public $basePath = '';

    /**
     * @var string
     */
    public $routeName;

    /**
     * @var boolean
     */
    public $updateBasePath = false;

    /**
     * @var boolean
     */
    public $updateRouteName = false;
}

