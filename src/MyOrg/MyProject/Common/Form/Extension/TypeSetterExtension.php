<?php

namespace MyOrg\MyProject\Common\Form\Extension;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class TypeSetterExtension extends AbstractTypeExtension
{
    /**
     * {@inheritDoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['original_type'] = $form->getConfig()->getType()->getName();
    }

    /**
     * {@inheritDoc}
     */
    public function getExtendedType()
    {
        return FormType::class;
    }
}