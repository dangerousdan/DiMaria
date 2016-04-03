<?php
namespace DD\DiMaria\Exception;

use Interop\Container\Exception\NotFoundException as InteropNotFoundException;

class NotFoundException extends \Exception implements InteropNotFoundException {}
