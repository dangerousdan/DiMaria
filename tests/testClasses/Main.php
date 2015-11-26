<?php
class Sink {}

class TV
{
    public $inches;

    public function __construct($inches = 32)
    {
        $this->inches = $inches;
    }
}

class TVRemote
{
    public $batteries;
    public $buttons;

    public function __construct($batteries, int $buttons = 12)
    {
        $this->batteries = $batteries;
        $this->buttons = $buttons;
    }
}

interface RoomInterface {}

class LivingRoom implements RoomInterface
{
    public $tv;
    public $tvRemote;

    public function __construct(TV $tv, TVRemote $tvRemote)
    {
        $this->tv = $tv;
        $this->tvRemote = $tvRemote;
    }
}

class Kitchen implements RoomInterface
{
    public $sink;

    public function __construct(Sink $sink)
    {
        $this->sink = $sink;
    }
}

class House
{
    public $livingRoom;
    public $kitchen;

    public function __construct(RoomInterface $livingRoom, RoomInterface $kitchen)
    {
        $this->livingRoom = $livingRoom;
        $this->kitchen = $kitchen;
    }
}

class Variadic
{
    public $a;
    public $b;

    public function __construct($a, ...$b)
    {
        $this->a = $a;
        $this->b = $b;
    }
}

class VariadicWithTypeHinting
{
    public $a;
    public $b;

    public function __construct($a, TV ...$b)
    {
        $this->a = $a;
        $this->b = $b;
    }
}
