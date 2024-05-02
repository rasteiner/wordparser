<?php 

namespace rasteiner\Wordparser;

use SimpleXMLElement;

class Style {

    public function __construct(
        public readonly ?string $id = null,
        public readonly ?string $name = null,
        protected ?Style $basedOn = null,
        protected array $properties = []
    )
    {
        
    }

    public function basedOn(): ?Style {
        return $this->basedOn;
    }

    public function withBasedOn(?Style $basedOn): self {
        $this->basedOn = $basedOn;
        return $this;
    }

    public function clone(): self {
        return clone $this;
    }

    public function __get($name): bool|int|string|null
    {
        return $this->properties[$name] ?? (
            $this->basedOn ? $this->basedOn->$name : null
        );
    }

    public function isEmpty() {
        return count($this->properties) === 0;
    }

    public function numbering(Numbering $numbering): ?ListInstance {
        if($this->id and $list = $numbering->get($this->id)) {
            return $list;
        } else if($this->basedOn) {
            return $this->basedOn->numbering($numbering);
        } else {
            return null;
        }
    }

    public function equals(Style|array $other): bool {
        if($this === $other) return true;

        // get flattened styles 
        $thisProps  = $this->flat()->properties;
        $otherProps = $other instanceof Style ? $other->flat()->properties : $other;
        
        // filter for only set properties
        $thisProps  = array_filter($thisProps,  fn($v) => $v !== null);
        $otherProps = array_filter($otherProps, fn($v) => $v !== null);
        
        if(count($otherProps) !== count($thisProps)) return false;
        if(count(array_diff_key($otherProps, $thisProps)) > 0) return false;

        foreach($thisProps as $key => $value) {
            if($otherProps[$key] !== $value) return false;
        }

        return true;
    }

    public function includes(Style|array $other): bool {
        if($this === $other) return true;

        // get flattened styles
        $otherProps = $other instanceof Style ? $other->properties : $other;
        $thisProps  = $this->flat()->properties;

        // filter for only set properties
        $otherProps = array_filter($otherProps, fn($v) => $v !== null);
        $thisProps  = array_filter($thisProps,  fn($v) => $v !== null);

        foreach($otherProps as $key => $value) {
            if(!isset($thisProps[$key])) return false;
            if($thisProps[$key] !== $value) return false;
        }

        return true;
    }

    public function unset(string ...$properties): self {
        foreach($properties as $prop) {
            unset($this->properties[$prop]);
        }

        return $this;
    }

    public function merge(Style|array $other): self {
        $otherProps = $other instanceof Style ? $other->properties : $other;
        $otherProps = array_filter($otherProps, fn($v) => $v !== null);

        $this->properties = array_merge($this->properties, $otherProps);

        return $this;
    }

    public function flat(): self {
        $props = $this->properties;
        if($this->basedOn) {
            $props = array_merge($this->basedOn->flat()->properties, $props);
        }

        return new self(properties: $props);
    }

    public function toCSSProperties() {
        $props = $this->flat()->properties;

        $css = [];

        if($props['bold'] ?? false) $css['font-weight'] = 'bold';
        if($props['italic'] ?? false) $css['font-style'] = 'italic';
        if($props['underline'] ?? false) $css['text-decoration'] = 'underline';
        if($props['strike'] ?? false) $css['text-decoration'] = 'line-through';
        if($props['smallCaps'] ?? false) $css['font-variant'] = 'small-caps';

        if($props['color'] ?? false) $css['color'] = '#' . $props['color'];
        if($props['background'] ?? false) $css['background-color'] = '#' . $props['background'];

        if($props['font'] ?? false) $css['font-family'] = $props['font'];
        if($props['size'] ?? false) $css['font-size'] = ($props['size']/2) . 'pt';

        return $css;        
    }

    public function toCSS() {
        $props = $this->toCSSProperties();

        return join(';', array_map(fn($k, $v) => "$k: $v", array_keys($props), $props));
    }

    static function parseXML(SimpleXMLElement $props): array {
        $properties = [];

        // bold
        if(null !== $bold = Xml::child($props, 'b')) {
            if(Xml::attr($bold, 'val') === "0") {
                $properties['bold'] = false;
            } else {
                $properties['bold'] = true;
            }
        }

        // italic
        if(null !== $italic = Xml::child($props, 'i')) {
            if(Xml::attr($italic, 'val') === "0") {
                $properties['italic'] = false;
            } else {
                $properties['italic'] = true;
            }
        }

        // underline
        if(null !== $underline = $props->xpath('w:u')[0] ?? null) {
            if(Xml::attr($underline, 'val') === "none") {
                $properties['underline'] = false;
            } else {
                $properties['underline'] = true;
            }
        }

        // strike
        if(null !== $strike = $props->xpath('w:strike')[0] ?? null) {
            if(Xml::attr($strike, 'val') === "0") {
                $properties['strike'] = false;
            } else {
                $properties['strike'] = true;
            }
        }

        // small caps
        if(null !== $smallCaps = $props->xpath('w:smallCaps')[0] ?? null) {
            if(Xml::attr($smallCaps, 'val') === "0") {
                $properties['smallCaps'] = false;
            } else {
                $properties['smallCaps'] = true;
            }
        }

        // color
        if (null !== $color = $props->xpath('w:color')[0] ?? null) {
            $properties['color'] = (string)$color->attributes('w', true)['val'];
        }

        // font
        if (null !== $font = $props->xpath('w:rFonts')[0] ?? null) {
            $properties['font'] = (string)$font->attributes('w', true)['ascii'];
        }

        // size
        if (null !== $size = $props->xpath('w:sz')[0] ?? null) {
            $properties['size'] = (int) $size->attributes('w', true)['val'];
        }

        // shading
        if (null !== $shading = $props->xpath('w:shd')[0] ?? null) {
            $properties['background'] = (string) $shading->attributes('w', true)['fill'];
        }

        return $properties;
    }
}