<?php

namespace josegonzalez\Queuesadilla\Backend;

use \josegonzalez\Queuesadilla\Backend;

class TestBackend extends Backend {

    public $return = true;

    protected function connect() {
        return $this->return;
    }

    public function delete($item) {
        return $this->return;
    }

    public function pop($queue = null) {
        return $this->return;
    }

    public function push($class, $vars = array(), $queue = null) {
        return $this->return;
    }

    public function release($item, $queue = null) {
        return $this->return;
    }
}