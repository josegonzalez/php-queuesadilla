<?php

namespace josegonzalez\Queuesadilla;

class FixtureData
{
    public $default = [
        'first' =>  ['id' => '1', 'class' => null,                   'args' => [], 'options' => [], 'queue' => 'default', 'attempts' => 0],
        'second' => ['id' => '2', 'class' => 'some_function',        'args' => [], 'options' => [], 'queue' => 'default', 'attempts' => 0],
        'third' =>  ['id' => '3', 'class' => 'another_function',     'args' => [], 'options' => [], 'queue' => 'default', 'attempts' => 0],
        'fourth' => ['id' => '4', 'class' => 'yet_another_function', 'args' => [], 'options' => [], 'queue' => 'default', 'attempts' => 0],
        'fifth' =>  ['id' => '5', 'class' => 'some_function',        'args' => [], 'options' => [], 'queue' => 'default', 'attempts' => 1],
    ];

    public $other = [
        'first' =>  ['id' => '1', 'class' => null,                   'args' => [], 'options' => [], 'queue' => 'other', 'attempts' => 0],
        'second' => ['id' => '2', 'class' => 'some_function',        'args' => [], 'options' => [], 'queue' => 'other', 'attempts' => 0],
        'third' =>  ['id' => '3', 'class' => 'another_function',     'args' => [], 'options' => [], 'queue' => 'other', 'attempts' => 0],
        'fourth' => ['id' => '4', 'class' => 'yet_another_function', 'args' => [], 'options' => [], 'queue' => 'other', 'attempts' => 0],
    ];
}
