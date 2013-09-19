<?php

abstract class Backend {

  public function bulk($jobs, $vars = array(), $queue = null) {
    foreach ((array)$jobs as $callable) {
      $this->push($callable, $vars, $queue);
    }
  }

  abstract public function getJobClass();

  abstract public function push($callable, $vars = array(), $queue = null);

  abstract public function pop($queue = null);

}
