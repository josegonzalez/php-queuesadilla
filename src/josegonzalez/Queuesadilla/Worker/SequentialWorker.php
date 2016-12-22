<?php

namespace josegonzalez\Queuesadilla\Worker;

use Exception;
use josegonzalez\Queuesadilla\Worker\Base;

class SequentialWorker extends Base
{
    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function work()
    {
        if (!$this->connect()) {
            $this->logger()->alert(sprintf('Worker unable to connect, exiting'));
            $this->dispatchEvent('Worker.job.connectionFailed');
            return false;
        }

        $jobClass = $this->engine->getJobClass();
        $time = microtime(true);
        while (true) {
            if (is_int($this->maxRuntime) && $this->runtime >= $this->maxRuntime) {
                $this->logger()->debug('Max runtime reached, exiting');
                $this->dispatchEvent('Worker.maxRuntime');
                break;
            }
            $this->runtime += microtime(true) - $time;
            $time = microtime(true);

            if (is_int($this->maxIterations) && $this->iterations >= $this->maxIterations) {
                $this->logger()->debug('Max iterations reached, exiting');
                $this->dispatchEvent('Worker.maxIterations');
                break;
            }

            $this->iterations++;
            $item = $this->engine->pop($this->queue);
            $this->dispatchEvent('Worker.job.seen', ['item' => $item]);
            if (empty($item)) {
                $this->logger()->debug('No job!');
                $this->dispatchEvent('Worker.job.empty');
                sleep(1);
                continue;
            }

            $success = false;
            $job = new $jobClass($item, $this->engine);
            if (!is_callable($item['class'])) {
                $this->logger()->alert('Invalid callable for job. Rejecting job from queue.');
                $job->reject();
                $this->dispatchEvent('Worker.job.invalid', ['job' => $job]);
                continue;
            }

            try {
                $success = $this->perform($item, $job);
            } catch (Exception $e) {
                $this->logger()->alert(sprintf('Exception: "%s"', $e->getMessage()));
                $this->dispatchEvent('Worker.job.exception', [
                    'job' => $job,
                    'exception' => $e,
                ]);
            }

            if ($success) {
                $this->logger()->debug('Success. Acknowledging job on queue.');
                $job->acknowledge();
                $this->dispatchEvent('Worker.job.success', ['job' => $job]);
                continue;
            }

            $this->logger()->info('Failed. Releasing job to queue');
            $job->release();
            $this->dispatchEvent('Worker.job.failure', ['job' => $job]);
        }

        return true;
    }

    public function connect()
    {
        $maxIterations = $this->maxIterations ? sprintf(', max iterations %s', $this->maxIterations) : '';
        $this->logger()->info(sprintf('Starting worker%s', $maxIterations));
        return (bool)$this->engine->connection();
    }

    public function perform($item, $job)
    {
        if (!is_callable($item['class'])) {
            return false;
        }

        $success = false;
        if (is_array($item['class']) && count($item['class']) == 2) {
            $className = $item['class'][0];
            $methodName = $item['class'][1];
            $instance = new $className;
            $success = $instance->$methodName($job);
        } elseif (is_string($item['class'])) {
            $success = call_user_func($item['class'], $job);
        }

        if ($success !== false) {
            $success = true;
        }

        return $success;
    }

    protected function disconnect()
    {
    }
}
