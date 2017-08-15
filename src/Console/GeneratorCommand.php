<?php

namespace Iginikolaev\SphinxSearch\Console;

use Iginikolaev\SphinxSearch\Generator;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class GeneratorCommand extends Command
{
    protected $signature = 'sphinxsearch:generate {--output}';

    protected $description = 'Create a new sphinx configuration file';

    /**
     * @var Filesystem
     */
    protected $files;

    public function __construct(Filesystem $files)
    {
        $this->files = $files;

        parent::__construct();
    }

    public function handle()
    {
        $fileName = \Config::get('sphinxsearch.generator.config_filepath');

        $this->createDirectories();

        $generator = new Generator();
        $content = $generator->generate();

        if ($this->option('output')) {
            $this->line($content);

            return;
        }
        $result = $this->files->put($fileName, $content);

        if ($result !== false) {
            $this->info('New sphinx config file was created:');
            $this->info($fileName);
        } else {
            $this->error('Failed to write new sphinx config file to ' . $fileName);
        }
    }

    /**
     *
     */
    private function createDirectories()
    {
        $dirs = [
            dirname(\Config::get('sphinxsearch.generator.config_filepath')),
            \Config::get('sphinxsearch.generator.data_dir'),
            \Config::get('sphinxsearch.generator.logs_dir'),
        ];
        foreach ($dirs as $dir) {
            if ($dir && $this->files->exists($dir) === false) {
                $this->files->makeDirectory($dir, 0755, true);
            }
        }
    }
}