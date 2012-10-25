<?php
/**
 * (c) Netvlies Internetdiensten
 *
 * @author M. de Krijger <mdekrijger@netvlies.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netvlies\Bundle\RouteBundle\Tests\Model;

use Netvlies\Bundle\RouteBundle\Mapping\Annotations as ROUTING;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Symfony\Cmf\Bundle\ContentBundle\Document\StaticContent;
use Netvlies\Bundle\RouteBundle\Document\RouteAwareInterface;
use Netvlies\Bundle\RouteBundle\Document\RouteInterface;
use Netvlies\Bundle\RouteBundle\Document\RedirectRouteInterface;


/**
 * @ROUTING\Routing(basePath="bite1", routeName="[title]")
 * @PHPCRODM\Document
 */

class MyPage extends StaticContent implements RouteAwareInterface
{
    /**
     * @param \Netvlies\Bundle\RouteBundle\Document\RouteInterface $defaultRoute
     */
    public function setDefaultRoute(RouteInterface $defaultRoute)
    {
        // TODO: Implement setDefaultRoute() method.
    }

    /**
     * @return \Netvlies\Bundle\RouteBundle\Document\RouteInterface
     */
    public function getDefaultRoute()
    {
        // TODO: Implement getDefaultRoute() method.
    }

    /**
     * This method is used for switching the default route
     *
     * @param mixed $redirect
     */
    public function setDefaultRouteSwitch($redirect)
    {
        // TODO: Implement setDefaultRouteSwitch() method.
    }

    /**
     * @return mixed
     */
    public function getDefaultRouteSwitch()
    {
        // TODO: Implement getDefaultRouteSwitch() method.
    }

    /**
     * This method should only be used on creation of a new document. And only when you want to set the route manually
     *
     * @param \Netvlies\Bundle\RouteBundle\Route\RouteInterface $primaryRoute
     * @return mixed
     */
    public function setPrimaryRoute(RouteInterface $primaryRoute)
    {
        // TODO: Implement setPrimaryRoute() method.
    }

    /**
     * This route is used as permalink. It should never change once created.
     *
     * @return RouteInterface
     */
    public function getPrimaryRoute()
    {
        // TODO: Implement getPrimaryRoute() method.
    }

    /**
     * @param \Netvlies\Bundle\RouteBundle\Route\RedirectRouteInterface[] $redirects
     * @return mixed
     */
    public function setRedirects($redirects)
    {
        // TODO: Implement setRedirects() method.
    }

    /**
     * @return \Netvlies\Bundle\RouteBundle\Route\RedirectRouteInterface[]
     */
    public function getRedirects()
    {
        // TODO: Implement getRedirects() method.
    }

    /**
     * @param \Netvlies\Bundle\RouteBundle\Route\RedirectRouteInterface $redirect
     * @return mixed
     */
    public function addRedirects(RedirectRouteInterface $redirect)
    {
        // TODO: Implement addRedirects() method.
    }

    /**
     * @param \Netvlies\Bundle\RouteBundle\Route\RedirectRouteInterface $redirect
     * @return mixed
     */
    public function removeRedirects(RedirectRouteInterface $redirect)
    {
        // TODO: Implement removeRedirects() method.
    }


}
