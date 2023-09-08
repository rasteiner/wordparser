<?php 

namespace rasteiner\Wordparser\nodes;

use IteratorAggregate;
use rasteiner\Wordparser\Node;
use rasteiner\Wordparser\Parser;
use rasteiner\Wordparser\Xml;
use SimpleXMLElement;
use Traversable;

class Link extends ContainerNode {
    public function __construct(public string $url, public Traversable $children)
    {}

    public static function parse(SimpleXMLElement $xml, Parser $parser): ?static {
        if(!Xml::is($xml, 'hyperlink')) return null;

        // rel id 
        $relId = Xml::attr($xml, 'id', 'r');
        $rel = $parser->getRelation($relId);

        if($rel === null) return null;

        // url
        $url = $rel->target;

        // text
        $children = $parser->matchChildren($xml, [
            Text::class,
            Run::class,
        ]);

        return new static($url, $children);
    }
}