<?php
use DD\DiMaria as DiMaria;

class BasicTest extends AbstractTest
{
    public function testObjectCanBeConstructed()
    {
        $this->assertEquals(DiMaria::class, get_class($this->di));
    }

    public function testCreateAnObjectWhenTheresNoRulesAndNoParams()
    {
        $sink = $this->getDi()->get(Sink::class);
        $this->assertEquals(Sink::class, get_class($sink));
    }

    public function testCreateAnObjectWhenTheresNoRulesAndOptionalParams()
    {
        $tv = $this->di->get(TV::class);
        $this->assertEquals(TV::class, get_class($tv));
        $this->assertEquals(32, $tv->inches);
    }

    public function testCreateAnObjectWhenTheresNoRulesAndNonOptionalParamsThrowsAnException()
    {
        $this->setExpectedException('DD\DiMaria\Exception\ContainerException', 'Required parameter $batteries is missing');
        $tvRemote = $this->di->get(TVRemote::class);
    }

    public function testCreateAnObjectWhenTheresMissingRulesForNonOptionalParamsThrowsAnException()
    {
        $this->setExpectedException('DD\DiMaria\Exception\ContainerException', 'Required parameter $batteries is missing');
        $this->di->setParams(TVRemote::class, ['buttons' => 48]);
        $tvRemote = $this->di->get(TVRemote::class);
    }

    public function testDiMariaCanGetItself()
    {
        $di = $this->di->get('DD\DiMaria');
        $this->assertEquals($this->di, $di);
    }
}
