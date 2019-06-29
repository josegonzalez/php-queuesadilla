[![Build Status](https://img.shields.io/travis/josegonzalez/php-queuesadilla/master.svg?style=flat-square)](https://travis-ci.org/josegonzalez/php-queuesadilla)
[![Coverage Status](https://img.shields.io/coveralls/josegonzalez/php-queuesadilla/master.svg?style=flat-square)](https://coveralls.io/r/josegonzalez/php-queuesadilla?branch=master)
[![Total Downloads](https://img.shields.io/packagist/dt/josegonzalez/queuesadilla.svg?style=flat-square)](https://packagist.org/packages/josegonzalez/queuesadilla)
[![Latest Stable Version](https://img.shields.io/packagist/v/josegonzalez/queuesadilla.svg?style=flat-square)](https://packagist.org/packages/josegonzalez/queuesadilla)
[![Gratipay](https://img.shields.io/gratipay/josegonzalez.svg?style=flat-square)](https://gratipay.com/~josegonzalez/)

# Queuesadilla

A job/worker system built to support various queuing systems.

## Requirements

- PHP 7.2+

## Installation

_[Using [Composer](http://getcomposer.org/)]_

Add the plugin to your project's `composer.json` - something like this:

```composer
{
  "require": {
    "josegonzalez/queuesadilla": "dev-master"
  }
}
```

## Usage

- [Installation](/docs/installation.md)
- [Supported Systems](/docs/supported-systems.md)
- [Simple Usage](/docs/simple-usage.md)
- [Defining Jobs](/docs/defining-jobs.md)
- [Job Options](/docs/job-options.md)
- [Available Callbacks](/docs/callbacks.md)

## Tests

Tests are run via `phpunit` and depend upon multiple datastores. You may also run tests using the included `Dockerfile`:

```shell
docker build .
```
