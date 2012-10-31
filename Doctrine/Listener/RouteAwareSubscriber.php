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
use Doctrine\ODM\PHPCR\Event\OnClearEventArgs;

use Doctrine\ODM\PHPCR\DocumentManager;

use Netvlies\Bundle\RouteBundle\Routing\RouteService;
use Netvlies\Bundle\RouteBundle\Document\RouteAwareInterface;
use Netvlies\Bundle\RouteBundle\Document\Route;

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
     * @param ContainerInterface $container
     * @todo: [DD] only inject the needed dependencies, this is nasty!
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->routingRoot = $container->getParameter('symfony_cmf_chain_routing.routing_repositoryroot');
        $this->contentRoot = $container->getParameter('symfony_cmf_content.static_basepath');
        $this->routeService = $container->get('netvlies_routing.route_service');
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

        if ($document->getSwitchRoute() && ($document->getSwitchRoute() != $document->getDefaultRoute()->getPath())) {
            $this->switchRoute($dm, $document);
            return;
        }

        // if a new autoRoute should be created and set default (if title updated, etc.)
        if ($document->getDefaultRoute()->getDefault('autoRoute')) {
            $this->handleAutoRoute($dm, $document);
            return;
        }
    }

    protected function handleAutoRoute(DocumentManager $dm, $document)
    {
        /* @var $route \Netvlies\Bundle\RouteBundle\Document\Route */
        $route = $document->getDefaultRoute();

        /* @var $newRoute \Netvlies\Bundle\RouteBundle\Document\Route */
        // @todo we only need a valid new autocreated path!
        $newRoute = $this->routeService->createUpdatedRouteForDocument($document);

        // only if the new route differs from the default route do we update
        if ($newRoute->getPath() !== $route->getPath()) {

            $redirects = $route->getRedirects();

            $redirect = new \Netvlies\Bundle\RouteBundle\Document\RedirectRoute();
            $redirect->setDefaults($route->getDefaults());
            $redirect->setPath(str_replace($this->routingRoot, '/netvlies/redirects', $route->getPath()));
            $redirect->setPermanent(true);

            $dm->move($route, $newRoute->getPath());
            $dm->persist($route);
            $dm->flush($route);

            $redirect->setRouteTarget($route);
            $dm->persist($redirect);

            foreach ($redirects as $redirect) {
                $redirect->setRouteTarget($route);
                $dm->persist($redirect);
            }
        }
    }

    /**
     * @todo get redirects basePath
     * @param \Doctrine\ODM\PHPCR\DocumentManager $dm
     * @param                                     $document
     */
    protected function switchRoute(DocumentManager $dm, $document)
    {
        $route = $document->getDefaultRoute();
        $switch = $document->getSwitchRoute();
        $switchRoute = $dm->find(null, $switch);

        // first lets check if the switchRoute is the primary

        // lets create a new route from the routeSwitch
        $newRoute = new Route();
        $newRoute->setRouteContent($route->getRouteContent());
        $newRoute->setPath(str_replace('/netvlies/redirects', $this->routingRoot, $switch));
        $newRoute->setDefaults($switchRoute ? $switchRoute->getDefaults() : array());
        $dm->persist($newRoute);
        $dm->flush($newRoute);
        $document->setDefaultRoute($newRoute);

        foreach ($route->getRedirects() as $redirect) {
            if ($redirect == $switchRoute) {
                continue;
            }
            $redirect->setRouteTarget($newRoute);
            $dm->persist($redirect);
            $dm->flush($redirect);
        }

        // create the redirect for the current path
        $newRedirect = new \Netvlies\Bundle\RouteBundle\Document\RedirectRoute();
        $newRedirect->setDefaults($route->getDefaults());
        $newRedirect->setPath(str_replace($this->routingRoot, '/netvlies/redirects', $route->getPath()));
        $newRedirect->setRouteTarget($newRoute);
        $newRedirect->setPermanent(true);
        $dm->persist($newRedirect);
        $dm->flush($newRedirect);

        $this->garbage[(string)$route] = $dm;
        if ($switchRoute) {
            $this->garbage[(string)$switchRoute] = $dm;
        }
    }
}
