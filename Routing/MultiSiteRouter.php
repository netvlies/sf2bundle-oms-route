<?php
/**
 * A router that reads route entries from an Object-Document Mapper store.
 *
 * This is basically using the symfony routing matcher and generator. Different
 * to the default router, the route collection is loaded from the injected
 * route repository custom per request to not load a potentially large number
 * of routes that are known to not match anyways.
 *
 * If the route provides a content, that content is placed in the request
 * object with the CONTENT_KEY for the controller to use.
 *
 * @author Filippo de Santis
 * @author David Buchmann
 * @author Lukas Smith
 * @author Nacho Martìn
 * @author M. de Krijger <mdekrijger@netvlies.nl>
 *
 */
namespace Netvlies\Bundle\RouteBundle\Routing;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Cmf\Bundle\RoutingExtraBundle\Routing\DynamicRouter as BaseDynamicRouter;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;


class MultiSiteRouter extends BaseDynamicRouter implements ContainerAwareInterface
{

    /**
     * {@inheritDoc}
     *
     * Put content into the request attributes instead of the defaults
     */
    public function match($url)
    {
        $currentSite = $this->getCurrentSite();
        $this->routeRepository->setPrefixes($currentSite['prefixes']);

        return parent::match($url);
    }


    public function getCurrentSite()
    {
        $domain = $this->container->get('request')->getHttpHost();

        $sites = $this->container->getParameter('netvlies_routing.multisite');
        $currentSite = null;

        foreach($sites as $site){
            if(in_array($domain, $site['domains'])){
                $currentSite = $site;
                break;
            }
        }

        if(is_null($currentSite)){
            throw new \Exception(sprintf('Domain %s not found in config. Did you forget to add this domain to your multisite config?', $domain));
        }

        return $currentSite;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $name ignored
     * @param array $parameters must either contain the field 'route' with a
     *      RouteObjectInterface or the field 'content' with the document
     *      instance to get the route for (implementing RouteAwareInterface)
     *
     * @throws RouteNotFoundException If there is no such route in the database
     */
    public function generate($name, $parameters = array(), $absolute = false)
    {

        if (isset($parameters['route']) && '' !== $parameters['route']) {
            $route = $parameters['route'];
            unset($parameters['route']);
        } elseif ($name) {
            $route = $this->routeRepository->getRouteByName($name, $parameters);
        } else {
            $route = $this->getRouteFromContent($parameters);
            unset($parameters['route']); // could be an empty string
        }

        if (! $route instanceof RouteObjectInterface) {
            $hint = is_object($route) ? get_class($route) : gettype($route);
            throw new RouteNotFoundException('Route of this document is not an instance of RouteObjectInterface but: '.$hint);
        }

        $currentSite = $this->getCurrentSite();
        $prefixFound = false;

        // First try simple way for current site (which is ok for most cases), so wont check other sites yet
        foreach($currentSite['prefixes'] as $prefix){
            if(!strstr($route->getPath(), $prefix)){
                continue;
            }

            $route->setPrefix($prefix);
            $prefixFound = true;
            break;
        }

        // If not found we can also try other sites, and set absolute to true
        if(!$prefixFound){
            $sites = $this->container->getParameter('netvlies_routing.multisite');
            foreach($sites as $site){
                foreach($site['prefixes'] as $prefix){
                    if(!strstr($route->getPath(), $prefix)){
                        continue;
                    }

                    $route->setPrefix($prefix);
                    $prefixFound = true;
                    break;
                }
                if($prefixFound){
                    break;
                }
            }

            if(!$prefixFound){
                throw new RouteNotFoundException(sprintf('Prefix of this route could not be determined. Did you forget to add the prefix for route %s in config.yml?', $route->getPath()));
            }

            $absolute = true;
            $this->context->setHost($site['domains'][0]);
        }


        $collection = new RouteCollection();
        $collection->add(self::ROUTE_GENERATE_DUMMY_NAME, $route);

        return $this->getGenerator($collection)->generate(self::ROUTE_GENERATE_DUMMY_NAME, $parameters, $absolute);
    }

}
