<?php
/**
* (c) Netvlies Internetdiensten
*
* @author Sjoerd Peters <speters@netvlies.nl>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace Netvlies\Bundle\RouteBundle\Tests\Functional;

use Doctrine\Common\EventArgs;
use Netvlies\Bundle\PageBundle\Document\Page;
use Netvlies\Bundle\RouteBundle\Document\RedirectRoute;
use Netvlies\Bundle\RouteBundle\Tests\Model\TestRouteNamePage;
//use Netvlies\Bundle\RouteBundle\Tests\Functional\BaseTestCase;

class RouteAccessSubscriberTest extends BaseTestCase
{

    private $repoRoot;
    const ROUTE_ROOT = '/';


    public static function setupBeforeClass(array $options = array(), $routebase = null)
    {
        parent::setupBeforeClass(array(), basename(self::ROUTE_ROOT));
    }

    public function setUp()
    {
        $client = static::createClient();
        $container = $client->getContainer();
        $this->repoRoot = $container->getParameter('repository_root');
        if($root = self::$dm->find(null, $this->repoRoot)){
            self::$dm->remove($root);
            self::$dm->flush();
        }
    }

//    public function testCreateRemoveBasicPage()
//    {
//        $name = "my-basic-page";
//        $page = new Page();
//        $page->setTitle($name);
//        $page->setContent("long story");
//
//        self::$dm->persist($page);
//        self::$dm->flush();
//        $page = null;
//
//        $page = self::$dm->find(null, $this->repoRoot.'/content/my-basic-page');
//        $this->assertInstanceOf('\Netvlies\Bundle\PageBundle\Document\Page', $page);
//        $this->assertEquals($name, $page->getTitle());
//
//        $primaryRoute = $page->getPrimaryRoute();
//        $this->assertInstanceOf('\Netvlies\Bundle\RouteBundle\Document\Route', $primaryRoute);
//        $this->assertEquals($this->repoRoot.'/routes/my-basic-page', $primaryRoute->getPath());
//
//        $defaultRoute = $page->getDefaultRoute();
//        $this->assertInstanceOf('\Netvlies\Bundle\RouteBundle\Document\Route', $defaultRoute);
//        $this->assertEquals($this->repoRoot.'/routes/my-basic-page', $defaultRoute->getPath());
//        $this->assertEquals($primaryRoute, $defaultRoute);
//
//        $redirects = $page->getRedirects();
//        $this->assertEmpty($redirects);
//
//        $routes = $page->getRoutes();
//        $this->assertCount(1, $routes);
//
//        self::$dm->remove($page);
//        self::$dm->flush();
//
//        $this->assertFalse(self::$dm->contains($page));
//        $this->assertFalse(self::$dm->contains($primaryRoute));
//        $this->assertFalse(self::$dm->contains($defaultRoute));
//    }
//
//    public function testCreateAndAddRedirectRoute()
//    {
//        $name = "my-test-page";
//        $page = new Page();
//        $page->setTitle($name);
//        $page->setContent("long story");
//
//        $redirect = new RedirectRoute();
//        $redirect->setName($name.'-redirect');
//        $redirect->setDocumentTarget($page);
//        $page->addRedirects($redirect);
//
//        self::$dm->persist($page);
//        self::$dm->flush();
//        $page = null;
//
//        $page = self::$dm->find(null, $this->repoRoot.'/content/'.$name);
//        $redirects = $page->getRedirects();
//        $this->assertNotEmpty($redirects);
//        $this->assertCount(1, $redirects);
//
//        $redirect = current($redirects);
//        $this->assertEquals('/netvlies/routes/my-test-page-redirect', $redirect->getPath());
//
//        $routes = $page->getRoutes();
//        $this->assertCount(2, $routes);
//
//        self::$dm->remove($page);
//        self::$dm->flush();
//
//        $this->assertFalse(self::$dm->contains($page));
//        $this->assertFalse(self::$dm->contains($redirect));
//    }
//
//    public function testUpdateAndAddRedirectRoute()
//    {
//        $name = "test-my-page";
//        $page = new Page();
//        $page->setTitle($name);
//        $page->setContent("long story");
//
//        self::$dm->persist($page);
//        self::$dm->flush();
//        $page = null;
//
//        $page = self::$dm->find(null, $this->repoRoot.'/content/'.$name);
//        $redirects = $page->getRedirects();
//        $this->assertEmpty($redirects);
//
//        $routes = $page->getRoutes();
//        $this->assertCount(1, $routes);
//
//        $redirect = new RedirectRoute();
//        $redirect->setName($name.'-redirect');
//        $redirect->setDocumentTarget($page);
//        $page->addRedirects($redirect);
//        self::$dm->persist($page);
//        self::$dm->flush();
//        $page = null;
//
//        $page = self::$dm->find(null, $this->repoRoot.'/content/'.$name); // @todo test duplicate violation
//        $redirects = $page->getRedirects();
//        $this->assertNotEmpty($redirects);
//        $this->assertCount(1, $redirects);
//
//        $redirect = current($redirects);
//        $this->assertEquals('/netvlies/routes/test-my-page-redirect', $redirect->getPath());
//
//        $routes = $page->getRoutes();
//        $this->assertCount(2, $routes);
//
//        self::$dm->remove($page);
//        self::$dm->flush();
//
//        $this->assertFalse(self::$dm->contains($page));
//    }
//
//    public function testUpdateBasicPage()
//    {
//        $name = "my-update-page";
//        $page = new Page();
//        $page->setTitle($name);
//
//        self::$dm->persist($page);
//        self::$dm->flush();
//        $page = null;
//
//        $page = self::$dm->find(null, $this->repoRoot.'/content/my-update-page');
//        $this->assertInstanceOf('\Netvlies\Bundle\PageBundle\Document\Page', $page);
//        $this->assertEquals($name, $page->getTitle());
//
//        $defaultRoute = $page->getDefaultRoute();
//        $this->assertEquals($this->repoRoot.'/routes/'.$name, $defaultRoute->getPath());
//
//        $primaryRoute = $page->getPrimaryRoute();
//        $this->assertEquals($this->repoRoot.'/routes/'.$name, $primaryRoute->getPath());
//
//        $redirects = $page->getRedirects();
//        $this->assertEmpty($redirects);
//
//        $routes = $page->getRoutes();
//        $this->assertCount(1, $routes);
//
//        $title = "my-page";
//        $page->setTitle($title);
//        self::$dm->persist($page);
//        self::$dm->flush();
//        $page = null;
//
//        $page = self::$dm->find(null, $this->repoRoot.'/content/my-update-page');
//        $this->assertInstanceOf('\Netvlies\Bundle\PageBundle\Document\Page', $page);
//        $this->assertEquals($title, $page->getTitle());
//
//        $defaultRoute = $page->getDefaultRoute();
//        $this->assertInstanceOf('\Netvlies\Bundle\RouteBundle\Document\Route', $defaultRoute);
//        $this->assertEquals($this->repoRoot.'/routes/my-page', $defaultRoute->getPath());
//
//        $primaryRoute = $page->getPrimaryRoute();
//        $this->assertInstanceOf('\Netvlies\Bundle\RouteBundle\Document\RedirectRoute', $primaryRoute);
//        $this->assertEquals($this->repoRoot.'/routes/'.$name, $primaryRoute->getPath());
//
//        $redirects = $page->getRedirects();
//        $this->assertEmpty($redirects);
//
//        $routes = $page->getRoutes();
//        $this->assertCount(2, $routes);
//        self::$dm->remove($page);
//        self::$dm->flush();
//
//        $this->assertFalse(self::$dm->contains($page));
//        $this->assertFalse(self::$dm->contains($primaryRoute));
//        $this->assertFalse(self::$dm->contains($defaultRoute));
//    }
//
//    public function testSwitchDefaultRoute()
//    {
//        $name = "my-switch-page";
//        $page = new Page();
//        $page->setTitle($name);
//
//        $redirect = new RedirectRoute();
//        $redirect->setName($name.'-redirect');
//        $redirect->setDocumentTarget($page);
//        $page->addRedirects($redirect);
//
//        self::$dm->persist($page);
//        self::$dm->flush();
//        $page = null;
//
//        $page = self::$dm->find(null, $this->repoRoot.'/content/my-switch-page');
//
//        $redirects = $page->getRedirects();
//        $this->assertCount(1, $redirects);
//
//        $redirect = current($redirects);
//        $this->assertEquals($this->repoRoot.'/routes/'.$name.'-redirect', $redirect->getPath());
//
//        $page->setDefaultRouteSwitch($redirect->getPath());
//
//        self::$dm->persist($page);
//        self::$dm->flush();
//        $page = null;
//
//        $page1 = self::$dm->find(null, $this->repoRoot.'/content/my-switch-page');
//
//        $defaultRoute = $page1->getDefaultRoute();
//        $this->assertInstanceOf('\Netvlies\Bundle\RouteBundle\Document\Route', $defaultRoute);
//        $this->assertEquals($this->repoRoot.'/routes/'.$name.'-redirect', $defaultRoute->getPath());
//
//        $primaryRoute = $page1->getPrimaryRoute();
//        $this->assertInstanceOf('\Netvlies\Bundle\RouteBundle\Document\RedirectRoute', $primaryRoute);
//        $this->assertEquals($this->repoRoot.'/routes/'.$name, $primaryRoute->getPath());
//
//        $redirects1 = $page1->getRedirects();
//        $this->assertCount(0, $redirects1); // the switched redirect should be same as the primaryRoute
//
//        self::$dm->remove($page);
//        self::$dm->flush();
//
//        $this->assertFalse(self::$dm->contains($page));
//        $this->assertFalse(self::$dm->contains($primaryRoute));
//        $this->assertFalse(self::$dm->contains($defaultRoute));
//    }
//
//    public function _testUpdatePageAndSwitchDefaultRoute()
//    {
//        $name = "my-switch-page";
//        $page = new Page();
//        $page->setTitle($name);
//
//        $redirect = new RedirectRoute();
//        $redirect->setName($name.'-redirect');
//        $redirect->setDocumentTarget($page);
//        $page->addRedirects($redirect);
//
//        self::$dm->persist($page);
//        self::$dm->flush();
//        $page = null;
//
//        $page = self::$dm->find(null, $this->repoRoot.'/content/my-switch-page');
//
//        $redirects = $page->getRedirects();
//        $this->assertCount(1, $redirects);
//
//        $redirect = current($redirects);
//        $page->setDefaultRouteSwitch($redirect);
//
//        self::$dm->persist($page);
//        self::$dm->flush();
//        self::$dm->clear();
//        $page = null;
//
//        $page = self::$dm->find(null, $this->repoRoot.'/content/my-switch-page');
//
//        $defaultRoute = $page->getDefaultRoute();
//        $this->assertInstanceOf('\Netvlies\Bundle\RouteBundle\Document\Route', $defaultRoute);
//        $this->assertEquals($this->repoRoot.'/routes/'.$name.'-redirect', $defaultRoute->getPath());
//
//        $primaryRoute = $page->getPrimaryRoute();
//        $this->assertInstanceOf('\Netvlies\Bundle\RouteBundle\Document\RedirectRoute', $primaryRoute);
//        $this->assertEquals($this->repoRoot.'/routes/'.$name, $primaryRoute->getPath());
//
//        $redirects = $page->getRedirects();
//        $this->assertCount(0, $redirects); // the switched redirect should be same as the primaryRoute
//    }
//
//    public function testUpdateBasicPageBackAndForth()
//    {
//        $name1 = "my-first-page";
//        $page = new Page();
//        $page->setTitle($name1);
//
//        echo "first\n";
//        self::$dm->persist($page);
//        self::$dm->flush();
//
//        $page1 = self::$dm->find(null, $this->repoRoot.'/content/my-first-page');
//
//        $name2 = "my-second-page";
//        $page1->setTitle($name2);
//        echo "second\n";
//        self::$dm->persist($page1);
//        self::$dm->flush();
//
//        $page2 = self::$dm->find(null, $this->repoRoot.'/content/my-first-page');
//
//        $defaultRoute = $page2->getDefaultRoute();
//        $this->assertInstanceOf('\Netvlies\Bundle\RouteBundle\Document\Route', $defaultRoute);
//        $this->assertEquals($this->repoRoot.'/routes/my-second-page', $defaultRoute->getPath());
//
//        $primaryRoute = $page2->getPrimaryRoute();
//        $this->assertInstanceOf('\Netvlies\Bundle\RouteBundle\Document\RedirectRoute', $primaryRoute);
//        $this->assertEquals($this->repoRoot.'/routes/'.$name1, $primaryRoute->getPath());
//
//        $name3 = "my-third-page";
//        $page2->setTitle($name3);
//        echo "third\n";
//        self::$dm->persist($page2);
//        self::$dm->flush();
//
//        $page3 = self::$dm->find(null, $this->repoRoot.'/content/my-first-page');
//
//        $defaultRoute = $page3->getDefaultRoute();
//        $this->assertInstanceOf('\Netvlies\Bundle\RouteBundle\Document\Route', $defaultRoute);
//        $this->assertEquals($this->repoRoot.'/routes/my-third-page', $defaultRoute->getPath());
//
//        $primaryRoute = $page3->getPrimaryRoute();
//        $this->assertInstanceOf('\Netvlies\Bundle\RouteBundle\Document\RedirectRoute', $primaryRoute);
//        $this->assertEquals($this->repoRoot.'/routes/'.$name1, $primaryRoute->getPath());
//
//        $redirects = $page3->getRedirects();
//        $this->assertCount(1, $redirects);
//
//        $redirect = current($redirects);
//        $this->assertEquals('/netvlies/routes/my-second-page', $redirect->getPath());
//
//        $routes = $page3->getRoutes();
//        $this->assertCount(3, $routes);
//
//        $page3->setTitle($name1);
//        echo "fourth\n";
//        self::$dm->persist($page3);
//        self::$dm->flush();
//
//        $page4 = self::$dm->find(null, $this->repoRoot.'/content/my-first-page');
//
//        $defaultRoute = $page4->getDefaultRoute();
//        $this->assertInstanceOf('\Netvlies\Bundle\RouteBundle\Document\Route', $defaultRoute);
//        $this->assertEquals($this->repoRoot.'/routes/my-first-page', $defaultRoute->getPath());
//
//        $primaryRoute = $page4->getPrimaryRoute();
//        $this->assertInstanceOf('\Netvlies\Bundle\RouteBundle\Document\Route', $primaryRoute);
//        $this->assertEquals($this->repoRoot.'/routes/'.$name1, $primaryRoute->getPath());
//
//        $redirects = $page4->getRedirects();
//        $this->assertCount(2, $redirects);
//
//        $names = array($name2, $name3);
//        for ($i = 0; $i < count($redirects); $i++) {
//            $redirect = $redirects[$i];
//            $this->assertEquals('/netvlies/routes/'.$names[$i], $redirect->getPath());
//        }
//
//        $routes = $page4->getRoutes();
//        $this->assertCount(3, $routes);
//
//        self::$dm->remove($page1);
//        self::$dm->remove($page2);
//        self::$dm->remove($page3);
//        self::$dm->remove($page4);
//        self::$dm->flush();
//
//        $this->assertFalse(self::$dm->contains($page1));
//        $this->assertFalse(self::$dm->contains($page2));
//        $this->assertFalse(self::$dm->contains($page3));
//        $this->assertFalse(self::$dm->contains($page4));
//    }
//
//    public function _testCreateDuplicatePage()
//    {
//        $name = "my-test-page";
//        $page = new Page();
//        $page->setTitle($name);
//        $page->setContent("long story");
//
//        self::$dm->persist($page);
//        self::$dm->flush();
//        $page = null;
//
//        $page = self::$dm->find(null, $this->repoRoot.'/content/my-test-page');
//
//        $this->assertEquals($name, $page->getNodeName());
//        $this->assertEquals($name, $page->getTitle());
//
//        $primaryRoute = $page->getPrimaryRoute();
//        $this->assertEquals($this->repoRoot.'/routes/my-test-page', $primaryRoute->getPath());
//
//        $defaultRoute = $page->getDefaultRoute();
//        $this->assertEquals($this->repoRoot.'/routes/my-test-page', $defaultRoute->getPath());
//        $this->assertEquals($primaryRoute, $defaultRoute);
//
//        $page2 = new Page();
//        $page2->setTitle($name);
//        $page2->setContent("longer story");
//
//        self::$dm->persist($page2);
//        self::$dm->flush();
//        $page2 = null;
//
//        $page2 = self::$dm->find(null, $this->repoRoot.'/content/my-test-page-1');
//
//        $this->assertEquals('my-test-page-1', $page2->getNodeName());
//        $this->assertEquals('my-test-page', $page2->getTitle());
//
//        $primaryRoute = $page2->getPrimaryRoute();
//        $this->assertEquals($this->repoRoot.'/routes/my-test-page-1', $primaryRoute->getPath());
//
//        $defaultRoute = $page2->getDefaultRoute();
//        $this->assertEquals($this->repoRoot.'/routes/my-test-page-1', $defaultRoute->getPath());
//        $this->assertEquals($primaryRoute, $defaultRoute);
//
//        self::$dm->remove($page);
//        self::$dm->remove($page2);
//        self::$dm->flush();
//
//        $this->assertFalse(self::$dm->contains($page));
//        $this->assertFalse(self::$dm->contains($page2));
//    }
}


