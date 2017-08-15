<?php

namespace Iginikolaev\SphinxSearch\Generator\Sections;

use Illuminate\Support\Arr;

/**
 * Class SearchdSection
 *
 * @package Iginikolaev\SphinxSearch\Generator
 *
 * @link http://sphinxsearch.com/docs/current.html#confgroup-indexer
 */
class SearchdSection extends AbstractUnnamedSection
{
    /**
     * @return string
     */
    protected function getType()
    {
        return 'searchd';
    }

    /**
     * @param array $params
     *
     * @return $this
     */
    public function setParams(array $params)
    {
        $paramsNormalized = $params;

        $logsDir = rtrim(Arr::get($this->config, 'generator.logs_dir'), '/\\');
        $dataDir = rtrim(Arr::get($this->config, 'generator.data_dir'), '/\\');

        // Listeners
        $listenMysqlPort = (int)Arr::get($this->config, 'port_mysql');
        $paramsNormalized['listen'] = array_merge([
            (int)Arr::get($this->config, 'port'),
            $listenMysqlPort ? ($listenMysqlPort . ':mysql41') : null,
        ], (array)Arr::get($params, 'listen', []));

        // Searchd log
        $paramsNormalized['log'] = $logsDir . '/searchd.log';

        // Query log
        $queryLogPath = Arr::get($params, 'query_log');
        if ($queryLogPath || ($queryLogPath === null && \App::isLocal())) {
            $paramsNormalized['query_log'] = $queryLogPath ? : ($logsDir . '/query.log');
        }

        // Daemon PID
        $pidFilePath = Arr::get($params, 'pid_file');
        $paramsNormalized['pid_file'] = $pidFilePath ? : ($dataDir . '/searchd.pid');

        return parent::setParams($paramsNormalized);
    }
}