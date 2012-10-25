<?php

namespace Netvlies\Bundle\RouteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Netvlies\Bundle\RouteBundle\Document\RedirectRoute;
use Netvlies\Bundle\RouteBundle\Document\Route as OmsRoute;

/**
 *
 * @todo currently disabled this controller deliberately. We dont want to expose our route collections
 * besides the findall method in here will result in many, many route objects to be generated, which is a performance killer
 * so we shouldnt do this anyway
 *
 * @ Route("/route")
 */
class RouteController extends Controller
{
    /**
     * @ Route("/list")
     * @Template()
     */
    public function listAction()
    {
        $routes = $this->getRoutesFlat();

        return $this->createJsonResponse(array(
            'routes' => $routes,
        ));
    }

    /**
     * Returns a flat list of all existing routes.
     *
     * @return array
     */
    public function getRoutesFlat()
    {
        $routes = array();

        $dm = $this->get('doctrine_phpcr.odm.document_manager');
        $repo = $dm->getRepository('NetvliesOmsBundle:Route');

        $items = $repo->findAll();

        foreach ($items as $item) {
            $routes[] = array(
                'id' => $item->getPath(),
                'label' => $item->getName(),
            );
        }

        return $routes;
    }

    /**
     * Retrieves the routes recursively starting from the root. This is another approach that does not seem to work
     * completely as different types of elements are returned (not only routes) which require different handling. Also
     * the function does not seem to go in depth and memory issues were encountered when increasing depth.
     *
     * @todo This function was the initial set up, but if the getRoutesFlat is sufficient, this function can be removed.
     *
     * @return array
     */
    public function getRoutesFromRoot()
    {
        $dm = $this->get('doctrine_phpcr.odm.document_manager');
        $routingRoot = $this->container->getParameter('symfony_cmf_chain_routing.routing_repositoryroot');

        $node = $dm->find(null, $routingRoot);

        $routes = $this->getChildren($node, 2);

        return $routes;
    }

    /**
     * Returns the child routes of a node up to the given maximum depth.
     *
     * @param $node
     * @param int $maxDepth
     * @return array
     */
    public function getChildren($node, $maxDepth = 100)
    {
        $routes = array();

        if ($maxDepth == 0) {
            return $routes;
        }

        $dm = $this->get('doctrine_phpcr.odm.document_manager');

        $children = $dm->getChildren($node);

        foreach ($children as $child) {
            if ($child instanceof OmsRoute) {
                $routes[] = array(
                    'id' => $child->getPath(),
                    'label' => $child->getName(),
                    'children' => $this->getChildren($child, $maxDepth - 1),
                );
            }
        }

        return $routes;
    }

    /**
     * @param array $response
     * @return Response
     */
    protected function createJsonResponse(array $response)
    {
        $serializer = $this->get('serializer');
        $jsonResponse = $serializer->serialize($response, 'json');

        return new Response($jsonResponse, 200, array('Content-Type' => 'application/json'));
    }
}