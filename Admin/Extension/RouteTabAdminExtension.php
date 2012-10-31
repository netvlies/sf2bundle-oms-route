<?php
/*
 * (c) Netvlies Internetdiensten
 *
 * Sjoerd Peters <speters@netvlies.net>
 * 27-9-12
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Netvlies\Bundle\RouteBundle\Admin\Extension;

use Netvlies\Bundle\OmsBundle\Admin\Extension\BaseAdminExtension;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;

class RouteTabAdminExtension extends BaseAdminExtension
{
    /**
     * @param FormMapper $form
     */
    public function configureFormFields(FormMapper $formMapper)
    {
        /** @var \Netvlies\Bundle\RouteBundle\Document\RouteAwareInterface $subject */
        $subject = $this->admin->getSubject();
        $defaultRoute = $subject->getDefaultRoute();

        $formMapper->with('URL\'s')
            ->add('switchRoute',
                'choice',
                array(
                    'choices' => $this->getRoutePaths(),
                    'required' => true,
                    'label' => 'Standaard URL',
                    'data' => empty($defaultRoute) ? null : $defaultRoute->getPath()
                ))
            ->add('defaultRoute',
                 'sonata_type_admin',
                  array(
                      'label' => 'Alternatieve URL\'s',
                      'required' => false,
                      'data_class' => '\Netvlies\Bundle\RouteBundle\Document\Route',
                      'delete' => false
                  ),
                  array(
                      'admin_code' => 'netvlies_routing.route_tab'
                  ))
        ->end();


    }

    protected function getRoutePaths()
    {
        $routes = array();
        foreach ($this->admin->getSubject()->getRoutes() as $route) {
            //@todo this cant be final...
            $path = $route->getPath();
            $label = '/' . trim(str_replace($this->admin->getRoutingRoot(), '', $path), '/');
            $label = '/' . trim(str_replace('/netvlies/redirects/', '', $label), '/');
            $routes[$path] = $label;
        }
        asort($routes);
        return $routes;
    }



    public function postUpdate($document)
    {
        $routeAware = $this->container->get('netvlies_routing.route_aware_subscriber');
        $routeAware->flushGarbage();
    }
}
