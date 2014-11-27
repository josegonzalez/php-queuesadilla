<?php

namespace josegonzalez\Queuesadilla\Engine;

/**
 * Describes a queue engine
 */
interface EngineInterface
{

    public function bulk($jobs, $vars = array(), $options = array());

    public function getJobClass();

    public function setting($settings, $key, $default = null);

    public function watch($options = array());

    public function connected();

    public function jobId();

    public function connect();

    public function delete($item);

    public function pop($options = array());

    public function push($class, $vars = array(), $options = array());

    public function release($item, $options = array());
}
