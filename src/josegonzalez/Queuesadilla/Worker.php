<?php

namespace josegonzalez\Queuesadilla;

class Worker
{

    public function __construct($backend, $params = array())
    {
        $params = array_merge(array(
            'max_iterations' => null,
            'queue' => null,
        ), $params);

        $this->_backend = $backend;
        $this->_queue = $params['queue'];
        $this->_max_iterations = $params['max_iterations'];

        $this->_name = get_class($this->_backend);
        if (preg_match('@\\\\([\w]+)$@', $this->_name, $matches)) {
            $this->_name = $matches[1];
        }

        $this->_name = str_replace('Backend', '', $this->_name) . ' Worker';
    }

    public function log($message)
    {
        printf("[%s] %s\n", $this->_name, $message);
    }

    public function work()
    {
        $max_iterations = $this->_max_iterations ? sprintf(', max iterations %s', $this->_max_iterations) : '';
        $this->log(sprintf('Starting worker%s', $max_iterations));
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
                } catch (\Exception $e) {
                    $this->log(sprintf('Exception: "%s"', $e->getMessage()));
                }
            } else {
                $this->log('Invalid callable for job. Deleting job from queue.');
                $this->_backend->delete($item);
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
