<?php

namespace josegonzalez\Queuesadilla;

use ReflectionClass;

class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function protectedMethodCall(&$object, $methodName, array $parameters = [])
    {
        $reflection = new ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
