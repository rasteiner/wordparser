<?php

namespace rasteiner\Wordparser\nodes;

use IteratorAggregate;
use rasteiner\Wordparser\HasStyle;
use rasteiner\Wordparser\Node;
use rasteiner\Wordparser\Parser;
use rasteiner\Wordparser\Style;
use rasteiner\Wordparser\Xml;
use SimpleXMLElement;
use Traversable;

class Table extends ContainerNode implements HasStyle {
    public function __construct(public Traversable $children, public Style $style)
    {
        
    }
    
    public function getStyle(): Style {
        return $this->style;
    }

    public static function parse(SimpleXMLElement $xml, Parser $parser): ?static {
        if(!Xml::is($xml, 'tbl')) return null;

        $props = Xml::child($xml, 'tblPr');
        $style = new Style(properties: Style::parseXML($props));

        return new static($parser->matchChildren($xml, static::contains()), $style);
    }

    public static function contains(): array
    {
        return [
            TableRow::class,
        ];
    }
}