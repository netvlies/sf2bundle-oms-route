<?php
/*
 * (c) Netvlies Internetdiensten
 *
 * @author Sjoerd Peters <speters@netvlies.net>
 * @author Marco de Krijger <mdekrijger@netvlies.nl>
 * @author Kristian Zondervan <kristian@netvlies.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Netvlies\Bundle\RouteBundle\Document;

use Symfony\Cmf\Bundle\RoutingExtraBundle\Document\RedirectRoute as BaseRedirectRoute;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Symfony\Component\Validator\Constraints as Assert;
use Netvlies\Bundle\PageBundle\Document\ContentInterface;
use Netvlies\Bundle\RouteBundle\Document\RedirectRouteInterface;
use Netvlies\Bundle\RouteBundle\Document\RouteInterface;

/**
 * Default document for OMS routing
 *
 * @PHPCRODM\Document(referenceable=true)
 */
class RedirectRoute extends BaseRedirectRoute implements RouteInterface, RedirectRouteInterface
{
//    /**
//     * @Assert\NotBlank
//     * @var string $name
//     */
//    protected $name;

    /**
     * @var bool $active
     * @PHPCRODM\Boolean()
     */
    protected $active = true;

    public function __construct()
    {
        // Set default to 301 redirect (permanent)
        $this->permanent = true;
    }

    /**
     * @param $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * @param bool $active
     * @return RedirectRouteInterface
     */
    public function setActive($active)
    {
        $this->active = $active;
        return $this;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->active;
    }


    /**
     * Used for deletion checkbox in admin
     * @param bool $active
     */
    public function setInactive($active)
    {
        $this->active = !$active;
    }

    /**
     * Used for deletion checkbox in admin
     * @return bool
     */
    public function isInActive()
    {
        return !$this->active;
    }

    public function __toString()
    {
        return $this->getPath() ?: '';
    }

}
