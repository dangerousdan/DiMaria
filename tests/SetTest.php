<?php
class SetTest extends AbstractTest
{
    public function testSetValueCanBeSetAndFetched()
    {
        $this->di->set('foo', 10);
        $this->assertEquals(10, $this->di->get('foo'));
    }

    public function testSetValueCanBeOveridden()
    {
        $this->di->set('foo', 10);
        $this->di->set('foo', 4);
        $this->assertEquals(4, $this->di->get('foo'));
    }

    public function testSetValueCantBeFetchedByCreate()
    {
        $this->setExpectedException('DD\DiMaria\Exception\ContainerException', 'Class foo does not exist');
        $this->di->set('foo', 10);
        $this->assertEquals(10, $this->di->create('foo'));
    }

    public function testSetValueCanBeFetchedByCreateIfMarkedAsShared()
    {
        $this->di->set('foo', 10);
        $this->di->setShared('foo');
        $this->assertEquals(10, $this->di->create('foo'));
    }
}
