<?php

namespace josegonzalez\Queuesadilla\Job;

use \josegonzalez\Queuesadilla\Job;

class PdoJob extends Job
{

    public function delete()
    {
        $this->backend->delete($this->item);
    }

    public function attempts()
    {
        if (array_key_exists('attempts', $this->item)) {
            return $this->item['attempts'];
        }

        return $this->item['attempts'] = 0;
    }
}
