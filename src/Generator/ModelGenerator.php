<?php

namespace Iginikolaev\SphinxSearch\Generator;

use Iginikolaev\SphinxSearch\Generator\Sections\IndexSection;
use Iginikolaev\SphinxSearch\Generator\Sections\SourceSection;
use Iginikolaev\SphinxSearch\SphinxSearchableGeneratable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ModelGenerator
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var string
     */
    protected $dataDir;

    /**
     * @var ConnectionSources
     */
    protected $connections;

    /**
     * @var Model|SphinxSearchableGeneratable
     */
    protected $model;

    /**
     * @var array
     */
    protected $sections = [];

    /**
     * ModelGenerator constructor.
     *
     * @param array $config
     * @param ConnectionSources $connections
     */
    public function __construct(array $config, ConnectionSources $connections)
    {
        $this->config = $config;
        $this->dataDir = Arr::get($this->config, 'generator.data_dir');
        $this->connections = $connections;
    }

    /**
     * @param string $modelName
     */
    public function generate($modelName)
    {
        $this->model = new $modelName;

        $source = $this->makeSource();
        // Unsuuported connection driver and no parent
        if (!$source) {
            return;
        }

        $this->sections[] = $source;

        $index = $this->makeIndex($source);
        $this->sections[] = $index;

        if ($this->model->hasSphinxDelta()) {
            $deltaSource = $this->makeDeltaSource($source);
            $this->sections[] = $deltaSource;

            $deltaIndex = $this->makeDeltaIndex($index, $deltaSource);
            $this->sections[] = $deltaIndex;
        }
    }

    /**
     * @return array
     */
    public function getSections()
    {
        return $this->sections;
    }

    /**
     * Model source section
     *
     * @return null|SourceSection
     */
    private function makeSource()
    {
        $parent = null;
        // Parent provided as string by model
        if (method_exists($this->model, 'getSphinxSourceParentName')) {
            $parent = $this->model->getSphinxSourceParentName();
        }

        // If not provided by model, take connection source 
        if (!$parent) {
            $parent = $this->connections->findOrNew($this->model->getConnectionName());
        }

        // If not provided by model and was not created by connection (invalid driver) - quit
        if (!$parent) {
            return null;
        }

        // Make params
        $params = [];

        // Collect queries
        foreach ($this->model->getSphinxSourceQueries() as $queryName => $queries) {
            if (!$queries) {
                continue;
            }
            $params[$queryName] = [];

            $queries = is_object($queries) ? [$queries] : (array)$queries;
            foreach ($queries as $query) {
                if ($query instanceof Builder) {
                    $query = Str::replaceArray('?', $query->getBindings(), $query->toSql());
                }

                $params[$queryName][] = $query;
            }
        }

        // Collect attributes
        foreach ($this->model->getSphinxSourceAttributes() as $attributeName => $type) {
            if (is_numeric($attributeName)) {
                continue;
            }

            $params[$type] = $attributeName;
        }

        // Collect additional params
        if (method_exists($this->model, 'getSphinxSourceParams')) {
            $params = array_merge($params, $this->model->getSphinxSourceParams());
        }

        $source = new SourceSection($this->config);
        $source->setName($this->model->getSphinxSourceName())
            ->setParent($parent)
            ->setParams($params);

        return $source;
    }

    /**
     * @param SourceSection $source
     *
     * @return IndexSection
     */
    private function makeIndex(SourceSection $source)
    {
        $parent = $this->model->getSphinxIndexParentName();

        $index = new IndexSection($this->config);
        $index->setName($this->model->getSphinxIndexName())
            ->setParent($parent);

        $params = [];
        if (method_exists($this->model, 'getSphinxIndexParams')) {
            $params = (array)$this->model->getSphinxIndexParams();
        }

        // Mandatory params
        $params = array_merge($params, [
            'source' => $source->getName(),
            'path' => $this->dataDir . '/' . $index->getName(),
        ]);

        $index->setParams($params);

        return $index;
    }

    /**
     * @param SourceSection $source
     *
     * @return SourceSection
     */
    private function makeDeltaSource(SourceSection $source)
    {
        // Make params
        $params = [];

        // Collect queries
        foreach ($this->model->getSphinxDeltaSourceQueries() as $queryName => $queries) {
            if (!$queries) {
                continue;
            }
            $params[$queryName] = [];

            $queries = is_object($queries) ? [$queries] : (array)$queries;
            foreach ($queries as $query) {
                if ($query instanceof Builder) {
                    $query = Str::replaceArray('?', $query->getBindings(), $query->toSql());
                }

                $params[$queryName][] = $query;
            }
        }

        // Collect additional params
        if (method_exists($this->model, 'getSphinxDeltaSourceParams')) {
            $params = array_merge($params, $this->model->getSphinxDeltaSourceParams());
        }

        $delta = new SourceSection($this->config);
        $delta->setName($this->model->getSphinxDeltaSourceName())
            ->setParent($source)
            ->setParams($params);

        return $delta;
    }

    /**
     * @param IndexSection $index
     * @param SourceSection $deltaSource
     *
     * @return IndexSection
     */
    private function makeDeltaIndex(IndexSection $index, SourceSection $deltaSource)
    {
        $delta = new IndexSection($this->config);
        $delta->setName($this->model->getSphinxDeltaIndexName())
            ->setParent($index);

        $params = [];
        if (method_exists($this->model, 'getSphinxDeltaIndexParams')) {
            $params = (array)$this->model->getSphinxDeltaIndexParams();
        }

        // Mandatory params
        $params = array_merge($params, [
            'source' => $deltaSource->getName(),
            'path' => $this->dataDir . '/' . $delta->getName(),
        ]);

        $delta->setParams($params);

        return $delta;
    }
}