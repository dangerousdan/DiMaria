<?php
class FactoryTest extends AbstractTest
{
    public function testSetFactoryInvokesTheCallable()
    {
        $this->di->setFactory('foo', function() {
            return 'bar';
        });
        $this->assertEquals('bar', $this->di->get('foo'));
    }

    public function testSetFactoryReceivesParameters()
    {
        $this->di->setFactory('foo', function($params) {
            return 'bar-' . implode(',', $params);
        });
        $this->assertEquals('bar-1,2', $this->di->get('foo', ['a' => 1, 'b' => 2]));
    }

    public function testSetFactoryCanUseUseStatement()
    {
        $bar = 'baz';
        $this->di->setFactory('foo', function() use ($bar) {
            return 'bar' . $bar;
        });
        $this->assertEquals('barbaz', $this->di->get('foo'));
    }

    public function testSetFactoryOverridesCurrentValue()
    {
        $sink1 = $this->di->create('Sink');
        $this->di->setFactory('Sink', function() {
            return 'bar';
        });
        $sink2 = $this->di->create('Sink');
        $this->assertInstanceOf('Sink', $sink1);
        $this->assertEquals('bar', $sink2);
    }

    public function testSetFactoryIsSharedWhenGetIsUsed()
    {
        $sink1 = $this->di->get('Sink');
        $this->di->setFactory('Sink', function() {
            return 'bar';
        });
        $sink2 = $this->di->get('Sink');
        $this->assertInstanceOf('Sink', $sink1);
        $this->assertInstanceOf('Sink', $sink2);
    }

    public function testSetFactorySharedCanBeOverwritten()
    {
        $this->di->setShared('Sink', false);
        $sink1 = $this->di->get('Sink');
        $this->di->setFactory('Sink', function() {
            return 'bar';
        });
        $sink2 = $this->di->get('Sink');
        $this->assertInstanceOf('Sink', $sink1);
        $this->assertEquals('bar', $sink2);
    }

    public function testSetFactoryCanHandleAnInvokableClass()
    {
        $this->di->setFactory('Foo', $this->di->get('InvokableClass'));
        $result = $this->di->create('Foo');
        $this->assertEquals('foo', $result);
    }

    public function testSetFactoryCanHandleAnInvokableClassWithParameters()
    {
        $this->di->setFactory('Foo', $this->di->get('InvokableClassWithParams'));
        $result1 = $this->di->create('Foo');
        $result2 = $this->di->create('Foo', ['foo' => 'bar', 'baz' => 'taz']);
        $this->assertEquals([], $result1);
        $this->assertEquals(['foo' => 'bar', 'baz' => 'taz'], $result2);
    }
}
