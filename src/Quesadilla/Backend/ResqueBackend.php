<?php

class ResqueBackend extends Backend {

  protected $_redis = null;

  public function getJobClass() {
    return 'ResqueJob';
  }

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

    $item = $this->_datasource()->lpop('queue:' . $queue);
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

    $this->_datasource()->sadd('queues', $queue);
    $this->_datasource()->rpush('queue:' . $queue, json_encode($item));
  }

  protected function _datasource() {
    if (!$this->_redis) {
      $this->_redis = new Redis();
      $this->_redis->connect('127.0.0.1', 6379);
    }

    return $this->_redis;
  }

}
