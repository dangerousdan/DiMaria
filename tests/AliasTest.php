<?php
use DD\DiMaria as DiMaria;

class AliasTest extends AbstractTest
{
    public function testCreatingAnAlias()
    {
        $this->di->setAlias('HugeTv', TV::class, ['inches' => 60]);
        $tv = $this->di->get('HugeTv');
        $this->assertEquals(TV::class, get_class($tv));
        $this->assertEquals(60, $tv->inches);
    }

    public function testAliasConfigIsOveriddenNotMerged()
    {
        $this->di->setAlias('TVRemote1', TVRemote::class, ['batteries' => 'AA', 'buttons' => 2]);
        $this->di->setAlias('TVRemote1', TVRemote::class, ['batteries' => 'AAA']);

        $tvRemote = $this->di->get('TVRemote1');
        $this->assertEquals('AAA', $tvRemote->batteries);
        $this->assertEquals(12, $tvRemote->buttons);
    }

    public function testAliasRulesAlsoInheritBaseRules()
    {
        $this->di->setAlias('HugeTvRemote', TVRemote::class, ['batteries' => 'AA']);
        $this->di->setParams(TVRemote::class, ['buttons' => 80]);
        $tvRemote = $this->di->get('HugeTvRemote');
        $this->assertEquals(80, $tvRemote->buttons);
        $this->assertEquals('AA', $tvRemote->batteries);
    }

    public function testCreatingAnAliasDoesntFuckUpNormalCreation()
    {
        $this->di->setAlias('HugeTv', TV::class, ['inches' => 60]);
        $tv = $this->di->get(TV::class);
        $this->assertEquals(TV::class, get_class($tv));
        $this->assertEquals(32, $tv->inches);
    }

    public function testOuterAliasPropertiesOverrideOthersInAliasInception()
    {
        $this->di->setAlias('HugeTvRemote', TVRemote::class, ['batteries' => 'AA', 'buttons' => 189]);
        $this->di->setAlias('AnotherTvRemote', 'HugeTvRemote', ['batteries' => 'AAA']);
        $this->di->setAlias('ReallyHugeTvRemote', 'AnotherTvRemote', ['buttons' => 218]);
        $tvRemote = $this->di->get('ReallyHugeTvRemote');
        $this->assertEquals(218, $tvRemote->buttons);
        $this->assertEquals('AAA', $tvRemote->batteries);
    }

    public function testParamsPassedAtCreationOverrideAliasAndClassParams()
    {
        $this->di->setParams(TV::class, ['inches' => 27]);
        $this->di->setAlias('TV28Inch', TV::class, ['inches' => 28]);
        $tv = $this->di->get('TV28Inch', ['inches' => 29]);
        $this->assertEquals(29, $tv->inches);
    }

    public function testPreferencesCanBeSet()
    {
        $this->di->setAlias(RoomInterface::class, Kitchen::class);
        $room = $this->di->get(RoomInterface::class);
        $this->assertEquals(Kitchen::class, get_class($room));
    }

    public function testDifferentPreferencesCanPointToAnAlias()
    {
        $this->di->setAlias('Room', Kitchen::class);
        $this->di->setAlias(RoomInterface::class, 'Room');

        $room = $this->di->get(RoomInterface::class);
        $this->assertEquals(Kitchen::class, get_class($room));
    }

    public function testPreferencesCanBeOverridden()
    {
        $this->di->setParams(TVRemote::class, ['batteries' => 'AAA']);
        $this->di->setAlias(RoomInterface::class, Kitchen::class);
        $this->di->setParams(House::class, ['livingRoom' => ['instanceOf' => LivingRoom::class]]);

        $house = $this->di->get(House::class);
        $this->assertEquals(Kitchen::class, get_class($house->kitchen));
        $this->assertEquals(LivingRoom::class, get_class($house->livingRoom));
    }

    public function testPreferencesCanBeOverriddenAtCreation()
    {
        $this->di->setParams(TVRemote::class, ['batteries' => 'AAA']);
        $this->di->setAlias(RoomInterface::class, Kitchen::class);

        $house = $this->di->get(House::class, ['livingRoom' => ['instanceOf' => LivingRoom::class]]);
        $this->assertEquals(Kitchen::class, get_class($house->kitchen));
        $this->assertEquals(LivingRoom::class, get_class($house->livingRoom));
    }

    public function testParameterConfigPersistsWhenPreferenceIsUsed()
    {
        $this->di->setParams(TV::class, ['inches' => 12]);
        $this->di->setParams(TVRemote::class, ['batteries' => 'AA']);

        $this->di->setAlias('LargeTV', TV::class, ['inches' => 50]);
        $this->di->setAlias('Room', LivingRoom::class);
        $this->di->setAlias(RoomInterface::class, 'Room');

        $room = $this->di->get(RoomInterface::class);
        $this->assertEquals('AA', $room->tvRemote->batteries);
        $this->assertEquals(12, $room->tv->inches);
    }

    public function testAliasConfigPersistsWhenPreferenceIsUsed()
    {
        $this->di->setParams(TV::class, ['inches' => 12]);
        $this->di->setParams(TVRemote::class, ['batteries' => 'AA']);

        $this->di->setAlias('LargeTV', TV::class, ['inches' => 50]);
        $this->di->setAlias('Room', LivingRoom::class, ['tv' => ['instanceOf' => 'LargeTV']]);
        $this->di->setAlias(RoomInterface::class, 'Room');

        $room = $this->di->get(RoomInterface::class);
        $this->assertEquals('AA', $room->tvRemote->batteries);
        $this->assertEquals(50, $room->tv->inches);
    }

    public function testPreferencesCanPointToCompletelyDifferentClass()
    {
        $this->di->setAlias(TV::class, Sink::class);
        $tv = $this->di->get(TV::class);
        $this->assertEquals(Sink::class, get_class($tv));
    }
}
