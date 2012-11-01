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
use Doctrine\Common\EventManager;
use Doctrine\ODM\PHPCR\Event;
use Doctrine\ODM\PHPCR\Event\LifecycleEventArgs;
use Doctrine\ODM\PHPCR\Event\OnClearEventArgs;
use Doctrine\ODM\PHPCR\Event\OnFlushEventArgs;

use Doctrine\ODM\PHPCR\DocumentManager;

use Netvlies\Bundle\RouteBundle\Routing\RouteService;
use Netvlies\Bundle\RouteBundle\Document\RouteAwareInterface;
use Netvlies\Bundle\RouteBundle\Document\Route;
use Netvlies\Bundle\RouteBundle\Document\RedirectRoute;

class RouteAwareSubscriber implements EventSubscriber
{
    /** @var ContainerInterface $container */
    protected $container;

    /** @var string $routingRoot */
    protected $routingRoot;

    /** @var string $routingRoot */
    protected $contentRoot;

    /** @var RouteService $routeService */
    private $routeService;

    /** @var \Metadata\MetadataFactory $metaDataFactory */
    protected $metaDataFactory;

    /** @var RouteSubscriber $routeSubscriber */
    protected $routeSubscriber;

    protected $documentProcessed = array();

    protected $garbage = array();

    /**
     * @var \Netvlies\Bundle\OmsBundle\OmsConfig $omsConfig
     */
    protected $omsConfig;

    /**
     * @param ContainerInterface $container
     * @todo: [DD] only inject the needed dependencies, this is nasty!
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->omsConfig = $container->get('oms_config');

        $this->routingRoot = $this->omsConfig->getRoutingRoot();
        $this->contentRoot = $this->omsConfig->getContentRoot();
        $this->redirectsRoot = $this->omsConfig->getRedirectsRoot();

        $this->routeService = $container->get('netvlies_routing.route_service');
        $this->routeSubscriber = $container->get('netvlies_routing.route_subscriber');
    }

    public function flushGarbage()
    {
        if (empty($this->garbage)) {
            return;
        }

        foreach ($this->garbage as $node => $dm) {
            if ($route = $dm->find(null, $node)) {
                $dm->remove($route);
            }
        }
        $dm->flush();
    }

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(Event::prePersist, Event::preUpdate, Event::preRemove, Event::onFlush);
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
        /* @var $document RouteAwareInterface */
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
        /** @var $document RouteAwareInterface */
        $document = $event->getDocument();
        if (!$document instanceof RouteAwareInterface) {
            return;
        }

        // Little extra check to prevent the good old loop by the cascade flaw in doctrine-phpcr
        $oid = spl_object_hash($document);
        if (isset($this->documentProcessed[$oid])) {
            return;
        }

        $this->documentProcessed[$oid] = true;
        $dm = $event->getDocumentManager();

        $eventManager = new EventManager();
        $eventManager->addEventSubscriber($this->routeSubscriber);
        $subDm = DocumentManager::create($dm->getPhpcrSession(), $dm->getConfiguration(), $eventManager);

        // Make document known in subdocument manager
        //$subDm->getUnitOfWork()->registerDocument($document, $document->getPath());
        //$subDm->getUnitOfWork()->registerDocument($document, $document->getDefaultRoute());

        if ($document->getSwitchRoute() && ($document->getSwitchRoute() != $document->getDefaultRoute()->getPath())) {
            $this->switchRoute($subDm, $document);
        }

        return;

        // If auto route is present it should be updated
        if ($document->getAutoRoute()) {
            $this->handleAutoRoute($dm, $document);
            return;
        }
    }

    protected function handleAutoRoute(DocumentManager $dm, $document)
    {
        /* @var $autoRoute \Netvlies\Bundle\RouteBundle\Document\Route */
        $autoRoute = $document->getAutoRoute();

        $newRoutePath = $this->routeService->createUpdatedRoutePathForDocument($document);

        // only if the new route differs from the default route do we update
        if ($newRoutePath !== $autoRoute->getPath()) {

            $redirects = $autoRoute->getRedirects();

            $oldPath = $autoRoute->getPath();
            $oldDefaults = $autoRoute->getDefaults();
            //var_dump('old'.$oldPath);

            $redirect = new \Netvlies\Bundle\RouteBundle\Document\RedirectRoute();
            $redirect->setDefaults($oldDefaults);
            $redirect->setDefault('autoRoute', false);
            $redirectPath = str_replace($this->routingRoot, $this->redirectsRoot, $oldPath);
            //var_dump('creating '.$redirectPath);
            $redirect->setPath($redirectPath);
            $redirect->setPermanent(true);

            $autoRoute->setDefault('primaryRoute', false);
            //var_dump($autoRoute->getPath());
            //var_dump($newRoutePath);
            $dm->move($autoRoute, $newRoutePath);
            $dm->persist($autoRoute);
            $dm->flush($autoRoute);

            $redirect->setDefaultRouteTarget($autoRoute);

            $dm->persist($redirect);
            $dm->persist($autoRoute);
            $dm->flush($redirect);

//            echo 'pointing default route to'.$document->getDefaultRoute()->getPath();

//          this shouldnt be needed, this should only be needed on route switch default <> redirect
            foreach ($redirects as $redirect) {
                $redirect->setRouteTarget($autoRoute);
                $dm->persist($redirect);
            }
//
//            $dm->flush();
        }
    }

    /**
     * @param \Doctrine\ODM\PHPCR\DocumentManager $dm
     * @param $changedDocument managed by parent DM
     */
    protected function switchRoute(DocumentManager $dm, $changedDocument)
    {

//        $doc = $dm->find(null, '/netvlies/redirects/lindenberg/workshops-voor-bedrijven');
//        var_dump($dm->getReferrers($doc));
//        exit;

        $changedRoute = $changedDocument->getDefaultRoute();
        $changedRedirects = $changedRoute->getRedirects();

        //
        $currentDocument = $dm->find(null, $changedDocument->getPath());
        $currentRoute = $dm->find(null, $changedRoute->getPath());
        $currentRedirectPath = $changedDocument->getSwitchRoute();
        $switchRoute = $dm->find(null, $currentRedirectPath);
        $newRoutePath = str_replace($this->redirectsRoot, $this->routingRoot, $currentRedirectPath);
        $newRedirectPath = str_replace($this->routingRoot, $this->redirectsRoot, $changedRoute->getPath());

        // first lets check if the switchRoute is the primary


        // lets create a new route from the routeSwitch
        $newRoute = new Route();
        $newRoute->setRouteContent($currentDocument);
        $newRoute->setPath($newRoutePath);
        $newRoute->setDefaults($switchRoute->getDefaults());
        $dm->persist($newRoute);

        $currentDocument->setDefaultRoute($newRoute);
        $dm->persist($currentDocument);



        // Then create the redirect for the current path
        $newRedirect = new RedirectRoute();
        $newRedirect->setDefaults($changedRoute->getDefaults());
        $newRedirect->setPath($newRedirectPath);
        $newRedirect->setRouteTarget($newRoute);
        $newRedirect->setPermanent(true);

        $dm->persist($newRedirect);
        $dm->remove($currentRoute);
        $oldRedirectPath = $switchRoute->getPath();
        $dm->remove($switchRoute);

        foreach ($changedRedirects as $redirect) {
            $currentRedirect = $dm->find(null, $redirect->getPath());
            if ($currentRedirect->getPath() == $oldRedirectPath) {
                continue;
            }
            $currentRedirect->setRouteTarget($newRoute);
            $dm->persist($currentRedirect);
        }

        $dm->flush();

//        $this->garbage[(string)$route] = $dm;
//        if ($switchRoute) {
//            $this->garbage[(string)$switchRoute] = $dm;
//        }
    }

    public function onFlush(OnFlushEventArgs $event)
    {
        $this->flushGarbage();
    }
}
