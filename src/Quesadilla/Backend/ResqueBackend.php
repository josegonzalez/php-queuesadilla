<?php

namespace Queuesadilla\Backend;

use \Redis;
use \Queuesadilla\Backend;

class ResqueBackend extends Backend {

  protected $_connection = null;

  protected $_baseConfig = array(
    'prefix' => null,
    'server' => '127.0.0.1',
    'port' => 6379,
    'password' => false,
    'timeout' => 0,
    'persistent' => true,
    'queue' => 'default',
  );

  protected $_settings = null;

  public function __construct($config = array()) {
    if (!class_exists('Redis')) {
      return false;
    }

    $this->_settings = array_merge($this->_baseConfig, $config);
    return $this->_connect();
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

    $item = $this->_connection()->lpop('queue:' . $queue);
    if (!$item) {
      return null;
    }

    return json_decode($item, true);
  }

  public function delete($item) {
  }

  protected function _push($item, $queue = null) {
    if ($queue === null) {
      $queue = $this->_settings['queue'];
    }

    $this->_connection()->sadd('queues', $queue);
    $this->_connection()->rpush('queue:' . $queue, json_encode($item));
  }

/**
 * Connects to a Redis server
 *
 * @return boolean True if Redis server was connected
 */
  protected function _connect() {
    $return = false;
    try {
      $this->_connection = new Redis();
      if (empty($this->_settings['persistent'])) {
        $return = $this->_connection->connect($this->_settings['server'], $this->_settings['port'], $this->_settings['timeout']);
      } else {
        $return = $this->_connection->pconnect($this->_settings['server'], $this->_settings['port'], $this->_settings['timeout']);
      }
    } catch (RedisException $e) {
      return false;
    }
    if ($return && $this->_settings['password']) {
      $return = $this->_connection->auth($this->_settings['password']);
    }
    return $return;
  }

}
