<?php
class InvokableClass
{
    public function __invoke()
    {
        return 'foo';
    }
}

class InvokableClassWithParams
{
    public function __invoke(array $params = [])
    {
        return $params;
    }
}
