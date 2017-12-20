<?php


namespace Sledium\Tests;

use PHPUnit\Framework\TestCase;
use Sledium\Container;
use Sledium\Traits\ContainerAwareTrait;

class ContainerAwareTest extends TestCase
{
    /**
     * @test
     */
    public function containerAwareShouldWorks()
    {
        $container = new Container(__DIR__);
        $object = (new class
        {
            use ContainerAwareTrait;
        });
        $this->assertFalse($container->has('abc'));
        $this->assertFalse($object->has('abc'));

        $container['abc'] = function () {
            return "abc";
        };

        $object->setContainer($container);

        $this->assertTrue($container->has('abc'));
        $this->assertTrue($object->has('abc'));

        $this->assertEquals($container->get('abc'), $object->get('abc'));
    }
}
