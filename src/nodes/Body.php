<?php

namespace rasteiner\Wordparser\nodes;

use IteratorAggregate;
use rasteiner\Wordparser\Node;
use rasteiner\Wordparser\Parser;
use rasteiner\Wordparser\Xml;
use SimpleXMLElement;
use Traversable;

class Body extends ContainerNode {
    public function __construct(public Traversable $children)
    {}

    public static function parse(SimpleXMLElement $xml, Parser $parser): ?static {
        return Xml::is($xml, 'body') ? new static($parser->matchChildren($xml, static::contains())) : null;
    }

    public static function contains(): array
    {
        return [
            Heading::class,
            Paragraph::class,
            Table::class,
        ];
    }
}