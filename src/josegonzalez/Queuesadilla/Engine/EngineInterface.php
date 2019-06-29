<?php

namespace josegonzalez\Queuesadilla\Engine;

/**
 * Describes a queue engine
 */
interface EngineInterface
{

    /**
     * Returns a string representation of the class name
     *
     * @return string
     */
    public function getJobClass();

    /**
     * Gets a configuration setting
     *
     * @param array  $settings    an array of settings
     * @param string $key         a key to set or retrieve
     * @param mixed  $default     a default value to return if the config value does not exist
     *
     * @return mixed
     **/
    public function setting($settings, $key, $default = null);

    /**
     * Creates a connection to the underlying engine datastore
     *
     * @return boolean
     **/
    public function connect();

    /**
     * Returns a connection to the underlying datastore
     *
     * @return mixed
     **/
    public function connection();

    /**
     * Acknowledges a message on the queue.
     *
     * @param  array  $item       an array of item data
     *
     * @return void
     */
    public function acknowledge($item);

    /**
     * Rejects a message from the queue.
     *
     * @param  array  $item       an array of item data
     *
     * @return void
     */
    public function reject($item);

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
