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

class RedirectRouteAdmin extends BaseAdmin
{
    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->remove('show');
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('path', 'text', array('label' => 'Redirect', 'template'=>'NetvliesOmsBundle:Sonata:Admin/List/routing_root_transformer.html.twig'))
            ->add('routeTarget', null, array('label' => 'Doel', 'template'=>'NetvliesOmsBundle:Sonata:Admin/List/internalExternalLink.html.twig'))
            ->add('active', null, array('label' => 'Status', 'template'=>'NetvliesOmsBundle:Sonata:Admin/List/status_field_transformer.html.twig'))
        ;
    }


    protected function configureFormFields(FormMapper $formMapper)
    {
        $path = null;
        if($subject = $this->getSubject()){
            $path = $subject->getPath();
        }

        if(empty($path)){
            $formMapper->add('path', 'text', array(
                    'label' => 'URL',
                    'required' => true,
                    'help'=>'Zonder domein en beginnend met / (bijv: /producten/bestseller)')
            );

            $pathTransformer = new PathTransformer($this->omsConfig);
            $formMapper->getFormBuilder()->addModelTransformer($pathTransformer);
        }

        if(! $this->hasParentFieldDescription()) {
            $pathTransformer = new PathTransformer($this->omsConfig);

            $formMapper
                ->add('target', 'oms_routelink',
                    array('label' => 'Link', 'data_class' => 'Netvlies\Bundle\RouteBundle\Document\RedirectRoute'
                ));
            //@todo add transformer, so that redirect route is pointing to route isntead of dodcument
        }


    }

    /**
     * To have right label above edit screen
     *
     * @param mixed $object
     * @return mixed|string
     */
    public function toString($object)
    {
        return str_replace($this->omsConfig->getRoutingRoot(), '', $object->getPath());
    }


    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('path',  'doctrine_phpcr_string', array('label' => 'URL'))
        ;
    }

    /**
     * @param string $context
     * @return ProxyQuery|\Sonata\AdminBundle\Datagrid\ProxyQueryInterface
     */
    public function createQuery($context = 'list')
    {
        if($this->hasParentFieldDescription()){
            // within tab
            $queryBuilder = $this->dm->createQueryBuilder();
            $qomFactory = $queryBuilder->getQOMFactory();
            $queryBuilder->where($qomFactory->comparison($qomFactory->propertyValue('active'), '=', $qomFactory->literal(true)) );
            $query = new ProxyQuery($qomFactory, $queryBuilder);
            $query->setDocumentName($this->getClass());
            $query->setDocumentManager($this->dm);
        } else {
            // full admin
            $query = parent::createQuery($context);
        }
        return $query;
    }

    /**
     * @param \Sonata\AdminBundle\Validator\ErrorElement $errorElement
     * @param \Netvlies\Bundle\RouteBundle\Document\RedirectRoute $object
     */
    public function validate(ErrorElement $errorElement, $redirect)
    {
//        $name = $redirect->getName();
//        if(! $name || empty($name)){
//            $errorElement->with('name')->addViolation("URL is een verplicht veld, vul de naam van de redirect URL in.")->end();
//        }

//        if(substr($name, 0, 1) == '/' || substr($name, 0, 1) == '\\'){
//            $errorElement->with('name')->addViolation("De waarde van het URL veld mag niet beginnen met een / of \\ slash.")->end();
//        }

//        $document = $redirect->getDocumentTarget();
//        if(! $document || empty($document)){
//            $errorElement->with('documentTarget')->addViolation("Pagina is een verplicht veld, selecteer een pagina uit de lijst.")->end();
//        }

        $errors = $errorElement->getErrors();
        if(! empty($errors)){
            return;
        }

        // Beware! Dont just change this. Somehow validation is done 3 times!!!
        // the prepersist method changes the redirectroute, in following if else statement we take 2 scenarios in count
        $explicitPath = $redirect->getPath();
        if(!empty($explicitPath)){
            $path = $redirect->getPath();
        }
        else{
            // When not yet processed by prepersist hook
            $path = $this->routingRoot.'/'.$redirect->getName();
        }

        /**
         * check new redirects for duplicates
         * @var RedirectRoute $node
         */
        // Disabled checking, because it must be possible to change an existing node into a redirectroute
//        if($node = $this->dm->find(null, $path)){
//            /* If the fetched node and the redirect are the same it's
//             * just an update, not a duplicate
//             */
//            if($node !== $redirect){
//                $link = sprintf('<a href="%s">%s</a>', $this->generateObjectUrl('edit', $node), $node->getName());
//                $errMsg = sprintf("De URL <b>%s</b> bestaat al, volg de link om het item te wijzigen %s.", $node->getName(), $link);
//                $errorElement->addViolation($errMsg);
//            }
//        }
    }

    public function getBatchActions()
    {
        return array();
    }
}
