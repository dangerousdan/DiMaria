<?php
namespace SetterInjectionTestClasses;

class Entity
{
    public $id;
    public $userId;

    public function setId($id)
    {
        $this->id = $id;
    }

    public function setUserId($userId)
    {
        $this->userId = $userId;
    }
}

class Logger
{
    public $loggers = [];

    public function addLogger($logger)
    {
        $this->loggers[] = $logger;
    }
}

class Foo
{
    public $somethingHappened = false;
    public $somethingElseHappened = false;

    public function doSomething()
    {
        $this->somethingHappened = true;
    }

    public function doSomethingElse()
    {
        $this->somethingElseHappened = true;
    }
}

class Bar
{
    public $entity;

    public function setEntity(Entity $entity)
    {
        $this->entity = $entity;
    }
}

class Baz
{
    public $bazzles = [];

    public function setBazzles(... $bazzles)
    {
        $this->bazzles = $bazzles;
    }
}

class Bazz
{
    public $bazzles = [];

    public function setBazzles(Baz ...$bazzles)
    {
        $this->bazzles = $bazzles;
    }
}
