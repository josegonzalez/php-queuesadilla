<?php

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
  abstract public function release($delay = 0);

}
