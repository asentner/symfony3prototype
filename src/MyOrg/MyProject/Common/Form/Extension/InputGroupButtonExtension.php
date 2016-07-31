<?php

namespace MyOrg\MyProject\Common\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\ButtonBuilder;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InputGroupButtonExtension extends AbstractTypeExtension
{
    /**
     * @var array
     */
    protected $buttons = [];

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if (!isset($this->buttons[$form->getName()])) {
            return;
        }
        $storedButtons = $this->buttons[$form->getName()];
        if (isset($storedButtons['prepend']) && $storedButtons['prepend'] !== null) {
            $view->vars['input_group_button_prepend'] = $storedButtons['prepend']->getForm()->createView();
        }
        if (isset($storedButtons['append']) && $storedButtons['append'] !== null) {
            $view->vars['input_group_button_append'] = $storedButtons['append']->getForm()->createView();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!isset($options['attr']) || !isset($options['attr']['input_group'])) {
            return;
        }
        if (isset($options['attr']['input_group']['button_prepend'])) {
            $this->storeButton(
                $this->addButton(
                    $builder,
                    $options['attr']['input_group']['button_prepend']
                ),
                $builder,
                'prepend'
            );
        }
        if (isset($options['attr']['input_group']['button_append'])) {
            $this->storeButton(
                $this->addButton(
                    $builder,
                    $options['attr']['input_group']['button_append']
                ),
                $builder,
                'append'
            );
        }
    }

    /**
     * Adds a button
     *
     * @param FormBuilderInterface $builder
     * @param array                $config
     *
     * @return ButtonBuilder
     */
    protected function addButton(FormBuilderInterface $builder, $config)
    {
        $options = (isset($config['options']))? $config['options'] : [];
        return $builder->create($config['name'], $config['type'], $options);
    }

    /**
     * Stores a button for later rendering
     *
     * @param ButtonBuilder        $buttonBuilder
     * @param FormBuilderInterface $form
     * @param string               $position
     */
    protected function storeButton(ButtonBuilder $buttonBuilder, FormBuilderInterface $form, $position)
    {
        if (!isset($this->buttons[$form->getName()])) {
            $this->buttons[$form->getName()] = [];
        }
        $this->buttons[$form->getName()][$position] = $buttonBuilder;
    }

    /**
     * Returns the name of the type being extended.
     *
     * @return string The name of the type being extended
     */
    public function getExtendedType()
    {
        return TextType::class;
    }
}