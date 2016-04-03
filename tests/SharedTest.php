<?php
use DD\DiMaria as DiMaria;

class SharedTest extends AbstractTest
{
    public function testSetSharedInstanceCreatesSameInstanceOfClass()
    {
        $this->di->setShared(TV::class);
        $tv1 = $this->di->create(TV::class);
        $tv2 = $this->di->create(TV::class);
        $tv2->inches = 40;
        $this->assertEquals($tv1, $tv2);
    }

    public function testClassIsNewInstanceWhenSharedIsOff()
    {
        $tv1 = $this->di->create(TV::class);
        $tv2 = $this->di->create(TV::class);
        $tv2->inches = 40;
        $this->assertNotEquals($tv1, $tv2);
    }

    public function testAliasCanBeShared()
    {
        $this->di->setAlias('LargeTV', TV::class, ['inches' => 55]);
        $this->di->setShared('LargeTV');
        $tv1 = $this->di->create('LargeTV');
        $tv2 = $this->di->create('LargeTV');
        $tv2->inches = 40;
        $this->assertEquals($tv1, $tv2);
    }

    public function testSharedAliasesCanBeDifferent()
    {
        $this->di->setAlias('LargeTV', TV::class, ['inches' => 55]);
        $this->di->setShared('LargeTV');
        $this->di->setAlias('SmallTV', TV::class, ['inches' => 21]);
        $this->di->setShared('SmallTV');
        $tvLarge = $this->di->create('LargeTV');
        $tvSmall = $this->di->create('SmallTV');
        $tv = $this->di->get(TV::class);
        $tvLarge->inches = 40;
        $this->assertNotEquals($tvLarge, $tvSmall);
        $this->assertNotEquals($tvLarge, $tv);
    }
}
