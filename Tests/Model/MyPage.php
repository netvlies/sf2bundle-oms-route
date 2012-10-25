<?php
/**
 * (c) Netvlies Internetdiensten
 *
 * @author M. de Krijger <mdekrijger@netvlies.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netvlies\Bundle\RouteBundle\Tests\Model;

use Netvlies\Bundle\RouteBundle\Mapping\Annotations as ROUTING;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Symfony\Cmf\Bundle\ContentBundle\Document\StaticContent;
use Netvlies\Bundle\RouteBundle\Document\RouteAwareInterface;
use Netvlies\Bundle\RouteBundle\Document\RouteInterface;
use Netvlies\Bundle\RouteBundle\Document\RedirectRouteInterface;
use Netvlies\Bundle\RouteBundle\Document\RouteAwareDocument;
use Symfony\Component\Validator\Constraints as Assert;

/**
 *
 * @ROUTING\Routing(basePath="pages", routeName="[title]")
 * @PHPCRODM\Document(referenceable=true)
 */
class MyPage extends RouteAwareDocument
{

    /**
     * to create the document at the specified location. read only for existing documents.
     * @PHPCRODM\Id
     */
    protected $path;

    /**
     * @Assert\NotBlank
     * @PHPCRODM\String()
     */
    protected $title;


    public function setPath($path)
    {
        $this->path = $path;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }
}
