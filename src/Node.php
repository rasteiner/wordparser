<?php

namespace rasteiner\Wordparser;

use IteratorAggregate;
use SimpleXMLElement;
use Traversable;

abstract class Node implements HasSpec {
    public static function contains(): array
    {
        return [];
    }
}