<?php

namespace MyOrg\MyProject\Common\Twig\Extension;


class BootstrapBadgeExtension extends \Twig_Extension
{
    /**
     * {@inheritDoc}
     */
    public function getFunctions()
    {


        return [
            new \Twig_SimpleFunction('badge', array($this, 'buttonFunction'), array('is_safe' => array('html'))),
        ];
    }

    /**
     * Returns the HTML code for a badge.
     *
     * @param string $text The text of the badge
     *
     * @return string The HTML code of the badge
     */
    public function badgeFunction($text)
    {
        return sprintf('<span class="badge">%s</span>', $text);
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'bootstrap_badge';
    }
}