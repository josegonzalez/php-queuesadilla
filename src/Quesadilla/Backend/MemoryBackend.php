<?php

namespace Queuesadilla\Backend;

use \Queuesadilla\Backend;

class MemoryBackend extends Backend {

  protected $_queue = array();

  public function push($class, $vars = array(), $queue = null) {
    $this->_push(compact('class', 'vars'), $queue);
  }

  public function release($item, $queue = null) {
    $this->_push($item, $queue);
  }

  public function pop($queue = null) {
    if ($queue === null) {
      $queue = 'default';
    }

    $item = array_shift($this->_queue);
    if (!$item) {
      return null;
    }

    return $item;
  }

  protected function _push($item, $queue = null) {
    if ($queue === null) {
      $queue = 'default';
    }

    array_push($this->_queue, $item);
  }

}
