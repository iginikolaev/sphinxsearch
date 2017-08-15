<?php

namespace Iginikolaev\SphinxSearch;

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
}