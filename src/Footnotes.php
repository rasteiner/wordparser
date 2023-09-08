<?php 

namespace rasteiner\Wordparser;

use SimpleXMLElement;

class Footnotes {

    protected $notes = [];

    public function __construct(public readonly SimpleXMLElement $xml) {
        foreach(Xml::children($xml) as $note) {
            if(!Xml::is($note, 'footnote')) continue;

            $id = Xml::attr($note, 'id');
            if($id === null) {
                error_log("Footnote without id");
                continue;
            }
            $id = (int) $id;
            $this->notes[$id] = $note;
        }
    }

    public function get(int $id): ?SimpleXMLElement {
        return $this->notes[$id] ?? null;
    }
}