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

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Symfony\Cmf\Bundle\RoutingExtraBundle\Document\Route as BaseRoute;
use Netvlies\Bundle\RouteBundle\Document\RouteInterface;

/**
 * Default document for OMS routing
 *
 * @PHPCRODM\Document(referenceable=true)
 */
class Route extends BaseRoute implements RouteInterface
{
    /**
     * @var \Doctrine\ODM\PHPCR\ReferrersCollection $redirects
     * @PHPCRODM\Referrers(referenceType="weak", filter="routeTarget")
     */
    protected $redirects;

    public function __construct()
    {
        $this->redirects = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @todo setPath is not available in base class and this is with a reason (setPath modifies id) check if this method is still needed
     * Used in PHPCR
     *
     * @param $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * @return mixed
     */
    public function getChildren()
    {
        return $this->children;
    }

    public function getRedirects()
    {
        return $this->redirects;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getPath() ?: '';
    }
}
