<?php

return [
    /**
     * Sphinx connection params
     */
    'host' => env('SPH_HOST', '127.0.0.1'),
    'port' => env('SPH_PORT', 9312),

    /**
     * Connection timeout
     */
    'timeout' => env('SPH_TIMEOUT', 30),

    /**
     *
     */
    'port_mysql' => env('SPH_PORT_MYSQL'), // 9306

    /**
     * Prefix for all indexes and sources
     */
    'prefix' => env('SPH_PREFIX'),

    /**
     *
     */
    'generator' => [

        /**
         * Path where generated config file will be stored
         */
        'config_filepath' => env('SPH_CONFIG_FILEPATH', storage_path('sphinx/sphinx.conf')),

        /**
         * Define in which directories the generator command should look for models
         */
        'model_locations' => [
            'app',
        ],

        /**
         * Path where searchd logs will be stored
         */
        'logs_dir' => env('SPH_LOGS_DIR', storage_path('sphinx/logs')),

        /**
         * Path where searchd data will be stored
         */
        'data_dir' => env('SPH_DATA_DIR', storage_path('sphinx/data')),

        /**
         * Additional indexes
         */
        'additional_indexes' => [
            'app_index' => [
                'charset_table' => '0..9, A..Z->a..z, _, a..z, U+410..U+42F->U+430..U+44F, U+430..U+44F, U+0401->U+0435, U+0451->U+0435',
                'ignore_chars' => 'U+20',
                'min_infix_len' => 3,
                'min_word_len' => 3,
                //'enable_star' => 1,
                //'expand_keywords' => 1,
                //'html_strip' => 1,
            ],
        ],

        /**
         * sphinx configuration additional sections
         */
        'additional_sections' => [
            'indexer' => [
                'mem_limit' => '128MB',
            ],
            'common' => [
                'lemmatizer_base' => env('SPH_LEMMATIZER_BASE'), // storage_path('sphinx/dict')
            ],
            'searchd' => [
                /**
                 * Query log is enabled by default when running local environment
                 * You can override the value by setting `query_log` to real file path or false to disable.
                 */
                'query_log' => null,
                'binlog_path' => env('SPH_DATA_DIR', storage_path('sphinx/data')),
                'read_timeout' => 5,
                'max_children' => 30,
                'pid_file' => env('SPH_PID_FILE'),
                'seamless_rotate' => 1,
                'preopen_indexes' => 1,
                'unlink_old' => 1,
            ],
        ],
    ],
];