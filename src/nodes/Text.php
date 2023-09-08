<?php 

namespace rasteiner\Wordparser\nodes;

use rasteiner\Wordparser\Node;
use rasteiner\Wordparser\Parser;
use rasteiner\Wordparser\Xml;
use SimpleXMLElement;

class Text extends Node {
    public function __construct(public string $text)
    {}

    public static function parse(SimpleXMLElement $xml, Parser $parser): ?static {
        return Xml::is($xml, 't') ? new static((string) $xml) : null;
    }
}