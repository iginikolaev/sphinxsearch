<?php

namespace Iginikolaev\SphinxSearch;

/**
 * Class SphinxSearchable
 *
 * @package Iginikolaev\SphinxSearch
 * @mixin ProxySphinxBuilder
 */
trait SphinxSearchable
{

    /**
     * Sphinx index name
     *
     * @return string
     */
    public function getSphinxName()
    {
        return config('sphinxsearch.prefix') . $this->getSphinxBaseName();
    }

    /**
     * @return string
     */
    public function getSphinxIndexName()
    {
        return $this->getSphinxName() . '_index';
    }

    /**
     * @return string
     */
    public function getSphinxSourceName()
    {
        return $this->getSphinxName() . '_source';
    }

    /**
     * @return string
     */
    public function getSphinxDeltaIndexName()
    {
        return $this->getSphinxName() . '_delta_index';
    }

    /**
     * @return string
     */
    public function getSphinxDeltaSourceName()
    {
        return $this->getSphinxName() . '_delta_source';
    }

    /**
     * Basic, non-prefixed name
     *
     * @return string
     * @internal
     */
    protected function getSphinxBaseName()
    {
        return class_basename($this);
    }

    /**
     * Determine do we use delta index
     *
     * @return bool
     */
    public function hasSphinxDelta()
    {
        return !empty($this->sphinxHasDelta);
    }

    /**
     * Create a new proxy query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder $query
     *
     * @return ProxySphinxBuilder|static
     */
    public function newEloquentBuilder($query)
    {
        return new ProxySphinxBuilder($query);
    }
}