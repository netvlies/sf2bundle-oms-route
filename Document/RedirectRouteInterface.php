<?php
/**
* (c) Netvlies Internetdiensten
*
* @author Sjoerd Peters <speters@netvlies.nl>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace Netvlies\Bundle\RouteBundle\Document;

use Symfony\Cmf\Component\Routing\RedirectRouteInterface as BaseRedirectRouteInterface;


interface RedirectRouteInterface extends BaseRedirectRouteInterface
{
    /**
     * @abstract
     * @param bool $active
     * @return mixed
     */
    public function setActive($active);

    /**
     * @abstract
     * @return bool
     */
    public function isActive();


}
