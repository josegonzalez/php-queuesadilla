<?php

namespace Queuesadilla;

abstract class Job {

  protected $_item;

  protected $_backend;

  public function __construct($item, $backend) {
    $this->_item = $item;
    $this->_backend = $backend;
  }

  public function data($key, $default = null) {
    if (array_key_exists($key, $this->_item['vars'])) {
      return $this->_item['vars'][$key];
    }

    return $default;
  }

  abstract public function delete();
  abstract public function attempts();

  public function release($delay = 0) {
    if (!isset($this->_item['attempts'])) {
      $this->_item['attempts'] = 0;
    }

    $this->_item['attempts'] += 1;
    $this->_item['delay'] = $delay;
    $this->_backend->release($this->_item);
  }

}
