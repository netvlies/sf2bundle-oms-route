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
     * @var RedirectRouteInterface[] $redirects
     * @PHPCRODM\Referrers(referenceType="hard", filter="routeTarget")
     */
    protected $redirects = array();

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
     * @return boolean
     */
    public function isPrimaryRoute()
    {
        return $this->getDefault('primaryRoute');
    }

    /**
     * @return mixed
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getPath() ?: '';
    }

    public function setRedirects($redirects)
    {
        $this->redirects = $redirects;
    }

    public function getRedirects()
    {
        return $this->redirects;
    }
}