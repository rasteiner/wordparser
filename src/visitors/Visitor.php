<?php 

namespace rasteiner\Wordparser\visitors;

use rasteiner\Wordparser\Node;

/**
 * Base class for visitors.
 * @template T
 */
abstract class Visitor {
    /**
     * @param Node $node
     * @return T
     */
    public function visit(Node $node) {
        // get local name
        $name = (new \ReflectionClass($node))->getShortName();
        $method = 'visit' . $name;

        return $this->$method($node);
    }
}