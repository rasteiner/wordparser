<?php 

namespace rasteiner\Wordparser\nodes;

use rasteiner\Wordparser\Node;
use rasteiner\Wordparser\Parser;
use rasteiner\Wordparser\Xml;
use SimpleXMLElement;

class HardBreak extends Node {
    public function __construct()
    {}

    public static function parse(SimpleXMLElement $xml, Parser $parser): ?static {
        return Xml::is($xml, 'br') ? new static() : null;
    }
}