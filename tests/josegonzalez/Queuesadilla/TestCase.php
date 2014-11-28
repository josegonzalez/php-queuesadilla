<?php

namespace josegonzalez\Queuesadilla;

use PHPUnit_Framework_TestCase;
use ReflectionClass;

class TestCase extends PHPUnit_Framework_TestCase
{
    public function testClass()
    {
        $this->markTestSkipped('No tests');
    }

    protected function protectedMethodCall(&$object, $methodName, array $parameters = [])
    {
        $reflection = new ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }
}
