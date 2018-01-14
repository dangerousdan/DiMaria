<?php
use DD\DiMaria as DiMaria;
use Psr\Container\ContainerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class InterfaceTest extends AbstractTest
{
    public function testDiImplementsInteropContainerInterface()
    {
        $this->assertInstanceOf(ContainerInterface::class, $this->di);
    }

    public function testHasWillDetectAClass()
    {
        $this->assertTrue($this->di->has('Sink'));
    }

    public function testHasWillDetectAnAlias()
    {
        $this->di->setAlias('Sink2', 'Sink');
        $this->assertTrue($this->di->has('Sink2'));
    }

    public function testHasWillFailWhenNoClassExists()
    {
        $this->assertFalse($this->di->has('Sink2'));
    }

    public function testNotFoundExceptionIsThrownWhenClassDoesntExist()
    {
        $this->expectException(NotFoundExceptionInterface::class);
        $this->di->get('abcdefg');
    }

    public function testContainerExceptionIsThrownWhenAttemptingToCreateAnInterface()
    {
        $this->expectException(ContainerExceptionInterface::class);
        $this->di->get('RoomInterface');
    }

    public function testContainerExceptionIsThrownWhenParamsAreIncorrect()
    {
        $this->expectException(ContainerExceptionInterface::class);
        $this->di->get('TvRemote', ['batteries' => 2, 'buttons' => 'a']);
    }

    public function testContainerExceptionIsThrownWhenParamsAreMissing()
    {
        $this->expectException(ContainerExceptionInterface::class);
        $this->di->get('TvRemote');
    }
}
