<?php

namespace josegonzalez\Queuesadilla\Engine;

/**
 * Describes a queue engine
 */
interface EngineInterface
{

    public function getJobClass();

    public function setting($settings, $key, $default = null);

    public function connect();

    public function connection();

    /**
     * Delete a message from the queue.
     *
     * @param  array  $item       an array of item data
     * @param  boole  $success    whether the message should be ackd or rejected based on it's success
     *
     * @return void
     */
    public function delete($item, $acknowledge = true);

    /**
     * Pop the next job off of the queue.
     *
     * @param array  $options     an array of options for popping a job from the queue
     *
     * @return array an array of item data
     */
    public function pop($options = []);

    /**
     * Push a single job onto the queue.
     *
     * @param array  $item        an item payload
     * @param array  $options     an array of options for publishing the job
     *
     * @return boolean
     **/
    public function push($item, $options = []);

    /**
     * Get a list of available queues
     *
     * @return array
     */
    public function queues();

    /**
     * Release the job back into the queue.
     *
     * @param  array  $item       an array of item data
     *
     * @return boolean
     */
    public function release($item, $options = []);
}
