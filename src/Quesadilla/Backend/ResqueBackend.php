<?php

namespace Queuesadilla\Backend;

use \Queuesadilla\Backend;

class ResqueBackend extends Backend {

  protected $_connection = null;

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

    $item = $this->_connection()->lpop('queue:' . $queue);
    if (!$item) {
      return null;
    }

    $item = json_decode($item, true);
    return $item;
  }

  protected function _push($item, $queue = null) {
    if ($queue === null) {
      $queue = 'default';
    }

    $this->_connection()->sadd('queues', $queue);
    $this->_connection()->rpush('queue:' . $queue, json_encode($item));
  }

  protected function _connection() {
    if (!$this->_connection) {
      $this->_connection = new Redis();
      $this->_connection->connect('127.0.0.1', 6379);
    }

    return $this->_connection;
  }

}
