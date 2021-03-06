<?php

namespace Netvlies\Bundle\RouteBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Netvlies\Bundle\MenuBundle\Document\MenuItem;
use Netvlies\Bundle\OmsBundle\Document\LinkInterface;


class RouteType extends AbstractType implements ContainerAwareInterface
{
    protected $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('linkType', 'choice', array(
                'label' => 'Linktype',
                'choices' => array(
                    LinkInterface::LINK_TYPE_EXTERNAL => 'Extern',
                    LinkInterface::LINK_TYPE_INTERNAL => 'Intern',
                )
            ))
 //This is conditional. (show only in redirect admin, not in subtab within page admin)
            ->add('uri', 'url', array(
                'label' => 'Externe link',
                'required' => false
            ))
            ->add('routeTarget', 'doctrine_phpcr_type_tree_model', array(
                'root_node' => $options['document_root'],
                'choice_list' => array(),
                'required' => false,
                'label'=> 'Interne link',
                'model_manager' => $this->container->get('sonata.admin.manager.doctrine_phpcr')
            ))
        ;
    }


    public function getDefaultOptions(array $options)
    {
        return array(
            'virtual' => true,
            'model_manager' => null,
            'document_root' => $this->container->get('oms_config')->getContentRoot()
        );
    }




    public function getName()
    {
        return 'oms_routelink';
    }
}