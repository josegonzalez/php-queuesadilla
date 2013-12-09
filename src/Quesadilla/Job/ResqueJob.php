<?php

namespace Queuesadilla\Job;

use \Queuesadilla\Job;

class ResqueJob extends Job {

  public function delete() {
  }

  public function attempts() {
    if (array_key_exists('attempts', $this->_item)) {
      return $this->_item['attempts'];
    }

    return $this->_item['attempts'] = 0;
  }

}
