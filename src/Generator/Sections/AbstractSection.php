<?php

namespace Iginikolaev\SphinxSearch\Generator\Sections;

use Illuminate\Support\Arr;

abstract class AbstractSection
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string|AbstractSection
     */
    protected $parent;

    /**
     * @var array
     */
    protected $params = [];

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        $result = get_object_vars($this);
        unset($result['config']);

        return $result;
    }

    /**
     * @return string
     */
    abstract protected function getType();

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = (string)$name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string|AbstractSection $parent
     *
     * @return $this
     */
    public function setParent($parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @return string
     */
    public function getParentName()
    {
        $parent = $this->getParent();
        if ($parent instanceof AbstractSection) {
            $parent = $parent->getName();
        }

        return $parent;
    }

    /**
     * @param array $params
     *
     * @return $this
     */
    public function setParams(array $params)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * @return string
     */
    public function generate()
    {
        $return = $this->generateSectionName() . ' {' . PHP_EOL;

        foreach ($this->params as $name => $values) {
            $values = (array)$values;
            foreach ($values as $value) {
                if ($value === null) {
                    continue;
                }
                if (strpos($value, "\n") !== false) {
                    $value = preg_replace('~[\r\n]+~ui', " \\\n", trim($value));
                }

                $return .= '    ' . $name . ' = ' . $value . PHP_EOL;
            }
        }

        $return .= '}';

        return $return;
    }

    /**
     * @return string
     */
    private function generateSectionName()
    {
        $return = (string)$this->getType();
        if ($name = $this->getName()) {
            $return .= ' ' . $name;
            if ($parent = $this->getParentName()) {
                $return .= ':' . $parent;
            }
        }

        return $return;
    }
}