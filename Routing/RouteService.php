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


    public function createUpdatedRouteForDocument(RouteAwareInterface $document)
    {
        /**
         * @var \Netvlies\Bundle\RouteBundle\Mapping\RouteClassMetadata $metaData
         */
        $metaData = $this->metaDataFactory->getMetadataForClass(get_class($document));

        $defaultRoute = $document->getDefaultRoute();
        $basePath = dirname($defaultRoute->getPath());
        $name = basename($defaultRoute->getPath());

        if($metaData->updateBasePath){
            $basePath = $this->parseRoutePath($this->getRoutingRoot().'/'.$metaData->basePath, $document);
        }

        if($metaData->updateRouteName){
            $name = $this->parseRouteName($metaData->routeName, $document);
        }

        $route = new Route();
        $route->setPath($basePath.'/'.$name);
        $route->setRouteContent($document);

        return $route;
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









//    /**
//     * @param RouteAccessInterface $document
//     * @return string
//     * @todo move this to oms
//     */
//    public function getContentPathForDocument(RouteAccessInterface $document)
//    {
//        $this->validate($document);
//        $basePath = $this->validatePath($this->getContentRoot().'/'.$document->getContentBasePath());
//
//        $nodeName = $document->getNodeName();
//
//        if(empty($nodeName)){
//            $nodeName = $this->parseRouteName($document);
//        }
//
//        $path = $basePath.'/'.$nodeName;
//        $path = $this->validatePath($path);
//        $name = $this->createUniqueNodeName($path);
//
//        return $basePath.'/'.$name;
//    }
//


//    /**
//     * @param RouteAccessInterface $document
//     * @return string
//     */
//    public function getDefaultRoutePathForDocument(RouteAccessInterface $document)
//    {
//        $this->validate($document);
//        $basePath = $this->validatePath($this->getRoutingRoot().'/'.$document->getRouteBasePath());
//        $name = $this->parseRouteName($document);
//        return "$basePath/$name";
//    }

//    /**
//     * @param RouteAccessInterface $document
//     * @return Route
//     */
//    public function createRouteForDocument(RouteAccessInterface $document)
//    {
//        $this->validate($document);
//        $path = $this->getDefaultRoutePathForDocument($document);
//        $this->createPath(dirname($path));
//
//        $route = new Route();
//        $route->setRouteContent($document);
//        $route->setPath($path);
//        return $route;
//    }

//    /**
//     * @param RouteAccessInterface $document
//     * @param RedirectRouteInterface $redirect
//     * @return string
//     */
//    public function getRoutePathForDocument(RouteAccessInterface $document, RedirectRouteInterface $redirect)
//    {
//        $routingRoot = $this->getRoutingRoot();
//
//        $defaultRoute = $document->getDefaultRoute();
//        $this->validate($document);
//        $this->validate($defaultRoute);
//        $this->validate($redirect);
//
//        $name = $this->parseRouteName($document, $redirect->getName());
//        if($setPath = $redirect->getPath()){
//            $path = $this->validatePath(dirname($setPath));
//        } else {
//            $path = $this->validatePath($routingRoot);
//        }
//
//        return "$path/$name";
//    }

//    /**
//     * @param RouteInterface $route
//     * @return \Netvlies\Bundle\RouteBundle\Document\RedirectRoute
//     */
//    public function convertRouteToRedirect(RouteInterface $route)
//    {
//        $this->validate($route);
//        $document = $route->getRouteContent();
//        $redirect = new RedirectRoute();
//        $redirect->setDocumentTarget($document);
//        $redirect->setRouteTarget($document->getDefaultRoute());
//        $redirect->setPath($route->getPath());
//        return $redirect;
//    }
    
//    /**
//     * Creates a unique node name to guarantee an unique entry point (permalink)
//     * and to avoid conflicts when inserting a new PHPCR node.
//     *
//     * @param string $path
//     * @return string
//     */
//    protected function createUniqueNodeName($path)
//    {
//        $number = 1;
//        $nodeName = $baseName = basename($path);
//        while ($this->nodeNameExists($nodeName, dirname($path))) {
//            $nodeName = $baseName.'-'.$number++;
//        }
//        return $nodeName;
//    }
//
//    /**
//     * Checks if a node with the given name exists.
//     *
//     * @param $nodeName
//     * @param $parentPath
//     * @return bool
//     */
//    protected function nodeNameExists($nodeName, $parentPath)
//    {
//        try {
//            $node = $this->phpcrSession->getNode($parentPath.'/'.$nodeName);
//            return true;
//        } catch (PathNotFoundException $exception) {
//            return false;
//        }
//    }
//
//    /**
//     * @param string $path
//     * @return \Jackalope\Node
//     */
//    protected function createPath($path)
//    {
//        /** @var \Jackalope\Node $current */
//        $current = $this->phpcrSession->getRootNode();
//
//        $segments = preg_split('#/#', $path, null, PREG_SPLIT_NO_EMPTY);
//        foreach ($segments as $segment) {
//            if ($current->hasNode($segment)) {
//                $current = $current->getNode($segment);
//            } else {
//                $current = $current->addNode($segment);
//            }
//        }
//        return $current;
//    }

    /**
     * @param object $document
     * @todo we should have a better way of handling the errors, otherwise we dont know where to find the actual error
     * @throws ValidationException
     */
    public function validate($document)
    {
        $errors = $this->validator->validate($document);
        if (count($errors) > 0) {
//            var_dump($errors->get(0)->getMessage());
//            var_dump($errors->get(0)->getPropertyPath());
            throw new ValidationException(sprintf("Invalid instance of class %s, %d constraint violations raised.", get_class($document), count($errors)));
        }
    }

//    /**
//     * @param $path
//     * @return string
//     */
//    public function validatePath($path)
//    {
//    }

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
        $routingRoot = $this->container->getParameter('symfony_cmf_routing_extra.routing_repositoryroot');

        if($this->container->isScopeActive('request')){
            // Domain switching can be done through sonata admin wich involves a routeroot change, this is kept in session
            // @todo maybe this check should be moved to oms_config?
            $omsConfig = $this->container->get('oms_config');
            $routingRoot = $omsConfig->getRoutingRoot();
        }

        return $routingRoot;
    }

    /**
     * @return mixed
     */
    public function getContentRoot()
    {
        $contentRoot = $this->container->getParameter('symfony_cmf_content.static_basepath');
        if($this->container->isScopeActive('request')){
            $omsConfig = $this->container->get('oms_config');
            $contentRoot = $omsConfig->getContentRoot();
        }
        return $contentRoot;
    }

}
