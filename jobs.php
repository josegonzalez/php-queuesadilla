<?php

function raise($job) {
  throw new Exception("Screw you");
}

class Output {
  public function output($job) {
    var_dump($job->data());
  }
}

class MyJob {

  public static function run($job) {
    printf("[MyJob] " . $job->data('message') . "\n");
    $sleep = $job->data('sleep');
    if (!empty($sleep)) {
      printf("[MyJob] Sleeping for " . $job->data('sleep') . " seconds\n");
      sleep($job->data('sleep'));
    }
  }

}
