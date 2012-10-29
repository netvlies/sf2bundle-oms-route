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

use Doctrine\Common\EventManager;
use Doctrine\Common\EventSubscriber;
use Doctrine\ODM\PHPCR\Event;
use Doctrine\ODM\PHPCR\Event\LifecycleEventArgs;
use Doctrine\ODM\PHPCR\DocumentManager;

use Netvlies\Bundle\RouteBundle\Routing\RouteService;
use Netvlies\Bundle\RouteBundle\Document\RouteAwareInterface;
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

    /** @var RouteSubscriber $routeSubscriber */
    protected $routeSubscriber;

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
        $this->routeSubscriber = $container->get('netvlies_routing.route_subscriber');
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

        $primaryRoute = $document->getPrimaryRoute();
        $defaultRoute = $document->getDefaultRoute();

        // If default route is empty (no route given from custom code)
        // we generate an auto route and copy it into primary route as well
        // We dont handle collisions when routes are set manually
        if(empty($defaultRoute)){
            // Create one
            $defaultRoute = $this->routeService->createRouteForDocument($document);
            $defaultRoute->setPath($this->routeService->getUniquePath($defaultRoute->getPath()));
            $document->setDefaultRoute($defaultRoute);
            $document->setAutoRoute($defaultRoute);
        }

        // In case no default route is not explicitly given, we copy primary route to defaultroute
        if(empty($primaryRoute)){
            $document->setPrimaryRoute($defaultRoute);
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

        // @todo currently we use a sub document manager to persist and change routes along the update for current routeAware document
        // For this we create a documentmanager with custom event manager (only route subscriber included)
        $dm = $event->getDocumentManager();
        $eventManager = new EventManager();
        $eventManager->addEventSubscriber($this->routeSubscriber);
        $subDm = DocumentManager::create($this->phpcrSession, $dm->getConfiguration(), $eventManager);

        // Same as $document, but managed by sub document manager
        $originalDocument = $subDm->find(null, $dm->getUnitOfWork()->getDocumentId($document));

        // The routes that are changed/updated by user (connected to $document)
        $primaryRoute = $document->getPrimaryRoute();
        $defaultRoute = $document->getDefaultRoute();
        $autoRoute = $document->getAutoRoute();

        if(empty($primaryRoute)){
            throw new \Exception('Primary route must be set');
        }

        if(empty( $defaultRoute)){
            throw new \Exception('Default route must be set');
        }


        if(!empty($autoRoute)){
            //@todo we only use path from newRoute, so maybe better method, because eventually must be a Route OR RedirectRoute
            $newRoute = $this->routeService->createUpdatedRouteForDocument($document);

            // If auto route has changed
            if($newRoute->getPath() != $autoRoute->getPath()){

                // We dont want to change primary route, so store auto route as new Route or RedirectRoute
                if($autoRoute === $primaryRoute){

                    if($autoRoute === $defaultRoute){

                        $newPath = $newRoute->getPath();

                        // Save new auto route on new location
                        $autoRoute = new Route();
                        $autoRoute->setPath($newPath);
                        $autoRoute->setRouteContent($originalDocument);

                        $originalDocument->setDefaultRoute($autoRoute);
                        $originalDocument->setAutoRoute($autoRoute);

                        $subDm->persist($autoRoute);
                        $subDm->flush($autoRoute);

                        // Refind primary route managed in sub document manager
                        $primaryRoute = $subDm->find(null, $primaryRoute->getPath());
                        $primaryRoutePath = $primaryRoute->getPath();

                        // Remove it (Route)
                        $subDm->remove($primaryRoute);
                        $subDm->flush();

                        // And recreate it (RedirectRoute)
                        $redirect = new RedirectRoute();
                        $redirect->setPath($primaryRoutePath);
                        $redirect->setRouteTarget($autoRoute);

                        $originalDocument->addRedirects($redirect);
                        $originalDocument->setPrimaryRoute($redirect);

                        // And recreate
                        $subDm->persist($redirect);
                        $subDm->flush();
                    }
                    else{
                        // Just create a new autoroute and save it
                        $autoRoute = new RedirectRoute();
                        $autoRoute->setPath($newRoute->getPath());
                        $autoRoute->setRouteTarget($defaultRoute);

                        $originalDocument->addRedirects($autoRoute);
                        $originalDocument->setAutoRoute($autoRoute);

                        $subDm->persist($autoRoute);
                        $subDm->flush($autoRoute);
                    }
                }
                else{
                    // move auto route to new path (doesnt matter which type (default or redirect))
                    $this->phpcrSession->itemExists($newRoute->getPath());

                    $existingRoute = $dm->find(null, $newRoute->getPath());
                    if($existingRoute->getDefault('primaryRoute') || get_class($existingRoute) == 'Netvlies\Bundle\RouteBundle\Document\Route'){
                        $newRoute->setPath($this->routeService->getUniquePath($newRoute->getPath()));
                    }
                    else{
                        $this->phpcrSession->removeItem($newRoute->getPath);
                    }

                    $this->phpcrSession->move($autoRoute->getPath(), $newRoute->getPath());
                }

            }
        }


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
