<?php
/*
 * (c) Netvlies Internetdiensten
 *
 * Sjoerd Peters <speters@netvlies.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Netvlies\Bundle\RouteBundle\Doctrine\Listener;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\EventSubscriber;
use Doctrine\ODM\PHPCR\Event;
use Doctrine\ODM\PHPCR\Event\LifecycleEventArgs;

use Netvlies\Bundle\RouteBundle\Document\RouteInterface;
use Netvlies\Bundle\RouteBundle\Document\RedirectRouteInterface;
use Netvlies\Bundle\RouteBundle\Document\RedirectRoute;
use Netvlies\Bundle\RouteBundle\Routing\RouteService;
use Netvlies\Bundle\RouteBundle\Exception\NullPointerException;

class RouteSubscriber implements EventSubscriber
{
    /** @var \Symfony\Component\DependencyInjection\ContainerInterface $container */
    protected $container;
    
    /** @var string $routingRoot */
    protected $routingRoot;
    
    /** @var string $routingRoot */
    protected $contentRoot;

    /** @var \PHPCR\SessionInterface $phpcrSession */
    protected $phpcrSession;
        
    /** @var RouteService $routeService */
    private $routeService;

    /**
     * @param ContainerInterface $container A ContainerInterface instance
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->routingRoot = $container->getParameter('symfony_cmf_chain_routing.routing_repositoryroot');
        $this->phpcrSession = $container->get('doctrine_phpcr.default_session');
        $this->routeService = $container->get('netvlies_routing.route_service');
    }

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        //return array(Event::prePersist);
        return array(Event::prePersist);
    }

    /**
     * @param \Doctrine\ODM\PHPCR\Event\LifecycleEventArgs $event
     * @throws \Netvlies\Bundle\RouteBundle\Exception\NullPointerException
     * @todo check if path exists, also check for node replacement, it should be possible to save a route on existing path if existing path is a node
     */
    public function prePersist(LifecycleEventArgs $event)
    {

        $route = $event->getDocument();
        $document = null;
        $dm = $event->getDocumentManager();
        
        if($route instanceof RedirectRouteInterface){
            /**
             * @var RedirectRoute $route
             */
            $this->routeService->validate($route);
            $document = $route->getDocumentTarget();


            $route->setPath($this->routeService->sanitizePath($route->getPath()));

//            if(strpos($route->getName(), '/')!==false){
//                // Given name contains slashes, so create parent path, set explicit path and strip name
//                $parts = explode('/', $route->getName());
//                $route->setName(array_pop($parts));
//                $parts = array_filter($parts);
//                $parentPath = $this->routeService->getRoutingRoot().'/'.implode('/', $parts);
//                $this->createPath($parentPath);
//                $route->setPath($parentPath.'/'.$route->getName());
//            }

            //$path = $this->routeService->getRoutePathForDocument($document, $route);

//            $existingRoute = $dm->find(null, $route->getPath());
//
//            if(!is_null($existingRoute)){
//                $dm->getUnitOfWork()->registerDocument($route, $route->getPath());
//                $dm->refresh($route);
//
//                //$route->setActive(true);
//                //$route->setDocumentTarget($document);
//            }
//            else{
//                $route->setPath($path);
//            }
        } else if ($route instanceof RouteInterface){
            $document = $route->getRouteContent();
        } else {
            return;
        }

        if(empty($document)){
            throw new NullPointerException("Cannot persist RouteInterface object, value of routeContent is empty");
        }

        if($this->phpcrSession->itemExists($route->getPath())){
            // Given path for new route already exists

            /**
             * @var Route $existingRoute
             */
            $existingRoute = $dm->find(null, $route->getPath());

            if($existingRoute->getDefault('primaryRoute') ){
                // Primary route / permalink
                // so create unique nodename for new route
                $dm->detach($existingRoute);
                $route->setPath($this->routeService->getUniquePath($route->getPath()));
            }
            elseif($existingRoute instanceof Route){
                // Default route
                // so create unique nodename for new route
                $dm->detach($existingRoute);
                $route->setPath($this->routeService->getUniquePath($route->getPath()));
            }
            if($existingRoute instanceof RedirectRoute){
                // Additional/Redirect routes
                // Just remove existing node
                //@todo check if this goes ok
                $dm->remove($existingRoute);
            }
        }

        $this->createPath(dirname($route->getPath()));
    }


    /**
     * Create a node and it's parents, if necessary.  Like mkdir -p.
     *
     * @param string $path  full path, like /cms/navigation/main
     * @return Node the (now for sure existing) node at path
     */
    protected function createPath($path)
    {
        $current = $this->phpcrSession->getRootNode();

        $segments = preg_split('#/#', $path, null, PREG_SPLIT_NO_EMPTY);
        foreach ($segments as $segment) {
            if ($current->hasNode($segment)) {
                $current = $current->getNode($segment);
            } else {
                $current = $current->addNode($segment);
            }
        }
        return $current;
    }
}
