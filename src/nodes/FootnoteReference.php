<?php 

namespace rasteiner\Wordparser\nodes;

use IteratorAggregate;
use rasteiner\Wordparser\Node;
use rasteiner\Wordparser\Parser;
use rasteiner\Wordparser\Xml;
use SimpleXMLElement;
use Traversable;

class FootnoteReference extends ContainerNode {
    public function __construct(public int $id, public Traversable $children)
    {}

    public static function parse(SimpleXMLElement $xml, Parser $parser): ?static {
        if(!Xml::is($xml, 'footnoteReference')) return null;

        //technically, there are footnote references without an id, but we don't care about them 
        $id = Xml::attr($xml, 'id');
        if($id === null) return null;
        $id = (int) $id;
        
        // load footnote content
        $newParser = clone $parser;
        $newParser->defaultRelType = 'footnotes';
        $footnote = $newParser->footnotes->get($id);
        $content = $newParser->matchChildren($footnote, [
            Paragraph::class,
            Table::class,
        ]);

        return new static($id, $content);
    }
}