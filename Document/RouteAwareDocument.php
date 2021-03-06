<?php
/**
 * Created by JetBrains PhpStorm.
 * User: mdekrijger
 * Date: 10/25/12
 * Time: 7:56 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Netvlies\Bundle\RouteBundle\Document;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

abstract class RouteAwareDocument implements RouteAwareInterface
{
    /**
     * @var RouteInterface $defaultRoute
     * @PHPCRODM\ReferenceOne(strategy="weak")
     */
    protected $defaultRoute;


    protected $switchRoute;

    /**
     * @param \Netvlies\Bundle\RouteBundle\Document\RouteInterface $primaryRoute
     * @return mixed|void
     */
    public function setPrimaryRoute(RouteInterface $primaryRoute)
    {
        $routes = $this->getRoutes();
        if(!in_array($primaryRoute, $routes)){
            throw new \Exception('Given route does not exist in current route collection for this document. Please use setDefaultRoute or addRedirects first to add this route');
        }

        foreach($routes as $route){
            if($route == $primaryRoute){
                $route->setDefault('primaryRoute', true);
            }
            else{
                $route->setDefault('primaryRoute', false);
            }
        }
    }

    /**
     * Returns the primary route
     * @return \Netvlies\Bundle\RouteBundle\Document\RouteInterface
     */
    public function getPrimaryRoute()
    {
        $routes = $this->getRoutes();
        foreach($routes as $route){
            if($route->getDefault('primaryRoute')){
                return $route;
            }
        }
    }

    /**
     * @param \Netvlies\Bundle\RouteBundle\Document\RouteInterface $defaultRoute
     * @return mixed|void
     */
    public function setDefaultRoute(RouteInterface $defaultRoute)
    {
        $this->defaultRoute = $defaultRoute;
    }

    /**
     * @return \Netvlies\Bundle\RouteBundle\Document\RouteInterface
     */
    public function getDefaultRoute()
    {
        return $this->defaultRoute;
    }


    /**
     * @return \Doctrine\ODM\PHPCR\ReferrersCollection
     */
    public function getRedirects()
    {
        return $this->getDefaultRoute()->getRedirects();
    }


    /**
     * Return all connected routes in array with path as keyname and route as value
     *
     * @return \Netvlies\Bundle\RouteBundle\Document\RouteInterface[]
     */
    public function getRoutes()
    {
        $routes = array();

        $defaultRoute = $this->getDefaultRoute();

        if (empty($defaultRoute)) {
            return array();
        }

        $path = $defaultRoute->getPath();
        $routes[$path] = $defaultRoute;


        foreach ($this->getRedirects()->toArray() as $redirect) {
            $routes[$redirect->getPath()] = $redirect;
        }

        return $routes;
    }

    /**
     * Get the route that is automatically updated
     *
     * @return \Netvlies\Bundle\RouteBundle\Document\RouteInterface
     */
    public function getAutoRoute()
    {
        $routes = $this->getRoutes();
        foreach($routes as $route){
            if($route->getDefault('autoRoute')){
                return $route;
            }
        }
    }

    /**
     * Set the route that is automatically updated
     *
     * @param \Netvlies\Bundle\RouteBundle\Document\RouteInterface $autoRoute
     */
    public function setAutoRoute(RouteInterface $autoRoute)
    {
        $routes = $this->getRoutes();
        if(!in_array($autoRoute, $routes)){
            throw new \Exception('Given route does not exist in current route collection for this document. Please use setDefaultRoute or addRedirects first to add this route');
        }

        foreach($routes as $route){
            if($route == $autoRoute){
                $route->setDefault('autoRoute', true);
            }
            else{
                $route->setDefault('autoRoute', false);
            }
        }
    }

    public function setSwitchRoute($switchRoute)
    {
        $this->switchRoute = $switchRoute;
    }

    public function getSwitchRoute()
    {
        return $this->switchRoute;
    }
}
