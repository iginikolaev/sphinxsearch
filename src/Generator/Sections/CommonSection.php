<?php

namespace Iginikolaev\SphinxSearch\Generator\Sections;

/**
 * Class CommonSection
 *
 * @package Iginikolaev\SphinxSearch\Generator
 *
 * @link http://sphinxsearch.com/docs/current.html#confgroup-common Since sphinxsearch 2.0.1-beta
 */
class CommonSection extends AbstractUnnamedSection
{
    /**
     * @return string
     */
    protected function getType()
    {
        return 'common';
    }
}