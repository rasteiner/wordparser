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

class Run extends ContainerNode implements HasStyle {

    public function __construct(public Style $style, public Traversable $children)
    {}

    public function getStyle(): Style {
        return $this->style;
    }
    
    public static function parse(SimpleXMLElement $xml, Parser $parser): ?static {
        if(!Xml::is($xml, 'r')) return null;

        // load properties
        $props = Xml::child($xml, 'rPr');
        if($props !== null) {
            $style = match($id = Xml::val($props, 'rStyle')) {
                null => new Style(),
                default => $parser->stylesheet->get($id)
            };

            $inlineStyle = Style::parseXML($props);

            $style = new Style(null, $style, $inlineStyle);
        } else {
            $style = new Style();
        }

        return new static($style, $parser->matchChildren($xml, self::contains()));
    }

    public static function contains(): array
    {
        return [
            FootnoteReference::class,
            Text::class,
            HardBreak::class,
            Image::class,
        ];
    }
}