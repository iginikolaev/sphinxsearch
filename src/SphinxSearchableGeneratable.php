<?php

namespace Iginikolaev\SphinxSearch;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;

/**
 * Class SphinxSearchable
 *
 * // Source
 * getSphinxSourceAttributes Sphinx source attributes from `->sphinxSourceAttributes`
 *
 * getSphinxSourceQueries Collect queries params
 * sql_query_pre        - getSphinxSourceQueryPre
 * sql_query            - getSphinxSourceQuery
 * sql_query_range      - getSphinxSourceQueryRange
 * sql_query_killlist   - getSphinxSourceQueryKillList
 * sql_query_post       - getSphinxSourceQueryPost
 * sql_query_post_index - getSphinxSourceQueryPostIndex
 *
 * getSphinxSourceParentName Source parent name, otherwise connection name will be taken
 * getSphinxSourceParams Additional params for source
 *
 * // Index
 * getSphinxIndexParentName Index source name from `->sphinxIndexParentName`, otherwise `app_index`
 * getSphinxIndexParams Additional params for index
 *
 * // Delta source
 * getSphinxDeltaSourceQueries Collect delta queries params
 * sql_query_pre        - getSphinxDeltaSourceQueryPre
 * sql_query            - getSphinxDeltaSourceQuery
 * sql_query_range      - getSphinxDeltaSourceQueryRange
 * sql_query_killlist   - getSphinxDeltaSourceQueryKillList
 * sql_query_post       - getSphinxDeltaSourceQueryPost
 * sql_query_post_index - getSphinxDeltaSourceQueryPostIndex
 *
 * getSphinxDeltaSourceParams Additional params for delta source
 *
 * // Delta index
 * getSphinxDeltaIndexParams Additional params for delta index
 */
trait SphinxSearchableGeneratable
{
    use SphinxSearchable;

    //<editor-fold desc="Methods for index generation">

    /**
     * Get index parent name
     *
     * @return string|null
     */
    public function getSphinxIndexParentName()
    {
        return isset($this->sphinxIndexParentName) ? $this->sphinxIndexParentName : 'app_index';
    }
    //</editor-fold>

    //<editor-fold desc="Methods for source generation">
    /**
     * @param string $methodPrefix
     *
     * @return array
     */
    public function getSphinxSourceQueries($methodPrefix = 'get_sphinx_source_')
    {
        $return = [
            'sql_query_pre' => null,
            'sql_query' => null,
            'sql_query_range' => null,
            'sql_query_killlist' => null,
            'sql_query_post' => null,
            'sql_query_post_index' => null,
        ];

        foreach ($return as $name => $_v) {
            $methodName = Str::camel(str_replace('sql_', $methodPrefix, $name));
            if (method_exists($this, $methodName)) {
                $return[$name] = $this->$methodName();
            }
        }

        return $return;
    }

    /**
     * @return array|null
     */
    protected function getSphinxSourceQueryPre()
    {
        if (!$this->hasSphinxDelta()) {
            return null;
        }

        $queries = [
            // Since we are overriding sql_query_pre we need to re-add this
            "SET NAMES utf8",
            // Set last_timestamp for sphinx trigger now because if we do that after indexing is completed we might miss some documents
            "INSERT INTO sphinx_trigger (index_name, last_timestamp) VALUES (" . $this->getConnection()->getPdo()->quote($this->getSphinxName()) . ", UNIX_TIMESTAMP()) ON DUPLICATE KEY UPDATE last_timestamp = VALUES(last_timestamp)",
        ];

        return $queries;
    }

    /**
     * Main source query
     *
     * @return @return string|null|array|\Illuminate\Database\Query\Builder
     */
    protected function getSphinxSourceQuery()
    {
        $attributes = [];
        foreach ($this->getSphinxSourceAttributes() as $name => $type) {
            $attributes[] = is_numeric($name) ? $type : $name;
        }

        return $this->toBase()
            ->select(array_merge([$this->getKeyName()], $attributes));
    }

    /**
     * @return array|null
     */
    protected function getSphinxSourceQueryPostIndex()
    {
        if (!$this->hasSphinxDelta()) {
            return null;
        }

        // This will update sphinx_trigger once indexing completes
        $queries = [
            "UPDATE sphinx_trigger SET last_id = \$maxid WHERE index_name = " . $this->getConnection()->getPdo()->quote($this->getSphinxName()),
        ];

        return $queries;
    }

    /**
     * Get source attributes
     *
     * @return array
     */
    public function getSphinxSourceAttributes()
    {
        return isset($this->sphinxSourceAttributes) ? $this->sphinxSourceAttributes : [];
    }

    //</editor-fold>

    //<editor-fold desc="Methods for delta source generation">
    /**
     * Queries for delta source
     *
     * @return array
     */
    public function getSphinxDeltaSourceQueries()
    {
        return $this->getSphinxSourceQueries('get_sphinx_delta_source_');
    }

    /**
     * @return array
     */
    protected function getSphinxDeltaSourceQueryPre()
    {
        $queries = [
            // Since we are overriding sql_query_pre we need to re-add this
            "SET NAMES utf8",
            "SELECT @last_id:=last_id, @last_timestamp:=last_timestamp FROM sphinx_trigger WHERE index_name = " . $this->getConnection()->getPdo()->quote($this->getSphinxName()),
        ];

        return $queries;
    }

    /**
     * @return array
     */
    protected function getSphinxDeltaSourceQueryPostIndex()
    {
        // Override this so it won't get inherited
        return [''];
    }

    /**
     * @return Builder
     */
    protected function getSphinxDeltaSourceQuery()
    {
        /* @var Builder $builder */
        $builder = $this->getSphinxSourceQuery();

        $builder = $builder->where(function ($query) {
            /* @var Builder $query */
            $query->whereRaw($this->getKeyName() . ' > @last_id');

            if ($this->timestamps) {
                $updatedAt = $this->getDateFormat() === 'U' ?
                    static::UPDATED_AT :
                    ("UNIX_TIMESTAMP(" . static::UPDATED_AT . ")");

                $query->whereRaw($updatedAt . ' > @last_timestamp', [], 'or');
            }
        });

        return $builder;
    }
    //</editor-fold>
}