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
        $path = $this->admin->getSubject()->getPath();
        if(! empty($path)){

            /** @var \Netvlies\Bundle\RouteBundle\Route\RouteAccessInterface $subject */
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
                ->add('redirects',
                     'sonata_type_collection',
                      array(
                          'label' => 'Alternatieve URL\'s',
                          'required' => false,
                          'data_class' => null,
                          'type_options' => array('delete' => false)
                      ),
                      array(
                          'edit' => 'inline',
                          'inline' => 'table',
                          'admin_code' => 'netvlies_admin.admin.redirect_route'
                      ))
            ->end();

        } else {
            $formMapper->with('URL\'s')
                ->add('redirects',
                     'sonata_type_collection',
                      array(
                          'label' => 'Alternatieve URL\'s',
                          'required' => false,
                          'data_class' => null,
                          'type_options' => array('delete' => false)
                      ),
                      array(
                          'edit' => 'inline',
                          'inline' => 'table',
                          'admin_code' => 'netvlies_admin.admin.redirect_route'
                      ))
            ->end();
        }
    }

    protected function getRoutePaths()
    {
        $routes = array();
        foreach ($this->admin->getSubject()->getRoutes() as $route) {
            $path = $route->getPath();
            $label = str_replace($this->admin->getRoutingRoot(), '', $path);
            $routes[$path] = $label;

            foreach ($route->getRedirects() as $redirect) {
                $path = $redirect->getPath();
                $label = str_replace($this->admin->getRoutingRoot(), '', $path);
                $routes[$path] = $label;
            }
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
