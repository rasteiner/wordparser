<?php

namespace rasteiner\Wordparser;

use SimpleXMLElement;

class Stylesheet {
    protected $styles = [];
    protected $styleNames = [];
    protected $styleTypes = [];
    
    public function __construct(SimpleXMLElement $xml)
    {
        $styles = $xml->xpath('//w:style');
        $styleProps = [];
        $basedOn = [];

        foreach ($styles as $style) {
            $styleId = Xml::attr($style, 'styleId');
            $basedOn[$styleId] = Xml::val($style, 'basedOn');

            $styleProps[$styleId] = match($props = Xml::child($style, 'rPr')) {
                null => [],
                default => Style::parseXML($props)
            };

            $this->styleTypes[$styleId] = Xml::val($style, 'type');
            $this->styleNames[$styleId] = Xml::val($style, 'name');
        }

        foreach($styleProps as $styleId => $props) {
            // this kind of assumes that styles are ordered by their dependencies
            $this->styles[$styleId] = new Style(
                $styleId,
                $this->getName($styleId),
                $basedOn[$styleId] ? $this->styles[$basedOn[$styleId]] : null,
                $props
            );
        }
    }

    public function get(string $styleId): ?Style {
        return $this->styles[$styleId] ?? null;
    }

    public function getName(string $styleId): ?string {
        return $this->styleNames[$styleId] ?? null;
    }
}