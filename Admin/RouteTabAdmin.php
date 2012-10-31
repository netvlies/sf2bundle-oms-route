<?php
    /**
     * (c) Netvlies Internetdiensten
     *
     * M. de Krijger <mdekrijger@netvlies.nl>
     *
     * For the full copyright and license information, please view the LICENSE
     * file that was distributed with this source code.
     */

namespace Netvlies\Bundle\RouteBundle\Admin;

use Netvlies\Bundle\OmsBundle\Admin\BaseAdmin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\DoctrinePHPCRAdminBundle\Datagrid\ProxyQuery;
use Sonata\AdminBundle\Validator\ErrorElement;
use Doctrine\ODM\PHPCR\DocumentManager;
use Sonata\AdminBundle\Route\RouteCollection;

use Netvlies\Bundle\RouteBundle\Document\RedirectRoute;
use Netvlies\Bundle\RouteBundle\Form\DataTransformer\PathTransformer;

class RouteTabAdmin extends BaseAdmin
{

    public function getCode()
    {
        return 'routingadmin';
    }


    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper->add('redirects',
            'sonata_type_collection',
            array(
                'label' => 'Alt URL\'s',
                'type_options' => array('delete' => true)
            ),
            array(
                'edit' => 'inline',
                'inline' => 'table',
                'admin_code' => 'netvlies_routing.redirect_route_tab'
            )
        );

    }

}
