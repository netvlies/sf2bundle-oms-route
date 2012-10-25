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
use Symfony\Component\Validator\Validator;

use Doctrine\Common\EventSubscriber;
use Doctrine\ODM\PHPCR\Event;
use Doctrine\ODM\PHPCR\Event\LifecycleEventArgs;

use Netvlies\Bundle\RouteBundle\Document\RouteAwareInterface;
use Netvlies\Bundle\RouteBundle\Routing\RouteService;
use Netvlies\Bundle\RouteBundle\Document\Route;
use Netvlies\Bundle\RouteBundle\Document\RouteInterface;
use Netvlies\Bundle\RouteBundle\Document\RedirectRouteInterface;
use Netvlies\Bundle\RouteBundle\Document\RedirectRoute;

use PHPCR\PathNotFoundException;
use PHPCR\NodeInterface;
use Gedmo\Sluggable\Util\Urlizer;

/**
 * @todo where is the primary route checking/validation?
 * @todo should we run a createpath on a new route when assigned manually?
 */
class RouteAwareSubscriber implements EventSubscriber
{
    /** @var ContainerInterface $container */
    protected $container;

    /** @var \Doctrine\ODM\PHPCR\DocumentManager $dm */
    protected $dm;

    /** @var string $routingRoot */
    protected $routingRoot;

    /** @var string $routingRoot */
    protected $contentRoot;

    /** @var \PHPCR\SessionInterface $phpcrSession */
    protected $phpcrSession;

    /** @var RouteService $routeService */
    private $routeService;

    /** @var string previousDefaultPath */
    private $previousDefaultPath;

    /** @var string $switchDefaultToPrimary */
    private $switchDefaultToPrimary;

    /** @var \Metadata\MetadataFactory $metaDataFactory */
    protected $metaDataFactory;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->routingRoot = $container->getParameter('symfony_cmf_chain_routing.routing_repositoryroot');
        $this->contentRoot = $container->getParameter('symfony_cmf_content.static_basepath');
        $this->phpcrSession = $container->get('doctrine_phpcr.default_session');
        $this->routeService = $container->get('netvlies_routing.route_service');
        $this->metaDataFactory = $container->get('netvlies_routing.metadata_factory');
    }

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(Event::prePersist, Event::preUpdate, Event::preRemove);
    }

    /**
     * @param \Doctrine\ODM\PHPCR\Event\LifecycleEventArgs $event
     * @throws \RuntimeException
     */
    public function prePersist(LifecycleEventArgs $event)
    {

        /** @var \Netvlies\Bundle\RouteBundle\Document\RouteAwareInterface $document */
        $document = $event->getDocument();
        if (!$document instanceof RouteAwareInterface) {
            return;
        }



        //@todo this should be moved into oms bundle
//        $this->routeService->setPathToDocument($document);
//        $this->createPath(dirname($document->getPath()));

        $primaryRoute = $document->getPrimaryRoute();
        $defaultRoute = $document->getDefaultRoute();

        // We dont know if user has set default route or primary route
        // In case of default route, we copy it to primary route if empty
        if(empty($primaryRoute) && !empty($defaultRoute)){
            $primaryRoute = $defaultRoute;
            $document->setPrimaryRoute($primaryRoute);
        }

        // If primary route is still empty (no route given from custom code)
        // we generate an auto route and copy it into default route as well
        if(empty($primaryRoute)){
            // create one
            $primaryRoute = $this->routeService->createRouteForDocument($document);
            $document->setPrimaryRoute($primaryRoute);
        }

        // In case no default route is not explicitly given, we copy primary route to defaultroute
        if(empty($defaultRoute)){
            $document->setDefaultRoute($primaryRoute);
        }

        // Be sure that primary route is marked as primary route, so it can be recognized later on
        // This is the permalink
        $primaryRoute->setDefault('primaryRoute', true);

        // check existing routes
        $routes = $document->getRoutes();
        $primaryRouteFound = 0;

        foreach($routes as $route){
            /**
             * @var Route $route
             */
            if($route->getDefault('primaryRoute')){
                $primaryRouteFound++;
            }
        }

        if($primaryRouteFound != 1){
            throw new \Exception(sprintf('There must be one primary route. Found %i primary routes', $primaryRouteFound));
        }
    }

    /**
     * @param \Doctrine\ODM\PHPCR\Event\LifecycleEventArgs $event
     */
    public function preRemove(LifecycleEventArgs $event)
    {
        /** @var RouteAccessInterface $document */
        $document = $event->getDocument();
        if (!$document instanceof RouteAwareInterface) {
            return;
        }

        foreach ($document->getRoutes() as $route) {
            $event->getDocumentManager()->remove($route);
        }
    }


    /**
     * @param \Doctrine\ODM\PHPCR\Event\LifecycleEventArgs $event
     * @throws \Exception
     */
    public function preUpdate(LifecycleEventArgs $event)
    {
        /** @var RouteAwareInterface $document */
        $document = $event->getDocument();
        if (!$document instanceof RouteAwareInterface) {
            return;
        }

        $dm = $event->getDocumentManager();
        $primaryRoute = $document->getPrimaryRoute();
        $defaultRoute = $document->getDefaultRoute();

        if(empty($primaryRoute) || empty($defaultRoute)){
            throw new \Exception('Primary and or default route must be set');
        }

        $newRoute = $this->routeService->createUpdatedRouteForDocument($document);

        //@todo What to do when switching default route with existing redirect route?
        // I guess it shouldnt update auto names, the user explicitly pointed a redirect
        // So we have to set an auto flag on a route (autoroute)
        if($newRoute->getPath() != $document->getDefaultRoute()->getPath()){

            //@todo we should always persist the newroute, and not move it because it can also be the primary route, which must stay intact
            // Move default route to new location
            $oldPath = $document->getDefaultRoute()->getPath();
            $this->phpcrSession->move($oldPath, $newRoute->getPath());

            // Create 302 for previous default route
            $redirectRoute = new RedirectRoute();
            $redirectRoute->setPath($oldPath);
            $redirectRoute->setRouteTarget($defaultRoute);
            $document->addRedirects($redirectRoute);
        }

        // Make sure that default route is instance of Route
        if(!($document->getDefaultRoute() instanceof Route)){
            // convert document
            /**
             * @var RedirectRoute $currentRoute
             */
            $currentRoute = $document->getDefaultRoute();
            $defaultRoute = new Route();

            $defaultRoute->setPath($currentRoute->getPath());
            $defaultRoute->setRouteContent($currentRoute->getRouteContent());
            $document->setDefaultRoute($defaultRoute);
        }

        // Other routes must be instance of RedirectRoute
        $redirects = array();
        foreach($document->getRoutes() as $route){
            if($route == $document->getDefaultRoute()){
                continue;
            }

            if(!($document->getDefaultRoute() instanceof RedirectRoute)){
                // convert document
                /**
                 * @var RedirectRoute $currentRoute
                 */
                $redirectRoute = new RedirectRoute();
                $redirectRoute->setPath($route->getPath());
                $redirectRoute->setRouteTarget($document->getDefaultRoute());
                $route = $redirectRoute;
            }

            $redirects[] = $route;
        }

        $document->setRedirects($redirects);


        // Validation (max 1 primary route) and name collisions
        $routes = $document->getRoutes();
        $primaryRouteFound = 0;


        foreach($routes as $route){
            /**
             * @var Route $route
             */
            if($route->getDefault('primaryRoute')){
                $primaryRouteFound++;
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
        }

        if($primaryRouteFound != 1){
            throw new \Exception(sprintf('There must be one primary route. Found %i primary routes', $primaryRouteFound));
        }

    }





//    /**
//     * @param \Doctrine\ODM\PHPCR\Event\LifecycleEventArgs $event
//     * @throws \Exception
//     */
//    public function postUpdate(LifecycleEventArgs $event)
//    {
//        /** @var RouteAccessInterface $document */
//        $document = $event->getDocument();
//        if (!$document instanceof RouteAccessInterface) {
//            return;
//        }
//
//        $this->dm = $event->getDocumentManager();
//        if (!is_null($this->previousDefaultPath)) {
//            $this->addRedirectsForPreviousRoutes($document);
//            $this->previousDefaultPath = null;
//        }
//    }


//    /**
//     * Default route can be changed afterwards, it is the route that is default used when generating a menu-item
//     * or that should be used when generating a link
//     *
//     * @param \Netvlies\Bundle\RouteBundle\Route\RouteAccessInterface $document
//     * @return \Netvlies\Bundle\RouteBundle\Route\RouteAccessInterface
//     */
//    protected function validateDefaultRoute(RouteAccessInterface $document)
//    {
//        $defaultRoute = $document->getDefaultRoute();
//        $primaryRoute = $document->getPrimaryRoute();
//        $switchPath = $document->getDefaultRouteSwitch();
//
//        if (is_null($defaultRoute)) {
//            $defaultRoute = $this->routeService->createRouteForDocument($document);
//        } else if (!empty($switchPath)) {
//            // @todo @fixme Sjopet??
////            $defaultPath = $defaultRoute->getPath();
////            $switch = $this->getDocumentSwitchRoute($document, $switchPath);
////
////            $this->dm->remove($switch);
////            $this->dm->move($defaultRoute, $switchPath);
////            $this->previousDefaultPath = $defaultPath;
////            $document->setDefaultRouteSwitch(null);
////            return $document;
//        }
//
//        // @todo document may be added manually instead of automatically generated by RouteAccessSubscriber
//        // Somehow in here this will trigger a cascade persist when assigning a route to current document
//        // outside this class this can be fixed by persisting the route before persisting the document
//        // Not sure if this must be fixed or not
//
//        // We want to update route paths when a name change occurred. So we generate a new route name as it if were a new document
//        // We only check name changes for route. Parent path stays the same.
//        $defaultPath = $defaultRoute->getPath();
//        $routeName = $this->routeService->parseRouteName($document);
//        $routePath = dirname($defaultPath) . '/' . $routeName;
//
//        if ($routePath != $defaultPath) {
//            // changes to the document resulted in the need to change the defaultRoute
//            if($this->nodeExists($routePath)){
//                foreach ($document->getRoutes() as $route) {
//                    // Remove optional existing redirect route to the same document
//                    // This should become a normal route, no redirect
//                    if($routePath == $route->getPath()){
//                        if($route instanceof RedirectRoute){
//                            $this->phpcrSession->removeItem($routePath);
//                            break;
//                        }
//                    }
//                }
//            }
//            $this->previousDefaultPath = $defaultPath;
//            //@todo this contains a flaw. If  there is an existing route/redirectroute connected on another document the it will fail when moving
//            $this->dm->move($defaultRoute, $routePath);
//        }
//
//        if(empty($primaryRoute)){
//            // if the primary is empty (insert) both default and primary get the same path
//            $document->setPrimaryRoute($defaultRoute);
//        }
//
//        $document->setDefaultRoute($defaultRoute);
//        return $document;
//    }

//    protected function addRedirectsForPreviousRoutes(RouteAccessInterface $document)
//    {
//        $defaultRoute = $document->getDefaultRoute();
//        $primaryRoute = $document->getPrimaryRoute();
//
//        $redirect = new RedirectRoute();
//        $redirect->setDocumentTarget($document);
//        $redirect->setRouteTarget($defaultRoute);
//        $redirect->setPath($this->previousDefaultPath);
//        $redirect->setName(basename($this->previousDefaultPath));
//
//        /*
//         * if the primary route is not a redirect it was the default so we can be
//         * sure this new redirect points to the first ever route eg. the primary.
//         */
//        if (!$primaryRoute instanceof RedirectRouteInterface) {
//            $document->setPrimaryRoute($redirect);
//        }
//        $document->addRedirects($redirect);
//        $this->dm->persist($document);
//        $this->phpcrSession->save();
//    }

//    /**
//     * Creates a unique node name to guarantee an unique entry point (permalink)
//     * and to avoid conflicts when inserting a new PHPCR node.
//     *
//     * @param \Netvlies\Bundle\RouteBundle\Route\RouteAccessInterface $document
//     * @return string
//     */
//    protected function createUniqueNodeName(RouteAccessInterface $document)
//    {
//        $number = 1;
//        $nodeName = $baseName = Urlizer::urlize($document->getTitle());
//        while ($this->nodeNameExists($nodeName, $document->getContentBasePath())) {
//            $nodeName = $baseName . '-' . $number++;
//        }
//        return $nodeName;
//    }

//    /**
//     * @param string $path
//     * @return bool
//     */
//    protected function nodeExists($path)
//    {
//        try {
//            $node = $this->phpcrSession->getNode($path);
//            return true;
//        } catch (PathNotFoundException $exception) {
//            return false;
//        }
//    }

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

//    /**
//     * @param \Netvlies\Bundle\RouteBundle\Route\RouteAccessInterface $document
//     * @param string $switchPath
//     * @param string $defaultPath
//     * @return \Netvlies\Bundle\RouteBundle\Document\Route
//     */
//    protected function switchDefaultRouteWith(RouteAccessInterface $document, $defaultPath, $switchPath)
//    {
//        $redirect = new RedirectRoute();
//        $redirect->setDocumentTarget($document);
//        $redirect->setPath($defaultPath);
//        $redirect->setName(basename($defaultPath));
//
//        $document->addRedirects($redirect);
//
//        $route = new Route();
//        $route->setRouteContent($document);
//        $route->setPath($switchPath);
//        return $route;
//    }

//    /**
//     * @param \Netvlies\Bundle\RouteBundle\Route\RouteAccessInterface $document
//     * @param $switchPath
//     * @return \Netvlies\Bundle\RouteBundle\Route\RedirectRouteInterface|null
//     */
//    protected function getDocumentSwitchRoute(RouteAccessInterface $document, $switchPath)
//    {
//        foreach ($document->getRedirects() as $redirect) {
//            if($switchPath == $redirect->getPath()){
//                return $redirect;
//            }
//        }
//        return null;
//    }

//    protected function updateRedirectTargets(RouteAccessInterface $document)
//    {
//        foreach ($document->getRoutes() as $route) {
//            if($route instanceof RedirectRoute){
//                $route->setRouteTarget($document->getDefaultRoute());
//            }
//        }
//    }


}
