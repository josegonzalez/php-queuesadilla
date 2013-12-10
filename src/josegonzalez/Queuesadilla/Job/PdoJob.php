<?php

namespace josegonzalez\Queuesadilla\Job;

use \josegonzalez\Queuesadilla\Job;

class PdoJob extends Job
{

    public function delete()
    {
        $this->_backend->delete($this->_item);
    }

    public function attempts()
    {
        if (array_key_exists('attempts', $this->_item)) {
            return $this->_item['attempts'];
        }

        return $this->_item['attempts'] = 0;
    }
}
