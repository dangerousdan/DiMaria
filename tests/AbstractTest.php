<?php
use DD\DiMaria as DiMaria;

abstract class AbstractTest extends PHPUnit_Framework_TestCase
{
    protected $di;

    protected function getDi() : DiMaria
    {
        return $this->di;
    }

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
}
