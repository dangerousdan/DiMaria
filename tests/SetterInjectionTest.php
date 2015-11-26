<?php
use DD\DiMaria as DiMaria;
use SetterInjectionTestClasses as Test;

class SetterInjectionTest extends AbstractTest
{
    public function testSetterInjectionCallsMethod()
    {
        $id = 12;
        $this->di->setInjection(Test\Entity::class, 'setId', ['id' => $id]);
        $entity = $this->di->get(Test\Entity::class);

        $this->assertEquals(Test\Entity::class, get_class($entity));
        $this->assertEquals($id, $entity->id);
    }

    public function testSetterInjectionCallsMultipleMethods()
    {
        $id = 12;
        $userId = 15;
        $this->di->setInjection(Test\Entity::class, 'setId', ['id' => $id]);
        $this->di->setInjection(Test\Entity::class, 'setUserId', ['userId' => $userId]);
        $entity = $this->di->get(Test\Entity::class);

        $this->assertEquals(Test\Entity::class, get_class($entity));
        $this->assertEquals($id, $entity->id);
        $this->assertEquals($userId, $entity->userId);
    }

    public function testSetterInjectionCanBeAppliedMultipleTimes()
    {
        $loggers = ['foo', 'bar'];
        $this->di->setInjection(Test\Logger::class, 'addLogger', ['logger' => $loggers[0]]);
        $this->di->setInjection(Test\Logger::class, 'addLogger', ['logger' => $loggers[1]]);
        $logger = $this->di->get(Test\Logger::class);

        $this->assertEquals(Test\Logger::class, get_class($logger));
        $this->assertEquals($loggers, $logger->loggers);
    }

    public function testSetterInjectionCanBeAppliedToAlias()
    {
        $id = 12;
        $this->di->setInjection('Alias', 'setId', ['id' => $id]);
        $this->di->setAlias('Alias', Test\Entity::class);
        $entity = $this->di->get('Alias');

        $this->assertEquals(Test\Entity::class, get_class($entity));
        $this->assertEquals($id, $entity->id);
    }

    public function testSetterInjectionCanBeAppliedWithoutParameters()
    {
        $this->di->setInjection(Test\Foo::class, 'doSomething');
        $foo = $this->di->get(Test\Foo::class);
        $this->assertTrue($foo->somethingHappened);
    }

    public function testSetterInjectionCanInjectClassesUsingInstanceOf()
    {
        $this->di->setInjection(Test\Logger::class, 'addLogger', ['logger' => ['instanceOf' => Test\Entity::class]]);
        $logger = $this->di->get(Test\Logger::class);
        $this->assertEquals(Test\Entity::class, get_class($logger->loggers[0]));
    }

    public function testSetterInjectionCanInjectClassesAutomaticallyWhenTypeHinted()
    {
        $this->di->setInjection(Test\Bar::class, 'setEntity');
        $bar = $this->di->get(Test\Bar::class);
        $this->assertEquals(Test\Entity::class, get_class($bar->entity));
    }

    public function testSetterInjectionCanInjectClassesUsingInstanceOfWhenTypeHinted()
    {
        $this->di->setInjection(Test\Bar::class, 'setEntity', ['entity' => ['instanceOf' => Test\Entity::class]]);
        $bar = $this->di->get(Test\Bar::class);
        $this->assertEquals(Test\Entity::class, get_class($bar->entity));
    }

    public function testSetterInjectionCanInjectAliases()
    {
        $this->di->setAlias('E', Test\Entity::class);
        $this->di->setInjection(Test\Bar::class, 'setEntity', ['entity' => ['instanceOf' => 'E']]);
        $bar = $this->di->get(Test\Bar::class);
        $this->assertEquals(Test\Entity::class, get_class($bar->entity));
    }

    public function testSetterInjectionCanInjectVariadicParameters()
    {
        $this->di->setInjection(Test\Baz::class, 'setBazzles', [
            'bazzles' => [1,2,4]
        ]);
        $baz = $this->di->get(Test\Baz::class);
        $this->assertEquals([1,2,4], $baz->bazzles);
    }

    public function testSetterInjectionCanInjectTypeHintedVariadicParameters()
    {
        $this->di->setInjection(Test\Bazz::class, 'setBazzles', [
            'bazzles' => [
                ['instanceOf' => Test\Baz::class],
                ['instanceOf' => Test\Baz::class],
            ]
        ]);
        $bazz = $this->di->get(Test\Bazz::class);
        $this->assertEquals(Test\Baz::class, get_class($bazz->bazzles[0]));
        $this->assertEquals(Test\Baz::class, get_class($bazz->bazzles[1]));
        $this->assertEquals(2, count($bazz->bazzles));
    }

    public function testSetterInjectionCanHaveEmptyVariadicParameters()
    {
        $this->di->setInjection(Test\Baz::class, 'setBazzles');
        $baz = $this->di->get(Test\Baz::class);
        $this->assertEquals([], $baz->bazzles);
    }

    public function testSetterInjectionCanHaveEmptyVariadicParametersWhenTypeHinted()
    {
        $this->di->setInjection(Test\Bazz::class, 'setBazzles');
        $bazz = $this->di->get(Test\Bazz::class);
        $this->assertEquals([], $bazz->bazzles);
    }

    public function testSetterInjectionOnlyAppliedToAnAliasIsntAppliedWhenClassCreatedBySomethingElse()
    {
        $this->di->setAlias('Foo1', Test\Foo::class);
        $this->di->setAlias('Foo2', Test\Foo::class);
        $this->di->setInjection('Foo1', 'doSomething');
        $this->di->setInjection('Foo2', 'doSomethingElse');
        $foo = $this->di->get(Test\Foo::class);
        $foo1 = $this->di->get('Foo1');
        $foo2 = $this->di->get('Foo2');
        $this->assertTrue($foo1->somethingHappened);
        $this->assertFalse($foo1->somethingElseHappened);

        $this->assertFalse($foo2->somethingHappened);
        $this->assertTrue($foo2->somethingElseHappened);

        $this->assertFalse($foo->somethingHappened);
        $this->assertFalse($foo->somethingElseHappened);
    }

    public function testSetterInjectionAppliedToAClassDoesntAffectAliases()
    {
        $this->di->setAlias('Foo1', Test\Foo::class);
        $this->di->setInjection(Test\Foo::class, 'doSomething');
        $foo = $this->di->get(Test\Foo::class);
        $foo1 = $this->di->get('Foo1');

        $this->assertTrue($foo->somethingHappened);
        $this->assertFalse($foo1->somethingHappened);
    }
}
