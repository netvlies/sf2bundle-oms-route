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
use Netvlies\Bundle\RouteBundle\Document\RedirectRoute;
use Netvlies\Bundle\RouteBundle\Routing\RouteService;

use Netvlies\Bundle\RouteBundle\Tests\Model\MyPage;

//use Netvlies\Bundle\RouteBundle\Tests\Model\TestRouteNamePage;

class RouteServiceTest extends BaseTestCase
{

    /** @var RouteService $routeService */
    private $routeService;

    /** @var \Symfony\Component\DependencyInjection\ContainerInterface */
    private $container;

    public function setUp()
    {
        $client = static::createClient();
        $this->container = $client->getContainer();
        $this->routeService = $this->container->get('netvlies_routing.route_service');
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
        $page = new MyPage();
        $page->setPath($this->container->getParameter('content_root').'/mypage');
        $page->setTitle('My page');

        $name = $this->routeService->parseRouteName('[title]', $page);
        $this->assertEquals('my-page', $name);

    }

    public function testValidateDocument()
    {
        $page = new MyPage();
        $this->setExpectedException('\Netvlies\Bundle\RouteBundle\Exception\ValidationException');
        $this->routeService->validate($page);
    }
}

