<?php
/**
* (c) Netvlies Internetdiensten
*
* @author Sjoerd Peters <speters@netvlies.nl>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace Netvlies\Bundle\RouteBundle\Tests\Model;

use Netvlies\Bundle\PageBundle\Document\Page;

class TestBasePathPage extends Page
{
    public function __construct()
    {
        $this->contentBasePath = 'pages';
        $this->routeBasePath = 'pages';
    }
}
