<?php

namespace Iginikolaev\SphinxSearch\Generator\Sections;

/**
 * Class IndexerSection
 *
 * @package Iginikolaev\SphinxSearch
 *
 * @link http://sphinxsearch.com/docs/current.html#confgroup-indexer
 */
class IndexerSection extends AbstractUnnamedSection
{
    /**
     * @return string
     */
    protected function getType()
    {
        return 'indexer';
    }
}