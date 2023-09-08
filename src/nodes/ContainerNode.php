<?php

namespace rasteiner\Wordparser\nodes;

use IteratorAggregate;
use rasteiner\Wordparser\Node;
use rasteiner\Wordparser\Parser;
use rasteiner\Wordparser\Xml;
use SimpleXMLElement;
use Traversable;

abstract class ContainerNode extends Node {
    public function __construct(public Traversable $children)
    {}

    public function children(): Traversable {
        yield from $this->children;
    }
}