<?php

namespace josegonzalez\Queuesadilla\Worker;

use \josegonzalez\Queuesadilla\Worker;

class SequentialWorker extends Worker
{
    public function work()
    {
        $max_iterations = $this->max_iterations ? sprintf(', max iterations %s', $this->max_iterations) : '';
        $this->log(sprintf('Starting worker%s', $max_iterations));
        $jobClass = $this->engine->getJobClass();
        $iterations = 0;
        if (!$this->engine->connected()) {
            $this->log(sprintf('Worker unable to connect, exiting'));
            return;
        }

        while (true) {
            if (is_int($this->max_iterations) && $iterations >= $this->max_iterations) {
                $this->log('Max iterations reached, exiting');
                break;
            }

            $iterations++;

            $item = $this->engine->pop($this->queue);
            if (empty($item)) {
                sleep(1);
                $this->log('No job!');
                continue;
            }

            $success = false;
            if (is_callable($item['class'])) {
                $job = new $jobClass($item, $this->engine);
                try {
                    if (is_array($item['class']) && count($item['class']) == 2) {
                        $item['class'][0] = new $item['class'][0];
                    }

                    $success = $item['class']($job);
                    if ($success !== false) {
                        $success = true;
                    }
                } catch (\Exception $e) {
                    $this->log(sprintf('Exception: "%s"', $e->getMessage()));
                }
            } else {
                $this->log('Invalid callable for job. Deleting job from queue.');
                $this->engine->delete($item);
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

        return true;
    }
}
