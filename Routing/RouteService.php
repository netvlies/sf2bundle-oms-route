<?php
/*
 * (c) Netvlies Internetdiensten
 *
 * Sjoerd Peters <speters@netvlies.net>
 * 11-9-12
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Netvlies\Bundle\RouteBundle\Routing;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Validator;

use PHPCR\PathNotFoundException;
use Gedmo\Sluggable\Util\Urlizer;

use Netvlies\Bundle\RouteBundle\Exception\ValidationException;
use Netvlies\Bundle\RouteBundle\Document\RouteAwareInterface;
use Netvlies\Bundle\RouteBundle\Document\Route;
use Netvlies\Bundle\RouteBundle\Document\RouteInterface;
use Netvlies\Bundle\RouteBundle\Document\RedirectRoute;
use Netvlies\Bundle\RouteBundle\Document\RedirectRouteInterface;
use Normalizer;

class RouteService
{
    /** @var ContainerInterface $container */
    protected $container;

    /** @var \PHPCR\SessionInterface $phpcrSession */
    protected $phpcrSession;

    /** @var Validator $validator */
    protected $validator;

    /** @var \Metadata\MetadataFactory $metaDataFactory */
    protected $metaDataFactory;

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->phpcrSession = $container->get('doctrine_phpcr.default_session');
        $this->validator = $container->get('validator');
        $this->metaDataFactory = $container->get('netvlies_routing.metadata_factory');
    }

    //@todo this isnt used anymore, but keep it commented for now, for historical/refactoring/log reasons
//    public function addDocumentRedirect($dm, $uri, $target, $permanent = true)
//    {
//        $path = '/netvlies/redirects/' . $uri;
//        $redirect = new RedirectRoute();
//        $redirect->setDocumentTarget($target);
//        $redirect->setPath($path);
//        $dm->persist($redirect);
//    }

    /**
     * @param \Netvlies\Bundle\RouteBundle\Document\RouteAwareInterface $document
     */
    public function createRouteForDocument(RouteAwareInterface $document)
    {
        /**
         * @var \Netvlies\Bundle\RouteBundle\Mapping\RouteClassMetadata $metaData
         */
        $metaData = $this->metaDataFactory->getMetadataForClass(get_class($document));
        $name = $this->parseRouteName($metaData->routeName, $document);
        $basePath = $this->parseRoutePath($this->getRoutingRoot().'/'.$metaData->basePath, $document);

        $route = new Route();
        $route->setPath($basePath.'/'.$name);
        $route->setRouteContent($document);

        return $route;
    }

    public function createUpdatedRoutePathForDocument(RouteAwareInterface $document)
    {
        /**
         * @var \Netvlies\Bundle\RouteBundle\Mapping\RouteClassMetadata $metaData
         */
        $metaData = $this->metaDataFactory->getMetadataForClass(get_class($document));

        $autoRoute = $document->getAutoRoute();
        $basePath = dirname($autoRoute->getPath());
        $name = basename($autoRoute->getPath());

        if($document->getDefaultRoute() === $document->getAutoRoute()){
            $routeRoot = $this->getRoutingRoot();
        }
        else{
            $routeRoot = $this->getRedirectRoot();
        }

        if($metaData->updateBasePath){
            $basePath = $this->parseRoutePath($routeRoot.'/'.$metaData->basePath, $document);
        }

        if($metaData->updateRouteName){
            $name = $this->parseRouteName($metaData->routeName, $document);
        }

        return $basePath.'/'.$name;
    }

    /**
     * Creates a path  to guarantee an unique entry point (permalink)
     * used to avoid conflicts when inserting a new PHPCR node.
     * @todo move this into oms bundle
     *
     * @param string path
     * @return string
     */
    public function getUniquePath($path)
    {
        $number = 1;
        $newPath = $path;

        while ($this->phpcrSession->nodeExists($newPath)) {
            $newPath = $path . '-' . $number++;
        }

        return $newPath;
    }

    /**
     * This method is only used for NEW documents
     *
     * @param RouteAccessInterface $document
     * @todo hmmmm better to just have 'getDefaultContentPathForDocument' and set it if wanted
     * @todo refactor this, shouldnt be here, but in omsbundle and using annotations for this
     * @return string
     */
    public function setPathToDocument(RouteAwareInterface $document)
    {
        $this->validate($document);
        $currentPath = $document->getPath();

        if(!empty($currentPath)){
            $currentPath = $this->validatePath($currentPath);
            $name = $this->createUniqueNodeName($currentPath);
            $document->setPath(dirname($currentPath).'/'.$name);
            return $document;
        }

        $path = $this->parseRoutePath($this->getContentRoot().'/'.$document->getContentBasePath().'/'.$document->getTitle());
        $path = $this->getUniquePath($path);

        $document->setPath($path);

    }

    /**
     * @param object $document
     * @todo we should have a better way of handling the errors, otherwise we dont know where to find the actual error
     * @throws ValidationException
     */
    public function validate($document)
    {
        $errors = $this->validator->validate($document);
        if (count($errors) > 0) {
            throw new ValidationException(sprintf("Invalid instance of class %s, %d constraint violations raised.", get_class($document), count($errors)));
        }
    }

    /**
     * @param RouteAwareInterface $document
     * @param string $pattern
     * @return string
     */
    public function parseRouteName($name, $document=null)
    {
        if(!is_null($document)){
            $name = $this->compileRouteVars($document, $name);
        }

        $name = $this->sanitizePath($name);
        return str_replace('/', '-', $name);
    }


    /**
     * @param string $path
     * @return string
     */
    public function parseRoutePath($path, $document=null)
    {
        if(!is_null($document)){
            $path = $this->compileRouteVars($document, $path);
        }

        // Ensure path specific characteristics
        $pattern = array(
            '/\/\/+/',          // make single / slashes from double // slashes
            '/(\/\s|\s\/)/',    // clear whitespace next to slashes
            '/^\/?(.*)/',       // add leading / slash
            '/(.*)\/$/',        // remove trailing / slash
        );
        $replacement = array(
            '/',
            '/',
            '/\1',
            '\1',
        );

        $path = preg_replace($pattern, $replacement, $path);

        return $this->sanitizePath($path);
    }


    /**
     * Pattern compiler to use dynamicly generated routes
     * E.g.
     * A pattern like "[title]-[name]" will be compiled using the getters on document $doc->getTitle(), $doc->getName()
     * characters outside blockquotes will be left alone.
     *
     * We do 2 compilation passes, this way we give flexibility to have the user set his own patterns instead of the annotation
     * e.g. in annotation we use [pattern], which will call getPattern wich can return something like [title]-[name]*
     *
     * @param $document
     * @param $pattern
     * @return string
     * @throws \Exception
     */
    protected function compileRouteVars($document, $pattern)
    {
        for($j=0;$j<2;$j++){
            preg_match_all('/(?<=\[).*?(?=\])/s', $pattern, $matches);
            for ($i = 0; $i < count($matches[0]); $i++) {
                $getter = "get".ucfirst($matches[0][$i]);
                if(method_exists($document, $getter)){
                    $value = call_user_func(array($document, $getter));
                    $pattern = str_replace("[".$matches[0][$i]."]", $value, $pattern);
                }
                else{
                    throw new \Exception(sprintf('Method %s does not exist for class %s while generating route name. Please check your annotation', $getter, get_class($document)));
                }
            }
        }
        return $pattern;
    }

    /**
     * @param string $path
     */
    public function sanitizePath($path)
    {
        // Replace special chars ë into e etc, and make lowercase
        $path = preg_replace('~&([a-z]{1,2})(acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i', '$1', htmlentities($path, ENT_QUOTES, 'UTF-8'));
        $path = html_entity_decode($path, ENT_QUOTES, 'UTF-8');

        // Make lower case, because uppercase chars have no special meaning in url
        // And make spaces and underscores into dashes
        $path = strtolower($path);
        $path = str_replace(' ', '-', $path);
        $path = str_replace('_', '-', $path);

        // White listing valid chars
        $path = preg_replace('/[^a-z0-9\-\/]/', '', $path);

        // Replace optional double dashes
        $path = preg_replace('/[-]+/', '-', $path);
        return $path;
    }

    /**
     * @return mixed
     */
    public function getRoutingRoot()
    {
        $omsConfig = $this->container->get('oms_config');
        return $omsConfig->getRoutingRoot();
    }


    public function getRedirectRoot()
    {
        $omsConfig = $this->container->get('oms_config');
        return $omsConfig->getRedirectsRoot();
    }

    /**
     * @return mixed
     */
    public function getContentRoot()
    {
        $omsConfig = $this->container->get('oms_config');
        return $omsConfig->getContentRoot();
    }

}
