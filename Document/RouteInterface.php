<?php
/*
 * (c) Netvlies Internetdiensten
 *
 * Sjoerd Peters <speters@netvlies.net>
 * 31-8-12
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Netvlies\Bundle\RouteBundle\Document;

use Symfony\Cmf\Component\Routing\RouteObjectInterface;

interface RouteInterface extends RouteObjectInterface
{
//    /**
//     * This must return the entire path of the route node
//     *
//     * @abstract
//     * @return string
//     */
//    public function getPath();


    public function getRedirects();


}
