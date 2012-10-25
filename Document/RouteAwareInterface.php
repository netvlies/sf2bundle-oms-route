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
//    /**
//     * @abstract
//     * @return string
//     */
//    public function getRouteBasePath();
    
//    /**
//     * @abstract
//     * @param string $routeBasePath
//     */
//    public function setRouteBasePath($routeBasePath);

//    /**
//     * @abstract
//     * @return string
//     */
//    public function getRouteName();

    /**
     * @abstract
     * @param \Netvlies\Bundle\RouteBundle\Document\RouteInterface $defaultRoute
     */
    public function setDefaultRoute(RouteInterface $defaultRoute);

    /**
     * @abstract
     * @return \Netvlies\Bundle\RouteBundle\Document\RouteInterface
     */
    public function getDefaultRoute();

//    /**
//     * This method is used for switching the default route
//     *
//     * @abstract
//     * @param mixed $redirect
//     */
//    public function setDefaultRouteSwitch($redirect);
//
//    /**
//     * @abstract
//     * @return mixed
//     */
//    public function getDefaultRouteSwitch();

    /**
     * This method should only be used on creation of a new document. And only when you want to set the route manually
     *
     * @abstract
     * @param \Netvlies\Bundle\RouteBundle\Document\RouteInterface $primaryRoute
     * @return mixed
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
     * @abstract
     * @param \Netvlies\Bundle\RouteBundle\Document\RedirectRouteInterface[] $redirects
     * @return mixed
     */
    public function setRedirects($redirects);

    /**
     * @abstract
     * @return \Netvlies\Bundle\RouteBundle\Document\RedirectRouteInterface[]
     */
    public function getRedirects();

    /**
     * @abstract
     * @param \Netvlies\Bundle\RouteBundle\Document\RedirectRouteInterface $redirect
     * @return mixed
     */
    public function addRedirects(RedirectRouteInterface $redirect);

    /**
     * @abstract
     * @param \Netvlies\Bundle\RouteBundle\Document\RedirectRouteInterface $redirect
     * @return mixed
     */
    public function removeRedirects(RedirectRouteInterface $redirect);

}
