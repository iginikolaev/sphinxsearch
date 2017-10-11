<?php

namespace Iginikolaev\SphinxSearch;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Sphinx\SphinxClient;

/**
 * Class Builder
 *
 * @package Iginikolaev\SphinxSearch
 * @mixin \Sphinx\SphinxClient|\Eloquent
 */
class ProxySphinxBuilder
{
    /**
     * @var EloquentBuilder
     */
    protected $eloquentBuilder;

    /**
     * @var Model|SphinxSearchable
     */
    protected $model;

    /**
     * @var SphinxClient
     */
    protected $client;

    /**
     * @var string
     */
    protected $searchString;

    /**
     * ProxySphinxBuilder constructor.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param \Illuminate\Database\Eloquent\Builder $originalBuilder
     */
    public function __construct(
        \Illuminate\Database\Query\Builder $query,
        \Illuminate\Database\Eloquent\Builder $originalBuilder
    ) {
        $this->eloquentBuilder = new $originalBuilder($query);

        $this->setupClient();

        $this->setMatchMode(SphinxClient::SPH_MATCH_ANY);
        $this->setSortMode(SphinxClient::SPH_SORT_RELEVANCE);
    }

    /**
     *
     */
    private function setupClient()
    {
        $client = new SphinxClient();
        $client->setServer(config('sphinxsearch.host'), config('sphinxsearch.port'));
        $client->setConnectTimeout(config('sphinxsearch.timeout'));

        $this->client = $client;
    }

    /**
     * @param string $method
     * @param array $params
     *
     * @return mixed
     */
    public function __call($method, $params)
    {
        if ($method === 'create' || !method_exists($this->client, $method)) {
            $result = call_user_func_array([$this->eloquentBuilder, $method], $params);
        } else {
            $result = call_user_func_array([$this->client, $method], $params);
        }

        if ($result instanceof EloquentBuilder || $result instanceof SphinxClient) {
            return $this;
        }

        return $result;
    }

    /**
     * @param $searchString
     *
     * @return $this
     */
    public function match($searchString)
    {
        $this->searchString = (string)$searchString;

        return $this;
    }

    /**
     * @param Model|SphinxSearchable $model
     *
     * @return $this
     */
    public function setModel(Model $model)
    {
        $this->model = $model;

        $this->eloquentBuilder->setModel($model);

        return $this;
    }

    /**
     * @param array $columns
     *
     * @return \Illuminate\Database\Eloquent\Collection|Model[]
     */
    public function get($columns = ['*'])
    {
        $this->applyResultToEloquentBuilder();

        $result = call_user_func_array([$this->eloquentBuilder, 'get'], func_get_args());

        return $result;
    }

    /**
     * Paginate the given query.
     *
     * @param  int $perPage
     * @param  array $columns
     * @param  string $pageName
     * @param  int|null $page
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     *
     * @throws \InvalidArgumentException
     * @throws SphinxException
     */
    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $this->applyResultToEloquentBuilder();

        $paginator = call_user_func_array([$this->eloquentBuilder, 'paginate'], func_get_args());

        return $paginator;
    }

    /**
     * @throws SphinxException
     */
    private function applyResultToEloquentBuilder()
    {
        $sphinxResult = $this->getSphinxResult();

        if ($sphinxResult === null) {
            // No queries was performed
            return;
        }

        $documentsID = $this->getSphinxDocumentsID($sphinxResult);
        if ($documentsID) {
            $this->eloquentBuilder->whereIn('id', $documentsID);
        } else {
            $this->eloquentBuilder->whereRaw('0 = 1');
        }
    }

    /**
     * @return array|null
     * @throws SphinxException
     */
    private function getSphinxResult()
    {
        if ($this->searchString && !$this->client->reqs) {
            $indexName = $this->model->getSphinxIndexName();
            if ($deltaName = $this->model->getSphinxDeltaIndexName()) {
                $indexName .= ';' . $deltaName;
            }

            $this->client->addQuery($this->searchString, $indexName);
        }

        if (!$this->client->reqs) {
            return null;
        }

        $results = $this->client->runQueries();
        if ($error = $this->client->getLastError()) {
            throw new \RuntimeException($error);
        }

        $this->client->resetFilters();
        $this->client->resetGroupBy();

        $result = head($results);

        if ($result['error']) {
            throw new SphinxException($result['error']);
        }

        return $result;
    }

    /**
     * @param array $sphinxResult
     *
     * @return array
     */
    private function getSphinxDocumentsID(array $sphinxResult)
    {
        return isset($sphinxResult['matches']) ? array_keys($sphinxResult['matches']) : [];
    }
}