<?php
/*
 * (c) Netvlies Internetdiensten
 *
 * @author Sjoerd Peters <speters@netvlies.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Netvlies\Bundle\RouteBundle\Document;

use Symfony\Cmf\Component\Routing\RouteAwareInterface as BaseRouteAwareInterface;
use Netvlies\Bundle\RouteBundle\Document\RouteInterface;
use Netvlies\Bundle\RouteBundle\Document\RedirectRouteInterface;

interface RouteAwareInterface extends BaseRouteAwareInterface
{   

    /**
     * Set the default route.
     *
     * @abstract
     * @param \Netvlies\Bundle\RouteBundle\Document\RouteInterface $defaultRoute
     */
    public function setDefaultRoute(RouteInterface $defaultRoute);

    /**
     * Must return the default route. Default route is the route that is indexed by search engines. It is the only route
     * which has no 301 redirect status header.
     *
     * @abstract
     * @return \Netvlies\Bundle\RouteBundle\Document\RouteInterface
     */
    public function getDefaultRoute();

    /**
     * This method should only be used on creation of a new document. And only when you want to set the route manually
     * Primary route is the permalink for a document.
     *
     * @abstract
     * @param \Netvlies\Bundle\RouteBundle\Document\RouteInterface $primaryRoute
     */
    public function setPrimaryRoute(RouteInterface $primaryRoute);

    /**
     * This route is used as permalink. It should never change once created.
     *
     * @abstract
     * @return RouteInterface
     */
    public function getPrimaryRoute();

    /**
     * The override all existing/setting new redirects, use this method
     *
     * @abstract
     * @param \Netvlies\Bundle\RouteBundle\Document\RedirectRouteInterface[] $redirects
     */
    public function setRedirects($redirects);

    /**
     * Return all connected redirect routes
     *
     * @abstract
     * @return \Netvlies\Bundle\RouteBundle\Document\RedirectRouteInterface[]
     */
    public function getRedirects();

    /**
     * @abstract
     * @param \Netvlies\Bundle\RouteBundle\Document\RedirectRouteInterface $redirect
     */
    public function addRedirects(RedirectRouteInterface $redirect);

    /**
     * @abstract
     * @param \Netvlies\Bundle\RouteBundle\Document\RedirectRouteInterface $redirect
     */
    public function removeRedirects(RedirectRouteInterface $redirect);

    /**
     * Get the route that is automatically updated
     *
     * @abstract
     * @return \Netvlies\Bundle\RouteBundle\Document\RouteInterface
     */
    public function getAutoRoute();

    /**
     * Set the route that is automatically updated
     *
     * @abstract
     * @param \Netvlies\Bundle\RouteBundle\Document\RouteInterface $route
     */
    public function setAutoRoute(RouteInterface $route);

}
