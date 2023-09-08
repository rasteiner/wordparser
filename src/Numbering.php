<?php 

namespace rasteiner\Wordparser;

use Exception;
use NumberFormatter;
use SimpleXMLElement;

class Numbering {
    protected array $abstracts = [];
    protected array $lists = [];

    protected array $counters = [];

    private function readLevel(SimpleXMLElement $level): array {
        $style = Xml::val($level, 'pStyle');
        $ilvl = intval(Xml::attr($level, 'ilvl'));
        $level = [
            'ilvl' => $ilvl,
            'start' => intval(Xml::val($level, 'start')),
            'numFmt' => Xml::val($level, 'numFmt'),
            'lvlText' => Xml::val($level, 'lvlText'),
            'lvlRestart' => intval(Xml::val($level, 'lvlRestart')),
            'pStyle' => $style,
        ];
        return $level;
    }

    public function __construct(SimpleXMLElement $xml) {
        if(!Xml::is($xml, 'numbering')) {
            throw new Exception('Invalid numbering: Root element must be <w:numbering>');
        }

        // foreach child 
        foreach(Xml::children($xml) as $child) {
            if(Xml::is($child, 'abstractNum')) {
                $abstract = [
                    'id' => Xml::attr($child, 'abstractNumId'),
                    'levels' => []
                ];
                
                // foreach child
                foreach(Xml::children($child) as $level) {
                    if(Xml::is($level, 'lvl')) {
                        $level = $this->readLevel($level);
                        $abstract['levels'][$level['ilvl']] = $level;

                        $style = $level['pStyle'];
                        if($style !== null) {
                            $this->lists[$style] = new ListInstance($style, $abstract['id'], null, $this);
                            $this->lists[$style]->defaultLevel = $level['ilvl'];
                        }

                        $this->counters[$abstract['id'] . ':' . $level['ilvl']] = $level['start'];
                    }
                }

                $this->abstracts[$abstract['id']] = $abstract;
            } else if (Xml::is($child, 'num')) {
                $id = Xml::attr($child, 'numId'); 
                $abstractId = Xml::val($child, 'abstractNumId');

                $overrides = [];
                foreach(Xml::children($child) as $override) {
                    if(Xml::is($override, 'lvlOverride')) {
                        $ilvl = intval(Xml::attr($override, 'ilvl'));
                        $startOverride = intval(Xml::val($override, 'startOverride'));
                        $lvlOverride = Xml::child($override, 'lvl');
                        if($lvlOverride !== null) {
                            $lvlOverride = $this->readLevel($lvlOverride);
                        }
                        $overrides[$ilvl] = [
                            'startOverride' => $startOverride,
                            'lvl' => $lvlOverride
                        ];
                    }
                }

                $this->lists[$id] = new ListInstance($id, $abstractId, $overrides?:null, $this);
            }
        }
    }

    public function getAbstract(string $id): ?array {
        return $this->abstracts[$id] ?? null;
    }

    public function get(string $id): ?ListInstance {
        return $this->lists[$id] ?? null;
    }

    public function reset(string $abstractId, int $ilvl = 0, ?int $start = null) {
        $abstract = $this->getAbstract($abstractId);
        if($abstract === null) {
            throw new Exception("Abstract numbering with id $abstractId not found");
        }

        foreach($abstract['levels'] as $level) {
            if($level['ilvl'] >= $ilvl) {
                $this->counters[$abstractId . ':' . $level['ilvl']] = $level['start'] ?? 1;
            }
        }
    }

    public function current(string $abstractId, int $ilvl = 0) {
        return $this->counters[$abstractId . ':' . $ilvl] ?? 1;
    }

    public function next(string $abstractId, int $ilvl = 0) {
        $curr = $this->current($abstractId, $ilvl);

        // reset other levels 
        foreach($this->abstracts[$abstractId]['levels'] as $level) {
            if($level['ilvl'] >= $ilvl) {
                $this->counters[$abstractId . ':' . $level['ilvl']] = $level['start'] ?? 1;
            }
        }

        return $this->counters[$abstractId . ':' . $ilvl] = $curr + 1;
    }

    protected function formatDecimalEnclosedCircle(int $value): string {
        // when 0, use code point 24EA
        // when between 1 and 20, use enclosed circle code points 2460 - 2473
        // when between 21 and 35, use enclosed circle code points 3251 - 325F
        // when between 36 and 50, use enclosed circle code points 32B1 - 32BF
        // when over 50 return decimal

        if($value === 0) {
            return chr(0x24EA);
        } else if($value >= 1 && $value <= 20) {
            return chr(0x2460 + $value - 1);
        } else if($value >= 21 && $value <= 35) {
            return chr(0x3251 + $value - 21);
        } else if($value >= 36 && $value <= 50) {
            return chr(0x32B1 + $value - 36);
        } else {
            return $value;
        }
    }

    protected function formatLetter(int $value): string {
        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $len = strlen($letters);
        $result = '';
        while($value > 0) {
            $value--;
            $result = $letters[$value % $len] . $result;
            $value = floor($value / $len);
        }
        return $result;
    }

    protected function formatRoman(int $value): string {
        static $formatter = new NumberFormatter('@numbers=roman', NumberFormatter::DECIMAL);
        return $formatter->format($value);
    }

    protected function formatOrdinalText(int $value): string {
        $formatter = new NumberFormatter(locale_get_default(), NumberFormatter::ORDINAL);
        return $formatter->format($value);
    }

    protected function formatCardinalText(int $value): string {
        $formatter = new NumberFormatter(locale_get_default(), NumberFormatter::SPELLOUT);
        return $formatter->format($value);
    }

    protected function countIn(string $format, int $value): string {
        return match($format) {
            'decimal' => $value,
            'decimalZero' => sprintf('%02d', $value),
            'decimalEnclosedCircle' => $this->formatDecimalEnclosedCircle($value),
            'decimalEnclosedFullstop' => $value . '.',
            'decimalEnclosedParen' => '(' . $value . ')',
            'upperLetter' => $this->formatLetter($value),
            'lowerLetter' => strtolower($this->formatLetter($value)),
            'upperRoman' => $this->formatRoman($value),
            'lowerRoman' => strtolower($this->formatRoman($value)),
            'ordinalText' => $this->formatOrdinalText($value),
            'cardinalText' => $this->formatCardinalText($value),
            default => '',
        };
    }

    public function render(string $abstractId, int $ilvl): string {
        // get style from abstract 
        $abstract = $this->getAbstract($abstractId);
        if(!$abstract) return '';
        
        $level = $abstract['levels'][$ilvl];
        $text = $level['lvlText'];

        return preg_replace_callback('/%(\d+)/', function($matches) use ($abstractId, $abstract) {
            $index = intval($matches[1]) - 1;

            $level = $abstract['levels'][$index];
            $fmt = $level['numFmt'];
            $count = $this->current($abstractId, $index);
            $txt = $this->countIn($fmt, $count);

            return $txt ?? '';
        }, $text);
    }
}