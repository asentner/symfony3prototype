<?php

namespace MyOrg\MyProject\Common\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class MaskType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'mask_format' => null,
            'mask_options' => [],
        ])
        ->setRequired([
            'mask_format',
        ])
        ->setAllowedTypes([
            'mask_format' => 'string',
            'mask_options' => 'array',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['mask_format'] = $options['mask_format'];
        $view->vars['mask_options'] = $options['mask_options'];
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'text';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'mask';
    }

}