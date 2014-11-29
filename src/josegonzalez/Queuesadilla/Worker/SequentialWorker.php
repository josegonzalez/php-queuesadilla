<?php

namespace josegonzalez\Queuesadilla\Worker;

use Exception;
use josegonzalez\Queuesadilla\Worker\Base;

class SequentialWorker extends Base
{
    protected $stats = null;

    /**
     * {@inheritDoc}
     */
    public function work()
    {
        if (!$this->connect()) {
            $this->logger()->alert(sprintf('Worker unable to connect, exiting'));
            return false;
        }

        $iterations = 0;
        $jobClass = $this->engine->getJobClass();

        while (true) {
            if (is_int($this->maxIterations) && $iterations >= $this->maxIterations) {
                $this->logger()->debug('Max iterations reached, exiting');
                break;
            }

            $iterations++;
            $item = $this->engine->pop($this->queue);
            $this->stats['seen']++;
            if (empty($item)) {
                $this->logger()->debug('No job!');
                $this->stats['empty']++;
                sleep(1);
                continue;
            }

            $success = false;
            $job = new $jobClass($item, $this->engine);
            if (!is_callable($item['class'])) {
                $this->logger()->alert('Invalid callable for job. Deleting job from queue.');
                $this->engine->delete($item);
                $this->stats['invalid']++;
                continue;
            }

            try {
                $success = $this->perform($item, $job);
            } catch (Exception $e) {
                $this->logger()->alert(sprintf('Exception: "%s"', $e->getMessage()));
                $this->stats['exception']++;
            }

            if ($success) {
                $this->logger()->debug('Success. Deleting job from queue.');
                $job->delete();
                $this->stats['success']++;
                continue;
            }

            $this->logger()->info('Failed. Releasing job to queue');
            $job->release();
            $this->stats['failure']++;
        }

        return true;
    }

    public function connect()
    {
        $maxIterations = $this->maxIterations ? sprintf(', max iterations %s', $this->maxIterations) : '';
        $this->logger()->info(sprintf('Starting worker%s', $maxIterations));
        return $this->engine->connected();
    }

    public function perform($item, $job)
    {
        if (!is_callable($item['class'])) {
            return false;
        }

        $success = false;
        if (is_array($item['class']) && count($item['class']) == 2) {
            $item['class'][0] = new $item['class'][0];
            $success = $item['class'][0]->$item['class'][1]($job);
        } elseif (is_string($item['class'])) {
            $success = call_user_func($item['class'], $job);
        }

        if ($success !== false) {
            $success = true;
        }

        return $success;
    }
}
