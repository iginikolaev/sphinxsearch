<?php

namespace Iginikolaev\SphinxSearch;

use Iginikolaev\SphinxSearch\Generator\ConnectionSources;
use Iginikolaev\SphinxSearch\Generator\Exception;
use Iginikolaev\SphinxSearch\Generator\ModelGenerator;
use Iginikolaev\SphinxSearch\Generator\Sections\AbstractSection;
use Iginikolaev\SphinxSearch\Generator\Sections\IndexSection;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\ClassLoader\ClassMapGenerator;

class Generator
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var AbstractSection[]
     */
    protected $sections = [];

    /**
     * Generator constructor.
     */
    public function __construct()
    {
        $this->config = \Config::get('sphinxsearch');
    }

    /**
     * @return string
     * @throws Exception
     */
    public function generate()
    {
        // Collect all sections
        $this->collectConfigSections();
        $this->collectConfigIndexes();
        $this->collectIndexesAndSourcesFromModels();

        // Generate sections
        $content = $this->generateSections();

        return $content;
    }

    /**
     * Collect sections from config `generator.additional_sections`
     *
     * @throws Exception
     */
    private function collectConfigSections()
    {
        foreach (Arr::get($this->config, 'generator.additional_sections', []) as $name => $params) {
            $className = __NAMESPACE__ . '\Generator\Sections\\' . Str::studly($name) . 'Section';
            if (!class_exists($className)) {
                throw new Exception('Bad section ' . $name);
            }

            /* @var AbstractSection $section */
            $section = new $className($this->config);
            $section->setParams($params);

            $this->sections[] = $section;
        }
    }

    /**
     * Collect indexes from config `generator.additional_indexes`
     */
    private function collectConfigIndexes()
    {
        foreach (Arr::get($this->config, 'generator.additional_indexes', []) as $name => $params) {
            $index = new IndexSection($this->config);
            $index->setName($name)
                ->setParams($params);

            $this->sections[] = $index;
        }
    }

    /**
     * Collect models indexes
     */
    private function collectIndexesAndSourcesFromModels()
    {
        $models = $this->collectModels();

        $connectionSources = new ConnectionSources($this->config);

        $modelsSections = [];
        foreach ($models as $modelName) {
            $modelGenerator = new ModelGenerator($this->config, $connectionSources);
            $modelGenerator->generate($modelName);

            $modelsSections = array_merge($modelsSections, $modelGenerator->getSections());
        }

        // Append created connection source
        $this->sections = array_merge($this->sections, $connectionSources->getConnections());

        // Append models sections
        $this->sections = array_merge($this->sections, $modelsSections);
    }

    /**
     * Search models that are using `SphinxSearchable` trait
     *
     * @return array
     */
    private function collectModels()
    {
        $models = [];
        foreach (Arr::get($this->config, 'generator.model_locations', []) as $dir) {
            $dir = base_path($dir);
            if (!file_exists($dir)) {
                continue;
            }

            $files = ClassMapGenerator::createMap($dir);
            foreach ($files as $name => $path) {
                $classes = class_uses_recursive($name);
                if (isset($classes[SphinxSearchableGeneratable::class])) {
                    $models[] = $name;
                }
            }
        }

        return $models;
    }

    /**
     * Generate sections contents
     *
     * @return string
     */
    private function generateSections()
    {
        $content = '';
        foreach ($this->sections as $section) {
            $content .= $section->generate() . PHP_EOL . PHP_EOL;
        }

        return $content;
    }
}