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

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Netvlies\Bundle\RouteBundle\Document\RedirectRoute;

class RedirectRouteController extends Controller
{
    /**
     * @param RedirectRoute $contentDocument
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \RuntimeException
     */
    public function redirectAction($contentDocument)
    {
        $class = '\Netvlies\Bundle\RouteBundle\Document\RedirectRoute';
        if(empty($contentDocument)){
            throw new \RuntimeException(sprintf('Expected contentDocument to be an instance of %s got empty variable instead', $class));
        }
        if(! $contentDocument instanceof $class){
            throw new \RuntimeException(sprintf('Expected contentDocument to be an instance of %s got instance of %s instead', $class, get_class($contentDocument)));
        }
        
        $route = $contentDocument->getRouteTarget();
        $url = $this->generateUrl(null, array('route' => $route)); 
        
        return new RedirectResponse($url);
    }


}