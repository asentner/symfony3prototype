<?php

namespace MyOrg\MyProject\Common\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BootstrapCollectionType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars = array_replace(
            $view->vars,
            array(
                'allow_add'          => $options['allow_add'],
                'allow_delete'       => $options['allow_delete'],
                'add_button_text'    => $options['add_button_text'],
                'delete_button_text' => $options['delete_button_text'],
                'sub_widget_col'     => $options['sub_widget_col'],
                'button_col'         => $options['button_col'],
                'prototype_name'     => $options['prototype_name']
            )
        );

        if (false === $view->vars['allow_delete']) {
            $view->vars['sub_widget_col'] += $view->vars['button_col'];
        }

        if ($form->getConfig()->hasAttribute('prototype')) {
            $view->vars['prototype'] = $form->getConfig()->getAttribute('prototype')->createView($view);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'allow_add'          => false,
            'allow_delete'       => false,
            'prototype'          => true,
            'prototype_name'     => '__name__',
            'type'               => 'text',
            'add_button_text'    => 'Add',
            'delete_button_text' => 'Delete',
            'sub_widget_col'     => 10,
            'button_col'         => 2,
            'options'            => array(),
        ));

        $resolver->setNormalizer('options', function(Options $options, $value) {
            $value['block_name'] = 'entry';
            return $value;
        });
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return 'collection';
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'bootstrap_collection';
    }
}