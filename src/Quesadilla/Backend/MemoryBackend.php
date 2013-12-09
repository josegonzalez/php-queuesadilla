<?php

namespace Queuesadilla\Backend;

use \Queuesadilla\Backend;

class MemoryBackend extends Backend {

  protected $_baseConfig = array(
    'queue' => 'default',
  );

  protected $_queue = array();

  protected $_settings = null;

  public function __construct($config = array()) {
    return true;
  }

  public function push($class, $vars = array(), $queue = null) {
    $this->_push(compact('class', 'vars'), $queue);
  }

  public function release($item, $queue = null) {
    $this->_push($item, $queue);
  }

  public function pop($queue = null) {
    if ($queue === null) {
      $queue = $this->_settings['queue'];
    }

    $item = array_shift($this->_queue);
    if (!$item) {
      return null;
    }

    return $item;
  }

  public function delete($item) {
  }

  protected function _push($item, $queue = null) {
    if ($queue === null) {
      $queue = $this->_settings['queue'];
    }

    array_push($this->_queue, $item);
  }

}
