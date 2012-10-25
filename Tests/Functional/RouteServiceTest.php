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
use Netvlies\Bundle\RouteBundle\Routing\RouteService;

use Netvlies\Bundle\RouteBundle\Tests\Model\MyPage;

//use Netvlies\Bundle\RouteBundle\Tests\Model\TestRouteNamePage;

class RouteServiceTest extends BaseTestCase
{

    /** @var RouteService $routeService */
    private $routeService;

    public function setUp()
    {
        $client = static::createClient();
        $container = $client->getContainer();
        $this->routeService = $container->get('netvlies_routing.route_service');
    }

    public function testValidatePath()
    {
        $path = '/netvlies/content';
        $validPath = $this->routeService->parseRoutePath($path);
        $this->assertEquals('/netvlies/content', $validPath);

        $path = 'netvlies/content';
        $validPath = $this->routeService->parseRoutePath($path);
        $this->assertEquals('/netvlies/content', $validPath);
        
        $path = '/netvlies/ content';
        $validPath = $this->routeService->parseRoutePath($path);
        $this->assertEquals('/netvlies/content', $validPath);
        
        $path = 'netvlies/content/';
        $validPath = $this->routeService->parseRoutePath($path);
        $this->assertEquals('/netvlies/content', $validPath);
        
        $path = '//netvlies/content/';
        $validPath = $this->routeService->parseRoutePath($path);
        $this->assertEquals('/netvlies/content', $validPath);

        $path = 'netvlies///content';
        $validPath = $this->routeService->parseRoutePath($path);
        $this->assertEquals('/netvlies/content', $validPath);
        
        $path = '/netvlies/content/My Page';
        $validPath = $this->routeService->parseRoutePath($path);
        $this->assertEquals('/netvlies/content/my-page', $validPath);
        
        $path = '/netvlies/content/My-Page';
        $validPath = $this->routeService->parseRoutePath($path);
        $this->assertEquals('/netvlies/content/my-page', $validPath);
        
        $path = '/netvlies/content/My - Page';
        $validPath = $this->routeService->parseRoutePath($path);
        $this->assertEquals('/netvlies/content/my-page', $validPath);
        
        $path = '/netvlies/content/all pages/sompages/My Page';
        $validPath = $this->routeService->parseRoutePath($path);
        $this->assertEquals('/netvlies/content/all-pages/sompages/my-page', $validPath);
        
        $path = "/netvlies/content/My : test      Page";
        $validPath = $this->routeService->parseRoutePath($path);
        $this->assertEquals('/netvlies/content/my-test-page', $validPath);
        
        $path = "/netvlies/content/My # %test & Page!";
        $validPath = $this->routeService->parseRoutePath($path);
        $this->assertEquals('/netvlies/content/my-test-page', $validPath);
        
        $path = "/netvlies/content/My tést Page's";
        $validPath = $this->routeService->parseRoutePath($path);
        $this->assertEquals('/netvlies/content/my-test-pages', $validPath);

        $path = '/netvlies/routes/lindenberg/\asdf';
        $validPath = $this->routeService->parseRoutePath($path);
        $this->assertEquals('/netvlies/routes/lindenberg/asdf', $validPath);


    }

    public function testParseRouteName()
    {
        $dm = self::$dm;
        $page = new MyPage();
        $page->setTitle('My page');
        $dm->persist($page);
        $dm->flush();

        return;

        $page->routeName = 'My title';
        $name = $this->routeService->parseRouteName($page);
        $this->assertEquals('my-title', $name);

        $page->routeName = '[title]';
        $name = $this->routeService->parseRouteName($page);
        $this->assertEquals('my-page', $name);

        $page->routeName = '[title] Title';
        $name = $this->routeService->parseRouteName($page);
        $this->assertEquals('my-page-title', $name);

        $page->routeName = '[title] - [content]';
        $name = $this->routeService->parseRouteName($page);
        $this->assertEquals('my-page-long-story', $name);

        $page->routeName = '[title] - - [content]';
        $name = $this->routeService->parseRouteName($page);
        $this->assertEquals('my-page-long-story', $name);
    }
//
//    public function testValidateDocument()
//    {
//        $page = new Page();
//        $this->setExpectedException('\Netvlies\Bundle\RouteBundle\Exception\ValidationException');
//        $this->routeService->validate($page);
//    }
//
//    public function testGetContentPathForDocument()
//    {
//        $page = new Page();
//        $page->setTitle('My page');
//        $path = $this->routeService->getContentPathForDocument($page);
//        $this->assertEquals('/netvlies/content/my-page', $path);
//
//        $page = new TestBasePathPage();
//        $page->setTitle('My page');
//        $path = $this->routeService->getContentPathForDocument($page);
//        $this->assertEquals('/netvlies/content/pages/my-page', $path);
//    }
}

