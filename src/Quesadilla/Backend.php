<?php

namespace Queuesadilla;

abstract class Backend {

  public function bulk($jobs, $vars = array(), $queue = null) {
    foreach ((array)$jobs as $callable) {
      $this->push($callable, $vars, $queue);
    }
  }

  public function getJobClass() {
    $classname = get_class($this);

    if (preg_match('@\\\\([\w]+)$@', $classname, $matches)) {
        $classname = $matches[1];
    }

    return '\\Queuesadilla\\Job\\' . str_replace('Backend', 'Job', $classname);
  }

  abstract public function push($callable, $vars = array(), $queue = null);

  abstract public function pop($queue = null);

  abstract public function release($item, $queue = null);

}
