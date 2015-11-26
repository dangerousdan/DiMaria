<?php
use DD\DiMaria as DiMaria;

class ParameterTest extends AbstractTest
{
    public function testConstructorParamsCanBeAppliedWhenCreated()
    {
        $tv = $this->di->get(TV::class, ['inches' => 42]);
        $this->assertEquals(TV::class, get_class($tv));
        $this->assertEquals(42, $tv->inches);

        $tvRemote = $this->di->get(TVRemote::class, ['batteries' => 'AA']);
        $this->assertEquals('AA', $tvRemote->batteries);
    }

    public function testExtraConstructorParamsAreIgnoredWhenCreated()
    {
        $tv = $this->di->get(TV::class, ['hasBanana' => true, 'inches' => 42]);
        $this->assertEquals(42, $tv->inches);
    }

    public function testConstructorParamsCanBePredefined()
    {
        $this->di->setParams(TV::class, ['inches' => 48]);
        $tv = $this->di->get(TV::class);
        $this->assertEquals(48, $tv->inches);
    }

    public function testParamsWhenCreatedOverwritePredefinedParams()
    {
        $this->di->setParams(TV::class, ['inches' => 36]);
        $tv = $this->di->get(TV::class, ['inches' => 40]);
        $this->assertEquals(40, $tv->inches);
    }

    public function testPredefinedParamsCanBeOverwrittenOnCreation()
    {
        $this->di->setParams(TV::class, ['inches' => 36]);
        $this->di->setParams(TV::class, ['inches' => 34]);
        $tv = $this->di->get(TV::class);
        $this->assertEquals(34, $tv->inches);

        $this->di->setParams(TVRemote::class, ['batteries' => 'AA', 'buttons' => 12]);
        $this->di->setParams(TVRemote::class, ['buttons' => 34]);
        $tvRemote = $this->di->get(TVRemote::class);
        $this->assertEquals('AA', $tvRemote->batteries);
        $this->assertEquals(34, $tvRemote->buttons);
    }

    public function testPredefinedParamsMergeWithCreationParams()
    {
        $this->di->setParams(TVRemote::class, ['buttons' => 28]);
        $tvRemote = $this->di->get(TVRemote::class, ['batteries' => 'AAA']);
        $this->assertEquals('AAA', $tvRemote->batteries);
        $this->assertEquals(28, $tvRemote->buttons);
    }

    public function testTypehintedClassIsAutomaticallyCreated()
    {
        $kitchen = $this->di->get(Kitchen::class);
        $this->assertEquals(Sink::class, get_class($kitchen->sink));

        $this->di->setParams(TVRemote::class, ['batteries' => 'AAA']);

        $livingRoom = $this->di->get(LivingRoom::class);
        $this->assertEquals(TV::class, get_class($livingRoom->tv));
        $this->assertEquals(TVRemote::class, get_class($livingRoom->tvRemote));
        $this->assertEquals('AAA', $livingRoom->tvRemote->batteries);
    }

    public function testClassCanBePassedAsParameter()
    {
        $this->di->setParams(TV::class, ['inches' => ['instanceOf' => 'Sink']]);
        $tv = $this->di->get(TV::class);
        $this->assertEquals(Sink::class, get_class($tv->inches));
    }
}
