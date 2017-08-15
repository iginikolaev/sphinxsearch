<?php

namespace Iginikolaev\SphinxSearch\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;

class DeltaTableCommand extends Command
{
    protected $signature = 'sphinxsearch:delta-table';

    protected $description = 'Create a migration for the sphinx trigger database table';

    /**
     * @var Filesystem
     */
    protected $files;

    /**
     * @var Composer
     */
    protected $composer;

    public function __construct(Filesystem $files, Composer $composer)
    {
        parent::__construct();

        $this->files = $files;
        $this->composer = $composer;
    }

    public function handle()
    {
        $path = $this->laravel['migration.creator']->create(
            'create_sphinx_trigger_table',
            $this->laravel->databasePath() . '/migrations'
        );

        $this->files->put($path, $this->files->get(__DIR__ . '/stubs/delta_table.stub'));

        $this->info('Migration created successfully!');

        $this->composer->dumpAutoloads();
    }
}