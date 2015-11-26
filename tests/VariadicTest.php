<?php
use DD\DiMaria as DiMaria;

class VariadicTest extends AbstractTest
{
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
        $this->assertEquals([], $var->b);
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

    public function testClassesWithVariadicParamsCanBeLeftEmptyWhenTypeHinted()
    {
        $this->di->setParams(VariadicWithTypeHinting::class, [
            'a' => 1,
        ]);
        $var = $this->di->get(VariadicWithTypeHinting::class);

        $this->assertEquals(1, $var->a);
        $this->assertEquals([], $var->b);
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
