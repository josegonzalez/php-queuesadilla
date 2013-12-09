<?php

class SynchronousBackend extends MemoryBackend {

  public function getJobClass() {
    return 'SynchronousJob';
  }

  public function push($class, $vars = array(), $queue = null) {
    parent::push($class, $vars, $queue);
    $worker = new Worker($this, array('max_iterations' => 1));
    $worker->work();
  }

}
