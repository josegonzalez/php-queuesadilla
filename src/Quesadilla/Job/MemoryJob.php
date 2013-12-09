<?php

namespace Queuesadilla\Job;

use \Queuesadilla\Job;

class MemoryJob extends Job {

  public function delete() {
    throw new \LogicException("Job deletion unimplemented");
  }

  public function attempts() {
    if (array_key_exists('attempts', $this->_item)) {
      return $this->_item['attempts'];
    }

    return $this->_item['attempts'] = 0;
  }

  public function release($delay = 0) {
    $this->_item['attempts'] += 1;
    $this->_container->release($this->_item);
  }

}
