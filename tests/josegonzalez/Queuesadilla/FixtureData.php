<?php

namespace josegonzalez\Queuesadilla;

class FixtureData
{
    public $default = [
        'first' =>  ['id' => '1', 'class' => null,                   'args' => [], 'options' => [], 'queue' => 'default'],
        'second' => ['id' => '2', 'class' => 'some_function',        'args' => [], 'options' => [], 'queue' => 'default'],
        'third' =>  ['id' => '3', 'class' => 'another_function',     'args' => [], 'options' => [], 'queue' => 'default'],
        'fourth' => ['id' => '4', 'class' => 'yet_another_function', 'args' => [], 'options' => [], 'queue' => 'default'],
    ];

    public $other = [
        'first' =>  ['id' => '1', 'class' => null,                   'args' => [], 'options' => [], 'queue' => 'other'],
        'second' => ['id' => '2', 'class' => 'some_function',        'args' => [], 'options' => [], 'queue' => 'other'],
        'third' =>  ['id' => '3', 'class' => 'another_function',     'args' => [], 'options' => [], 'queue' => 'other'],
        'fourth' => ['id' => '4', 'class' => 'yet_another_function', 'args' => [], 'options' => [], 'queue' => 'other'],
    ];
}
