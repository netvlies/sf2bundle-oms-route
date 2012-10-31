<?php
/*
* (c) Netvlies Internetdiensten
*
* Richard van den Brand <richard@netvlies.net>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace Netvlies\Bundle\RouteBundle\Controller;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class RedirectRouteController
{
    /**
     * @var RouterInterface
     */
    protected $router;
    /**
     * @param RouterInterface $router the router to use to build urls
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @param RedirectRoute $contentDocument
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \RuntimeException
     */
    public function redirectAction($contentDocument)
    {
        $url = $contentDocument->getUri();

        if (empty($url)) {
            if ($routeTarget = $contentDocument->getRouteTarget()) {
                $url = $this->router->generate($routeTarget, $contentDocument->getParameters(), true);
            } else if ($documentTarget = $contentDocument->getDocumentTarget()) {
                $url = $this->router->generate($documentTarget->getRoute(), $contentDocument->getParameters(), true);
            } else {
                $routeName = $contentDocument->getRouteName();
                $url = $this->router->generate($routeName, $contentDocument->getParameters(), true);
            }
        }

        return new RedirectResponse($url, $contentDocument->isPermanent() ? 301 : 302);
//
//        $class = '\Netvlies\Bundle\RouteBundle\Document\RedirectRoute';
//        if(empty($contentDocument)){
//            throw new \RuntimeException(sprintf('Expected contentDocument to be an instance of %s got empty variable instead', $class));
//        }
//        if(! $contentDocument instanceof $class){
//            throw new \RuntimeException(sprintf('Expected contentDocument to be an instance of %s got instance of %s instead', $class, get_class($contentDocument)));
//        }
//
//        $route = $contentDocument->getRouteTarget();
//        $url = $this->generateUrl(null, array('route' => $route));
//
//        return new RedirectResponse($url);
    }


}
