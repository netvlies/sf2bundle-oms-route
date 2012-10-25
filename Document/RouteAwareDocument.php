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
     * @var RouteInterface $primaryRoute
     * @PHPCRODM\ReferenceOne(strategy="hard")
     */
    protected $primaryRoute;

    /**
     * @var RouteInterface $defaultRoute
     * @PHPCRODM\ReferenceOne(strategy="hard")
     */
    protected $defaultRoute;

    /**
     * @var RedirectRouteInterface[] $redirects
     * @PHPCRODM\Referrers(referenceType="hard", filter="documentTarget")
     */
    protected $redirects = array();


    /**
     * @param \Netvlies\Bundle\RouteBundle\Document\RouteInterface $primaryRoute
     * @return mixed|void
     */
    public function setPrimaryRoute(RouteInterface $primaryRoute)
    {
        $this->primaryRoute = $primaryRoute;
    }

    /**
     * @return \Netvlies\Bundle\RouteBundle\Document\RouteInterface
     */
    public function getPrimaryRoute()
    {
        return $this->primaryRoute;
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
     * @param \Symfony\Cmf\Component\Routing\RedirectRouteInterface[] $redirects
     * @return mixed|void
     */
    public function setRedirects($redirects)
    {
        $this->redirects = $redirects;
    }

    /**
     * @return \Symfony\Cmf\Component\Routing\RedirectRouteInterface[]
     */
    public function getRedirects()
    {
        $list = array();
        foreach ($this->redirects as $redirect) {
            if(!$redirect instanceof RedirectRouteInterface || $redirect->getPath() == $this->primaryRoute->getPath()){
                continue;
            }
            if($redirect->isActive()){
                $list[] = $redirect;
            }
        }
        return $list;
    }

    /**
     * @param \Netvlies\Bundle\RouteBundle\Document\RedirectRouteInterface $redirect
     * @return mixed|void
     */
    public function addRedirects(RedirectRouteInterface $redirect)
    {
        $this->redirects[] = $redirect;
    }

    /**
     * @param \Netvlies\Bundle\RouteBundle\Document\RedirectRouteInterface $redirect
     * @return mixed|void
     */
    public function removeRedirects(RedirectRouteInterface $redirect)
    {
        foreach($this->redirects as $key => $route){
            if($route === $redirect){
                unset($this->redirects[$key]);
            }
        }
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
        if (! empty($defaultRoute)) {
            $path = $defaultRoute->getPath();
            $routes[$path] = $defaultRoute;
        }

        $primaryRoute = $this->getPrimaryRoute();
        if (! empty($primaryRoute)) {
            $path = $primaryRoute->getPath();
            $routes[$path] = $primaryRoute;
        }

        foreach ($this->getRedirects() as $redirect) {
            $routes[$redirect->getPath()] = $redirect;
        }

        return $routes;
    }

}
