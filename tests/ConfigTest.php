<?php


namespace Sledium\Tests;

use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use Sledium\Config;

class ConfigTest extends TestCase
{
    protected $testData = [
        'config1' => [
            'a1' => [
                'a1-1' => 123,
                'a1-2' => 321,
            ],
            'b1' => 'abc'
        ],
        'config2' => [
            'a1' => 'a1',
            'a2' => 'a2',
            'a3' => 'a3',
            'a4' => 'a4',
        ],
        'config3' => [

        ],
    ];

    protected $dir;

    public function setUp()
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ConfigTest';
        mkdir($this->dir, 0777, true);
        foreach ($this->testData as $filename => $value) {
            file_put_contents(
                $this->dir . DIRECTORY_SEPARATOR . $filename . '.php',
                "<?php\nreturn " . var_export($value, true) . ';'
            );
        }
    }

    public function tearDown()
    {
        exec('rm -rf ' . $this->dir);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function configHasShouldWork()
    {
        $config = new Config($this->dir);
        $this->assertTrue($config->has('config1'));
        $this->assertTrue($config->has('config1.a1.a1-1'));
        $this->assertTrue($config->has('config2'));
        $this->assertTrue($config->has('config2.a1'));
        $this->assertTrue($config->has('config3'));
        $this->assertFalse($config->has('config3.abc'));
    }


    /**
     * @test
     */
    public function configGetShouldWork()
    {
        $config = new Config($this->dir);
        $this->assertInstanceOf(Collection::class, $config->get('config1'));
        $this->assertEquals($this->testData['config1'], $config->get('config1')->toArray());
        $this->assertEquals($this->testData['config1']['a1']['a1-1'], $config->get('config1.a1.a1-1'));
        $this->assertEquals($this->testData['config2'], $config->get('config2')->toArray());
        $this->assertEquals($this->testData['config2']['a1'], $config->get('config2.a1'));
        $this->assertEquals($this->testData['config3'], $config->get('config3')->toArray());
        $this->assertNull($config->get('config3.abc'));
        $this->assertEquals('123', $config->get('config3.abc', '123'));
    }


    /**
     * @test
     */
    public function configArrayAccessShouldWork()
    {
        $config = new Config($this->dir);

        $this->assertTrue(isset($config['config1']));
        $this->assertTrue(isset($config['config1.a1.a1-1']));
        $this->assertEquals($this->testData['config1'], $config['config1']->toArray());

        $config['config1.a1.a1-1'] = 5678;
        $this->assertEquals(5678, $config['config1.a1.a1-1']);

        unset($config['config1.a1.a1-1']);
        $this->assertNull($config['config1.a1.a1-1']);

        $this->assertEquals($this->testData['config2'], $config['config2']->toArray());
        $this->assertEquals($this->testData['config2']['a1'], $config['config2.a1']);
        $this->assertEquals($this->testData['config3'], $config['config3']->toArray());
        $this->assertNull($config['config3.abc']);
    }

    /**
     * @test
     */
    public function prependShouldWork()
    {
        $config = new Config($this->dir);
        $value = ['abc' => '1234'];
        $config->prepend('config1', $value);
        $this->assertEquals($value, $config->get('config1')[0]);
        $config->prepend('config1.0', $value);
        $this->assertEquals($value, $config->get('config1')[0][0]);
        $this->expectException(\InvalidArgumentException::class);
        $config->prepend('config1.0.abc', $value);
    }

    /**
     * @test
     */
    public function pushShouldWork()
    {
        $config = new Config($this->dir);
        $value = ['abc' => '1234'];
        $config->push('config1', $value);
        $this->assertEquals($value, $config->get('config1')[0]);
        $config->push('config1.0', $value);
        $this->assertEquals($value, $config->get('config1')[0][0]);
        $this->expectException(\InvalidArgumentException::class);
        $config->push('config1.0.abc', $value);
    }

    /**
     * @test
     */
    public function getAll()
    {
        $config = new Config($this->dir);
        $all = $config->all();
        $hasAssert = false;
        foreach ($all as $key => $value) {
            $this->assertEquals($this->testData[$key], $value->toArray());
            $hasAssert = true;
        }
        $this->assertTrue($hasAssert);
    }

    /**
     * @test
     */
    public function notExistConfigFolder()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Config('not-exist');
    }
}
