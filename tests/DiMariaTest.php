<?php
use DD\DiMaria as DiMaria;

class DiMariaTest extends PHPUnit_Framework_TestCase
{
    protected $di;

    protected function setUp()
    {
        parent::setUp();
        $this->di = new DiMaria;
    }

    protected function tearDown()
    {
        $this->di = null;
        parent::tearDown();
    }

    public function testObjectCanBeConstructed()
    {
        $this->assertEquals(DiMaria::class, get_class($this->di));
    }

    public function testCreateAnObjectWhenTheresNoRulesAndNoParams()
    {
        $sink = $this->di->get(Sink::class);
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
        $this->setExpectedException('\Exception', 'Required parameter $batteries is missing');
        $tvRemote = $this->di->get(TVRemote::class);
    }

    public function testCreateAnObjectWhenTheresMissingRulesForNonOptionalParamsThrowsAnException()
    {
        $this->setExpectedException('\Exception', 'Required parameter $batteries is missing');
        $this->di->setParams(TVRemote::class, ['buttons' => 48]);
        $tvRemote = $this->di->get(TVRemote::class);
    }

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

    public function testPreferencesCanBeOverriddenAtRuntime()
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

    public function testSetSharedInstanceCreatesSameInstanceOfClass()
    {
        $this->di->setShared(TV::class);
        $tv1 = $this->di->get(TV::class);
        $tv2 = $this->di->get(TV::class);
        $tv2->inches = 40;
        $this->assertEquals($tv1, $tv2);
    }

    public function testClassIsNewInstanceWhenSharedIsOff()
    {
        $tv1 = $this->di->get(TV::class);
        $tv2 = $this->di->get(TV::class);
        $tv2->inches = 40;
        $this->assertNotEquals($tv1, $tv2);
    }

    public function testSharedInstanceCanBeTurnedOff()
    {
        $this->di->setShared(TV::class);
        $this->di->setShared(TV::class, false);
        $tv1 = $this->di->get(TV::class);
        $tv2 = $this->di->get(TV::class);
        $tv2->inches = 40;
        $this->assertNotEquals($tv1, $tv2);
    }

    public function testAliasCanBeShared()
    {
        $this->di->setAlias('LargeTV', TV::class, ['inches' => 55]);
        $this->di->setShared('LargeTV');
        $tv1 = $this->di->get('LargeTV');
        $tv2 = $this->di->get('LargeTV');
        $tv2->inches = 40;
        $this->assertEquals($tv1, $tv2);
    }

    public function testSharedAliasesCanBeDifferent()
    {
        $this->di->setAlias('LargeTV', TV::class, ['inches' => 55]);
        $this->di->setShared('LargeTV');
        $this->di->setAlias('SmallTV', TV::class, ['inches' => 21]);
        $this->di->setShared('SmallTV');
        $tvLarge = $this->di->get('LargeTV');
        $tvSmall = $this->di->get('SmallTV');
        $tv = $this->di->get(TV::class);
        $tvLarge->inches = 40;
        $this->assertNotEquals($tvLarge, $tvSmall);
        $this->assertNotEquals($tvLarge, $tv);
    }

    public function testPreferencesCanPointToCompletelyDifferentClass()
    {
        $this->di->setAlias(TV::class, Sink::class);
        $tv = $this->di->get(TV::class);
        $this->assertEquals(Sink::class, get_class($tv));
    }

    public function testSetConfigCanSetConfigForMultipleClasses()
    {
        $this->di->setRules([
            'aliases' => [
                'LargeTV' => [TV::class],
                'SmallTV' => [
                    TV::class, [
                        'inches' => 12
                    ]
                ]
            ],
            'params' => [
                TV::class => [
                    'inches' => 55
                ]
            ],
            'shared' => [
                'LargeTV' => true,
                'SmallTV' => false
            ]
        ]);

        $tv = $this->di->get(TV::class);
        $tvLarge1 = $this->di->get('LargeTV');
        $tvLarge2 = $this->di->get('LargeTV');
        $tvSmall1 = $this->di->get('SmallTV');
        $tvSmall2 = $this->di->get('SmallTV');

        $tvLarge2->inches = 100;
        $tvSmall2->inches = 1;

        $this->assertEquals(55, $tv->inches);
        $this->assertEquals(TV::class, get_class($tvLarge1));
        $this->assertEquals(12, $tvSmall1->inches);

        $this->assertEquals($tvLarge1->inches, $tvLarge2->inches);
        $this->assertNotEquals($tvSmall1->inches, $tvSmall2->inches);
    }

    public function testClassesWithVariadicParamsCanBeCalled()
    {
        $this->di->setParams(Variadic::class, [
            'a' => 1,
            'b' => [2, 3, 4]
        ]);
        $var = $this->di->get(Variadic::class);

        $this->assertEquals(1, $var->a);
        $this->assertEquals([2, 3, 4], $var->b);
    }

    public function testClassesWithVariadicParamsCanBeLeftEmpty()
    {
        $this->di->setParams(Variadic::class, [
            'a' => 1,
        ]);
        $var = $this->di->get(Variadic::class);

        $this->assertEquals(1, $var->a);
        $this->assertEquals([null], $var->b);
    }

    public function testClassesWithTypehintedVariadicParamsCanBeCalled()
    {
        $this->di->setParams(VariadicWithTypeHinting::class, [
            'a' => 1,
            'b' => [['instanceOf' => 'TV']]
        ]);
        $var = $this->di->get(VariadicWithTypeHinting::class);

        $this->assertEquals(1, $var->a);
        $this->assertEquals(TV::class, get_class($var->b[0]));
    }

    public function testClassesWithTypehintedVariadicParamsCanBeCalledWithMultipleParams()
    {
        $this->di->setParams(VariadicWithTypeHinting::class, [
            'a' => 1,
            'b' => [['instanceOf' => 'TV'], ['instanceOf' => 'TV']]
        ]);
        $var = $this->di->get(VariadicWithTypeHinting::class);

        $this->assertEquals(1, $var->a);
        $this->assertEquals(TV::class, get_class($var->b[0]));
        $this->assertEquals(TV::class, get_class($var->b[1]));
    }

    public function testAliasesCanBePassedToVariadicFunctions()
    {
        $this->di->setAlias('LargeTV', TV::class,['inches' => 55]);

        $this->di->setParams(VariadicWithTypeHinting::class, [
            'a' => 1,
            'b' => [['instanceOf' => 'LargeTV'], ['instanceOf' => 'TV']]
        ]);
        $var = $this->di->get(VariadicWithTypeHinting::class);

        $this->assertEquals(1, $var->a);
        $this->assertEquals(TV::class, get_class($var->b[0]));
        $this->assertEquals(TV::class, get_class($var->b[1]));
        $this->assertEquals(55, $var->b[0]->inches);
        $this->assertEquals(32, $var->b[1]->inches);
    }

    public function testVariadicParametersCanBeSetAtObjectInstantiation()
    {
        $this->di->setParams(Variadic::class, [
            'a' => 1,
        ]);
        $var = $this->di->get(Variadic::class, ['b' => [2,3,4]]);

        $this->assertEquals(1, $var->a);
        $this->assertEquals([2, 3, 4], $var->b);
    }
}
