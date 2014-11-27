<?php

namespace josegonzalez\Queuesadilla\Engine;

/**
 * Describes a queue engine
 */
interface EngineInterface
{

    public function bulk($jobs, $vars = [], $options = []);

    public function getJobClass();

    public function setting($settings, $key, $default = null);

    public function watch($options = []);

    public function connected();

    public function jobId();

    public function connect();

    public function delete($item);

    public function pop($options = []);

    public function push($class, $vars = [], $options = []);

    public function release($item, $options = []);
}
