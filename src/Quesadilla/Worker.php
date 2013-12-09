<?php
class Worker {

  public function __construct($backend, $params = array()) {
    $params = array_merge(array(
      'max_iterations' => null,
      'queue' => null,
    ), $params);

    $this->_backend = $backend;
    $this->_queue = $params['queue'];
    $this->_max_iterations = $params['max_iterations'];
  }

  public function log($message) {
    printf("[%s Worker] %s\n", str_replace('Backend', '', get_class($this->_backend)), $message);
  }

  public function work() {
    $this->log(sprintf('Starting worker%s', ($this->_max_iterations ? sprintf(', max iterations %s', $this->_max_iterations) : '')));
    $jobClass = $this->_backend->getJobClass();
    $iterations = 0;
    while (true) {
      if (is_int($this->_max_iterations) && $iterations >= $this->_max_iterations) {
        $this->log('Max iterations reached, exiting');
        break;
      }

      $iterations++;

      $item = $this->_backend->pop($this->_queue);
      if (empty($item)) {
        sleep(1);
        $this->log('No job!');
        continue;
      }

      $success = false;
      if (is_callable($item['class'])) {
        $job = new $jobClass($item, $this->_backend);
        try {
          call_user_func($item['class'], $job);
          $success = true;
        } catch (Exception $e) {
          $this->log(sprintf('Exception! %s', $e->getMessage()));
        }
      } else {
        $this->log('Invalid callable for job!');
      }

      if ($success) {
        $this->log('Success!');
      } else {
        $this->log('Failed!');
      }
    }
  }

}
