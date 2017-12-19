<?php


namespace Sledium\Traits;

use Sledium\Container;

trait ContainerAwareTrait
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @param Container $container
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @return Container
     */
    public function getContainer(): Container
    {
        if (null === $this->container) {
            $this->container = Container::getInstance();
        }
        return $this->container;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function get($name)
    {
        return $this->getContainer()->get($name);
    }

    /**
     * @param $name
     * @return bool
     */
    public function has($name)
    {
        return $this->getContainer()->has($name);
    }
}
