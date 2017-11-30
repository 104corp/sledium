<?php


namespace Sledium;

use Illuminate\Contracts\Config\Repository as ConfigContract;
use Illuminate\Support\Collection;

class Config implements \ArrayAccess, ConfigContract
{
    /** @var string */
    private $configPath;

    /** @var array */
    protected $data = [];

    /**
     * Config constructor.
     * @param string $configPath config path
     */
    public function __construct(string $configPath)
    {
        $this->configPath = $configPath;
        if (false === ($realPath = realpath($configPath)) || !is_dir($realPath)) {
            throw new \InvalidArgumentException("'$configPath' not found or it is not dir");
        }
        $this->loadAll();
    }

    /**
     * Determine if the given configuration value exists.
     *
     * @param  string $key
     * @return bool
     */
    public function has($key)
    {
        if (false === ($pos = strpos($key, '.'))) {
            return isset($this->data[$key]);
        }
        $array = $this->data;
        foreach (explode('.', $key) as $segment) {
            if ((is_array($array) || $array instanceof \ArrayAccess) && isset($array[$segment])) {
                $array = $array[$segment];
            } else {
                return false;
            }
        }
        return true;
    }

    /**
     * Get the specified configuration value.
     *
     * @param  array|string $key
     * @param  mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (false === strpos($key, '.')) {
            return $this->data[$key] ?? $default;
        }
        $array = $this->data;
        foreach (explode('.', $key) as $segment) {
            if ((is_array($array) || $array instanceof \ArrayAccess) && isset($array[$segment])) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }
        return $array;
    }

    /**
     * Get all of the configuration items for the application.
     *
     * @return array
     */
    public function all()
    {
        return $this->data;
    }

    /**
     * Set a given configuration value.
     *
     * @param  array|string $key
     * @param  mixed $value
     * @return void
     */
    public function set($key, $value = null)
    {
        $keys = is_array($key) ? $key : [$key => $value];

        foreach ($keys as $key => $value) {
            $this->setToData($key, $value);
        }
    }

    /**
     * Prepend a value onto an array configuration value.
     *
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    public function prepend($key, $value)
    {
        $array = $this->get($key);
        array_unshift($array, $value);
        $this->set($key, $array);
    }

    /**
     * Push a value onto an array configuration value.
     *
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    public function push($key, $value)
    {
        $array = $this->get($key);
        $array[] = $value;
        $this->set($key, $array);
    }


    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }


    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }


    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        $this->set($offset, null);
    }

    private function loadAll()
    {
        $this->data = [];
        $dir = dir($this->configPath);
        while (false !== ($entry = $dir->read())) {
            if (is_file($fileName = $dir->path . DIRECTORY_SEPARATOR . $entry)) {
                $fileNameInfo = explode('.', $entry);
                if (isset($fileNameInfo[1]) && 'php' === $fileNameInfo[1]) {
                    $this->data[$fileNameInfo[0]] = $this->loadConfig($fileName);
                }
            }
        }
    }

    /**
     * @param string $fileName
     * @return Collection|null
     */
    private function loadConfig(string $fileName)
    {
        $conf = include $fileName;
        return is_array($conf) || $conf instanceof \ArrayAccess ? new Collection($conf) : null;
    }

    /**
     * @param $key string
     * @param $value mixed
     */
    private function setToData(string $key, $value)
    {
        $indexStack = explode('.', $key);
        $array = &$this->data;
        while (count($indexStack) > 1) {
            $index = array_shift($indexStack);
            if (!isset($array[$index]) || !is_array($array[$index])) {
                $array[$index] = [];
            }
            $array = &$array[$index];
        }
        $index = array_shift($indexStack);
        $array[$index] = $value;
    }
}
