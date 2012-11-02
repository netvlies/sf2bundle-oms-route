<?php
/**
 * (c) Netvlies Internetdiensten
 *
 * @author M. de Krijger <mdekrijger@netvlies.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netvlies\Bundle\RouteBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class DocumentToRouteTransformer implements DataTransformerInterface
{
    /**
     * Checks if the provided target is a route. If not and 'defaultRoute' exists,
     * we can assume it's a document and the default route should be returned
     *
     * @param mixed $value The value in the transformed representation
     * @return mixed The value in the original representation
     */
    public function reverseTransform($value)
    {
        if(is_null($value)){
            return null;
        }

        return $value->getDefaultRoute();
    }

    /**
     * Checks if the provided target is a route. If not and 'defaultRoute' exists,
     * we can assume it's a document and the default route should be returned
     *
     * @param mixed $value The value in the original representation
     * @return mixed The value in the transformed representation
     */
    public function transform($value)
    {
        if(is_null($value)){
            return null;
        }

        if ($value instanceof \Netvlies\Bundle\RouteBundle\Document\Route) {
            return $value->getRouteContent();
        }

        return $value;
    }
}
