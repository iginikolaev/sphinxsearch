<?php

namespace Iginikolaev\SphinxSearch\Generator;

use Iginikolaev\SphinxSearch\Generator\Sections\SourceSection;
use Illuminate\Support\Arr;

class ConnectionSources
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var SourceSection[]
     */
    protected $connections = [];

    /**
     * ConnectionSources constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param string $name
     *
     * @return SourceSection|null
     */
    public function findOrNew($name)
    {
        $name = $this->normalizeName($name);

        if (Arr::get($this->connections, $name, false) === false) {
            $this->createNew($name);
        }

        return $this->connections[$name];
    }

    /**
     * @return SourceSection
     */
    public function getConnections()
    {
        return $this->connections;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function normalizeName($name)
    {
        return trim($name) ? : \Config::get('database.default');
    }

    /**
     * @param $name
     *
     * @return SourceSection
     */
    private function createNew($name)
    {
        $configPrefix = 'database.connections.' . $name;

        $driverName = \Config::get($configPrefix . '.driver');
        $driverName = $this->normalizeDriverName($driverName);

        // Known types are mysql, pgsql, mssql, xmlpipe, xmlpipe2, odbc
        if (!in_array($driverName, ['mysql', 'pgsql', 'mssql', 'xmlpipe', 'xmlpipe2', 'odbc'])) {
            return null;
        }

        $params = [
            'type' => $driverName,
        ];

        if (strpos($driverName, 'sql') !== false) {
            $params += [
                'sql_host' => \Config::get($configPrefix . '.host'),
                'sql_port' => \Config::get($configPrefix . '.port'),
                'sql_user' => \Config::get($configPrefix . '.username'),
                'sql_pass' => \Config::get($configPrefix . '.password'),
                'sql_db' => \Config::get($configPrefix . '.database'),
                'sql_query_pre' => ["SET NAMES " . \Config::get($configPrefix . '.charset')],
            ];
        }

        $source = new SourceSection($this->config);
        $source->setName(Arr::get($this->config, 'prefix') . $name . '_connection');
        $source->setParams($params);

        $this->connections[$name] = $source;

        return $source;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function normalizeDriverName($name)
    {
        if ($name === 'sqlsrv') {
            return 'mssql';
        }

        return $name;
    }
}