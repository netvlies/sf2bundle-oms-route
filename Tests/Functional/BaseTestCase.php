<?php



namespace Netvlies\Bundle\RouteBundle\Tests\Functional;

use Symfony\Cmf\Bundle\RoutingExtraBundle\Document\Route;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BaseTestCase extends WebTestCase
{
    /**
     * @var \Doctrine\ODM\PHPCR\DocumentManager
     */
    protected static $documentManager;



    static protected function createKernel(array $options = array())
    {
        return new AppKernel(
            isset($options['config']) ? $options['config'] : 'default.yml'
        );
    }

    /**
     * careful: the kernel is shut down after the first test, if you need the
     * kernel, recreate it.
     *
     * @param array $options passed to self:.createKernel
     * @param string $routebase base name for routes under /test to use
     */
    public static function setupBeforeClass(array $options = array())
    {
        self::$kernel = self::createKernel($options);
        self::$kernel->init();
        self::$kernel->boot();

        self::$documentManager = self::$kernel->getContainer()->get('doctrine_phpcr.odm.document_manager');

        /**
         * @var \PHPCR\SessionInterface $session
         */
        $session = self::$kernel->getContainer()->get('doctrine_phpcr.session');


        /**
         * @var \Netvlies\Bundle\OmsBundle\OmsConfig $omsConfig
         */
        $omsConfig =  self::$kernel->getContainer()->get('oms_config');
        $routingRoot = $omsConfig->getRoutingRoot();
        $redirectRoot = $omsConfig->getRedirectsRoot();
        $contentRoot = $omsConfig->getContentRoot();

        if ($session->nodeExists($routingRoot)) {
            $session->getNode($routingRoot)->remove();
        }
        if ($session->nodeExists($contentRoot)) {
            $session->getNode($contentRoot)->remove();
        }
        if ($session->nodeExists($redirectRoot)) {
            $session->getNode($redirectRoot)->remove();
        }

        $session->save();

        self::createPath($routingRoot);
        self::createPath($contentRoot);
        self::createPath($redirectRoot);

        $session->save();
    }

    /**
     * @param string $path
     * @return \Jackalope\Node
     */
    protected static function createPath($path)
    {
        /** @var \Jackalope\Node $current */
        $session = self::$kernel->getContainer()->get('doctrine_phpcr.session');
        $current = $session->getRootNode();

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