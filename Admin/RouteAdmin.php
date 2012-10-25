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

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Validator\ErrorElement;
use Symfony\Cmf\Bundle\RoutingExtraBundle\Document\RedirectRoute;
use Doctrine\ODM\PHPCR\DocumentManager;

class RouteAdmin extends Admin
{
    /** @var \Symfony\Component\DependencyInjection\ContainerInterface $container */
    protected $container;
    
    /** @var \Doctrine\ODM\PHPCR\DocumentManager $dm */
    protected $dm;
    
    /** @var string $routingRoot */
    protected $routingRoot;
    
    /** @var string $contentRoot */
    protected $contentRoot;

    /** @var \PHPCR\SessionInterface $phpcrSession */
    protected $phpcrSession;

    protected $datagridValues = array(
        '_page'       => 1,
        '_sort_order' => 'DESC',
        '_sort_by' => 'path'
    );
    
    public function setContainer($container)
    {
        $this->dm = $container->get('doctrine_phpcr.odm.document_manager');
        $this->routingRoot = $container->getParameter('symfony_cmf_chain_routing.routing_repositoryroot');
        $this->contentRoot = $container->getParameter('symfony_cmf_content.static_basepath');
        $this->phpcrSession = $container->get('doctrine_phpcr.default_session');
        $this->container = $container;
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('path', 'text', array('label' => 'Pad', 'template'=>'NetvliesOmsBundle:Sonata:Admin/List/routing_root_transformer.html.twig'))
            ->add('routeContent', 'text', array('label' => 'Pagina'))
        ;
    }

    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->with('General')
                ->add('parent', 'doctrine_phpcr_type_tree_model', array('choice_list' => array(), 'select_root_node' => true, 'root_node' => $this->routingRoot))
                ->add('name', 'text', array('label'=>'Last URL part'))
                ->add('variablePattern', 'text', array('required' => false));
        
        if(! $this->hasParentFieldDescription()){
            $formMapper->add('routeContent', 'doctrine_phpcr_type_tree_model', array('class' => 'Netvlies\Bundle\PageBundle\Document\Page', 'required' => false, 'root_node' => $this->contentRoot));
        }
        
        $formMapper->end();
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('name',  'doctrine_phpcr_string', array('label' => 'Naam'))
            ;
    }

    public function getExportFormats()
    {
        return array();
    }
}