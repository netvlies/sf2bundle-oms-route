<?php
/**
 * (c) Netvlies Internetdiensten
 *
 * @author M. de Krijger <mdekrijger@netvlies.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netvlies\Bundle\RouteBundle\Routing;

use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Netvlies\Bundle\RouteBundle\Route\RouteInterface;

class MultiSiteDocumentMatcher
{

    /**
     * To get the request from, as its not available immediatly
     * @var ContainerInterface
     */
    protected $container;

    public function setContainer($container = null)
    {
        $this->container = $container;
    }

    /**
     * @todo this is a temporary thing
     * It shouldnt be needed once we let every route in the site be controlled by CMF
     * also routes that are explicitly defined in controllers should be managed by CMF routing
     *
     * We should do that in such way that a new route is created when no matching route is found
     * then the corresponding controller must be found and set to this route
     * this can be quite buggy when controller name and action changes, also when developer changes the route in annotation,
     * the previous route will still be working
     */
    public function getRouteContentDocument()
    {
        $currentSite = $this->getCurrentSite();
        $url = $this->container->get('request')->getPathInfo();
        $dm = $this->container->get('doctrine_phpcr.odm.document_manager');
        $document = null;

        if($url=='/'){
            $url = '/home';
        }

        foreach($currentSite['prefixes'] as $prefix){

            $routePath = $prefix.$url;
            $route = $dm->find(null, $routePath);
            if(empty($route) || !($route instanceof RouteInterface)){
                continue;
            }

            $document = $route->getRouteContent();
            break;
        }

        if(empty($document)){
            throw new \Exception(sprintf('Couldnt find matching document for route %s', $url));
        }


        return $document;
    }

    protected function getCurrentSite()
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

}
