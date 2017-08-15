<?php

namespace Iginikolaev\SphinxSearch\Generator\Sections;

class IndexSection extends AbstractSection
{
    /**
     * @return string
     */
    protected function getType()
    {
        return 'index';
    }
}