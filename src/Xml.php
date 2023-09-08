<?php 

namespace rasteiner\Wordparser;

use SimpleXMLElement;

class Xml {

    public static function is(SimpleXMLElement $xml, string $name): bool {
        return $xml->getName() === $name;
    }

    public static function children(SimpleXMLElement $xml, string $ns = 'w') {
        return $xml->children($ns, true);
    }

    public static function child(SimpleXMLElement $xml, string $name, string $ns = 'w'): ?SimpleXMLElement {
        $name = str_replace('/', "/$ns:", $name);
        $result = $xml->xpath("$ns:$name");
        if(count($result)) {
            return $result[0];
        }
        return null;
    }

    public static function attr(SimpleXMLElement $xml, string $name, string $ns = 'w'): ?string {
        $attrs = $xml->attributes($ns, true);
        return match($a = $attrs[$name] ?? null) {
            null => null,
            default => (string) $a
        };
    }

    public static function val(SimpleXMLElement $xml, string $name, string $ns = 'w'): ?string {
        $name = str_replace('/', "/$ns:", $name);
        $result = $xml->xpath("$ns:$name/@$ns:val");
        return match($r = $result[0] ?? null) {
            null => null,
            default => (string) $r
        };
    }
}