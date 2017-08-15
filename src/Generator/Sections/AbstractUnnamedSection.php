<?php

namespace Iginikolaev\SphinxSearch\Generator\Sections;

abstract class AbstractUnnamedSection extends AbstractSection
{
    /**
     * @return null
     */
    final public function getName()
    {
        return null;
    }

    /**
     * @return null
     */
    final public function getParent()
    {
        return null;
    }
}