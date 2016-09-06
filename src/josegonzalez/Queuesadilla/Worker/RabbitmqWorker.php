<?php

namespace josegonzalez\Queuesadilla\Worker;

use Exception;
use josegonzalez\Queuesadilla\Worker\SequentialWorker;

class RabbitmqWorker extends SequentialWorker
{
        /**
     * {@inheritDoc}
     */
    public function work()
    {
        if (!$this->connect()) {
            $this->logger()->alert(sprintf('Worker unable to connect, exiting'));
            $this->dispatchEvent('Worker.job.connectionFailed');
            return false;
        }

        $jobClass = $this->engine->getJobClass();
        $handler = function ($message) use ($jobClass) {
            $item = json_decode($message->body, true);
            $item['_delivery_tag'] = $message->delivery_info['delivery_tag'];
            $this->dispatchEvent('Worker.job.seen', ['item' => $item]);

            $success = false;
            $job = new $jobClass($item, $this->engine);
            if (!is_callable($item['class'])) {
                $this->logger()->alert('Invalid callable for job. Rejecting job from queue.');
                $job->reject();
                $this->dispatchEvent('Worker.job.invalid', ['job' => $job]);
                return true;
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
                return;
            }

            $this->logger()->info('Failed. Releasing job to queue');
            $job->release();
            $this->dispatchEvent('Worker.job.failure', ['job' => $job]);
        };

        $this->engine->attachHandler(['handler' => $handler]);
        while ($this->engine->canWork()) {
            if (is_int($this->maxIterations) && $this->iterations >= $this->maxIterations) {
                $this->logger()->debug('Max iterations reached, exiting');
                $this->dispatchEvent('Worker.maxIterations');
                break;
            }
            $this->iterations++;
            $this->engine->work();
        }
    }

    protected function disconnect()
    {
        $connection = $this->engine->connection();
        if ($connection !== null && $connection->isConnected()) {
            $this->logger()->debug("Shutting down amqp connection");
            $connection->close();
        }
    }
}
