<?php

namespace josegonzalez\Queuesadilla\Worker;

use \josegonzalez\Queuesadilla\Worker;

class SequentialWorker extends Worker
{
    public function work()
    {
        $max_iterations = $this->max_iterations ? sprintf(', max iterations %s', $this->max_iterations) : '';
        $this->log(sprintf('Starting worker%s', $max_iterations));
        $jobClass = $this->backend->getJobClass();
        $iterations = 0;
        if (!$this->backend->watch($this->queue)) {
            $this->log(sprintf('Worker unable to watch queue %s, exiting', $this->queue));
            return;
        }

        while (true) {
            if (is_int($this->max_iterations) && $iterations >= $this->max_iterations) {
                $this->log('Max iterations reached, exiting');
                break;
            }

            $iterations++;

            $item = $this->backend->pop($this->queue);
            if (empty($item)) {
                sleep(1);
                $this->log('No job!');
                continue;
            }

            $success = false;
            if (is_callable($item['class'])) {
                $job = new $jobClass($item, $this->backend);
                try {
                    call_user_func($item['class'], $job);
                    $success = true;
                } catch (\Exception $e) {
                    $this->log(sprintf('Exception: "%s"', $e->getMessage()));
                }
            } else {
                $this->log('Invalid callable for job. Deleting job from queue.');
                $this->backend->delete($item);
                continue;
            }

            if ($success) {
                $this->log('Success. Deleting job from queue.');
                $job->delete();
            } else {
                $this->log('Failed. Releasing job to queue');
                $job->release();
            }
        }
    }
}
